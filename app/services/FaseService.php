<?php
/*
 * =====================================================
 * Carpintería Carpin – app/services/FaseService.php
 * Reglas de las fases de producción (RN-01 a RN-04 y SRS 5.4):
 *   - avance normal: solo el responsable de la siguiente fase
 *     y solo a la fase inmediata siguiente (RN-02);
 *   - corrección del Carpintero a cualquier fase con motivo
 *     obligatorio (RN-03);
 *   - avance = porcentaje de la fase (RN-01);
 *   - advertencia de saldo al marcar "Entregado" (RN-04);
 *   - estado de las actividades, historial y notificaciones.
 * =====================================================
 */

final class FaseService {

    /* ===== Avance normal del Carpintero sin motivo (SRS 5.4) ===== */
    private const AVANCE_NORMAL_CARPINTERO = [
        1 => 2,
        2 => 3,
        3 => 4,
        9 => 10,
    ];

    /* ===== Rol → tipo de actividad que debe tener para avanzar (RN-02) ===== */
    private const TIPO_POR_ROL = [
        Auth::LIJADOR => 'LIJADO',
        Auth::PINTOR  => 'PINTURA',
    ];

    /* ===== Consultas de reglas ===== */

    /**
     * Devuelve la fase a la que el usuario puede avanzar el proyecto de
     * forma normal (sin motivo), o null si no le toca (RN-02 y SRS 5.4).
     *
     * Recibe: $proyecto, datos del proyecto (id_proyecto, id_fase);
     *         $usuario, datos de la sesión (id_usuario, rol).
     * Devuelve: datos de la siguiente fase, o null.
     */
    public static function siguienteFasePermitida(array $proyecto, array $usuario): ?array {
        $faseActual = (int) $proyecto['id_fase'];
        $modeloFase = new Fase();
        $actual = $modeloFase->buscarPorId($faseActual);
        $siguiente = $modeloFase->siguiente((int) $actual['orden']);

        if ($siguiente === null || $siguiente['rol_responsable'] !== $usuario['rol']) {
            return null;
        }

        // El Carpintero avanza 1→2→3→4 y 9→10 sin necesitar actividad
        if ($usuario['rol'] === Auth::CARPINTERO) {
            $permitida = self::AVANCE_NORMAL_CARPINTERO[$faseActual] ?? null;

            return ($permitida === (int) $siguiente['id_fase']) ? $siguiente : null;
        }

        // Lijador y Pintor necesitan una actividad de su tipo en el proyecto
        $tipo = self::TIPO_POR_ROL[$usuario['rol']] ?? null;

        if ($tipo === null) {
            return null;
        }

        $asignados = (new Actividad())->usuariosAsignados((int) $proyecto['id_proyecto'], $tipo);

        return in_array((int) $usuario['id_usuario'], $asignados, true) ? $siguiente : null;
    }

    /* ===== Cambios de fase ===== */

    /**
     * Avanza el proyecto a la fase inmediata siguiente (avance normal).
     * Reglas del secado (CU-05 A2 y respuestas 1 y 2 de la Fase 2):
     *   - de "Pintado" a "Secado" se necesita un secado programado;
     *   - de "Secado" a "Listo para entrega" antes de que termine el
     *     tiempo de secado, se pide confirmar.
     *
     * Recibe: $proyecto, datos completos del proyecto;
     *         $usuario, datos de la sesión;
     *         $confirmaciones, arreglo con 'saldo' y 'secado' (true si el
     *         usuario marcó la casilla de confirmación correspondiente).
     * Devuelve: datos de la fase nueva. Lanza DomainException si no se puede.
     */
    public static function avanzar(array $proyecto, array $usuario, array $confirmaciones): array {
        $siguiente = self::siguienteFasePermitida($proyecto, $usuario);

        if ($siguiente === null) {
            throw new DomainException('No te corresponde avanzar este proyecto en su fase actual.');
        }

        $idProyecto = (int) $proyecto['id_proyecto'];
        $idSiguiente = (int) $siguiente['id_fase'];
        $secado = self::secadoVigente($idProyecto);

        if ($idSiguiente === Fase::SECADO && $secado === null) {
            throw new DomainException('Programa el tiempo de secado antes de avanzar a "Secado".');
        }

        $secadoSinTerminar = $secado !== null && strtotime($secado['fin_estimado']) > time();

        if ($idSiguiente === Fase::LISTO_PARA_ENTREGA
            && $secadoSinTerminar
            && empty($confirmaciones['secado'])) {
            throw new DomainException(
                'El secado todavía no termina (termina el ' . fechaHora($secado['fin_estimado'])
                . '). Marca la casilla para confirmar que el mueble ya está listo.'
            );
        }

        self::cambiarFase(
            $proyecto,
            $siguiente,
            $usuario,
            null,
            !empty($confirmaciones['saldo']),
            false
        );

        return $siguiente;
    }

    /* ===== Secado (HU-09) ===== */

    /**
     * Devuelve la fecha y hora (al minuto) desde la que cuenta un secado:
     * la última vez que el proyecto entró a la fase "Pintado".
     *
     * Recibe: $idProyecto.
     * Devuelve: fecha "aaaa-mm-dd hh:mm:00", o null si nunca entró a "Pintado".
     */
    public static function inicioMinimoSecado(int $idProyecto): ?string {
        $entrada = (new HistorialFase())->ultimaEntrada($idProyecto, Fase::PINTADO);

        if ($entrada === null) {
            return null;
        }

        return date('Y-m-d H:i:00', strtotime($entrada));
    }

    /**
     * Devuelve el secado que cuenta para el proyecto: el más reciente
     * programado desde la última entrada a "Pintado" (respuesta 2).
     *
     * Recibe: $idProyecto.
     * Devuelve: datos del secado, o null si no hay.
     */
    public static function secadoVigente(int $idProyecto): ?array {
        $desde = self::inicioMinimoSecado($idProyecto);

        if ($desde === null) {
            return null;
        }

        return (new Secado())->ultimoDesde($idProyecto, $desde);
    }

    /* ===== Reparaciones (RN-05) ===== */

    /**
     * Pasa la reparación a su siguiente estado: Recibido → En reparación →
     * Lista para entrega → Entregada (solo hacia adelante, respuesta 14).
     * Al entregar con saldo pendiente se pide confirmación (como RN-04).
     *
     * Recibe: $reparacion, datos completos; $usuario, datos de la sesión;
     *         $confirmaSaldo, true si confirmó entregar con saldo.
     * Devuelve: llave del estado nuevo. Lanza DomainException si no se puede.
     */
    public static function avanzarReparacion(
        array $reparacion,
        array $usuario,
        bool $confirmaSaldo
    ): string {
        $nuevoEstado = Reparacion::siguienteEstado($reparacion['estado']);

        if ($nuevoEstado === null) {
            throw new DomainException('La reparación ya fue entregada.');
        }

        $saldo = PagoService::saldo($reparacion['costo'], $reparacion['pagado']);

        if ($nuevoEstado === 'ENTREGADA' && $saldo > 0 && !$confirmaSaldo) {
            throw new DomainException(
                'La reparación tiene un saldo pendiente de ' . dinero($saldo)
                . '. Marca la casilla de confirmación para entregarla.'
            );
        }

        $entregadaEn = ($nuevoEstado === 'ENTREGADA') ? date('Y-m-d H:i:s') : null;

        (new Reparacion())->actualizarEstado(
            (int) $reparacion['id_reparacion'],
            $nuevoEstado,
            $entregadaEn
        );

        self::notificarEstadoReparacion($reparacion, $nuevoEstado, (int) $usuario['id_usuario']);

        return $nuevoEstado;
    }

    /**
     * Notifica el cambio de estado de una reparación (SRS 5.2 y
     * respuesta 14): "Lista para entrega" al Carpintero, al Contador y al
     * Cliente; "Entregada" al Contador.
     *
     * Recibe: $reparacion; $estado, estado nuevo; $idUsuarioActor.
     * Devuelve: nada.
     */
    private static function notificarEstadoReparacion(
        array $reparacion,
        string $estado,
        int $idUsuarioActor
    ): void {
        $idReparacion = (int) $reparacion['id_reparacion'];
        $descripcion = $reparacion['folio'] . ' (' . $reparacion['descripcion_mueble'] . ')';

        if ($estado === 'LISTA') {
            $mensaje = "La reparación {$descripcion} está lista para entrega";

            NotificacionService::notificar(
                NotificacionService::usuariosDeRol(Auth::CARPINTERO),
                'LISTO',
                $mensaje,
                '/reparaciones/' . $idReparacion,
                $idUsuarioActor
            );

            NotificacionService::notificar(
                NotificacionService::usuariosDeRol(Auth::CONTADOR),
                'LISTO',
                $mensaje,
                null,
                $idUsuarioActor
            );

            NotificacionService::notificar(
                [(int) $reparacion['cliente_id_usuario']],
                'LISTO',
                self::mensajeListoCliente($reparacion['descripcion_mueble']),
                '/mis-pedidos/reparacion/' . $idReparacion
            );
        }

        if ($estado === 'ENTREGADA') {
            NotificacionService::notificar(
                NotificacionService::usuariosDeRol(Auth::CONTADOR),
                'FASE',
                "La reparación {$descripcion} fue entregada",
                null,
                $idUsuarioActor
            );
        }
    }

    /**
     * Corrige la fase del proyecto: el Carpintero lo coloca en cualquier
     * fase, adelante o atrás, con motivo obligatorio (RN-03).
     *
     * Recibe: $proyecto, datos completos del proyecto; $idFaseNueva;
     *         $motivo, texto obligatorio; $usuario, datos de la sesión;
     *         $confirmaSaldo, true si confirmó entregar con saldo.
     * Devuelve: datos de la fase nueva. Lanza DomainException si no se puede.
     */
    public static function corregir(
        array $proyecto,
        int $idFaseNueva,
        string $motivo,
        array $usuario,
        bool $confirmaSaldo
    ): array {
        if ($usuario['rol'] !== Auth::CARPINTERO) {
            throw new DomainException('Solo el Carpintero puede corregir la fase.');
        }

        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new DomainException('Escribe el motivo de la corrección.');
        }

        if (mb_strlen($motivo) > 255) {
            throw new DomainException('El motivo no puede pasar de 255 caracteres.');
        }

        $faseNueva = (new Fase())->buscarPorId($idFaseNueva);

        if ($faseNueva === null) {
            throw new DomainException('Elige una fase válida.');
        }

        if ($idFaseNueva === (int) $proyecto['id_fase']) {
            throw new DomainException('El proyecto ya está en esa fase.');
        }

        self::cambiarFase($proyecto, $faseNueva, $usuario, $motivo, $confirmaSaldo, true);

        return $faseNueva;
    }

    /* ===== Funciones internas ===== */

    /**
     * Aplica el cambio de fase en una transacción: proyecto, avance,
     * actividades e historial; después genera las notificaciones.
     *
     * Recibe: $proyecto; $faseNueva; $usuario; $motivo (null en avance
     *         normal); $confirmaSaldo; $esCorreccion.
     * Devuelve: nada. Lanza DomainException si falta confirmar el saldo.
     */
    private static function cambiarFase(
        array $proyecto,
        array $faseNueva,
        array $usuario,
        ?string $motivo,
        bool $confirmaSaldo,
        bool $esCorreccion
    ): void {
        $idProyecto = (int) $proyecto['id_proyecto'];
        $idFaseAnterior = (int) $proyecto['id_fase'];
        $idFaseNueva = (int) $faseNueva['id_fase'];

        // RN-04: advertencia si se entrega con saldo pendiente
        $saldo = PagoService::saldo($proyecto['precio'], $proyecto['pagado']);

        if ($idFaseNueva === Fase::ENTREGADO && $saldo > 0 && !$confirmaSaldo) {
            throw new DomainException(
                'El proyecto tiene un saldo pendiente de ' . dinero($saldo)
                . '. Marca la casilla de confirmación para entregarlo.'
            );
        }

        // Fecha de entrega real: se guarda al entrar a "Entregado" y se borra al salir
        $entregadoEn = null;

        if ($idFaseNueva === Fase::ENTREGADO) {
            $entregadoEn = date('Y-m-d H:i:s');
        }

        $db = Database::get();

        try {
            $db->beginTransaction();

            (new Proyecto())->actualizarFase(
                $idProyecto,
                $idFaseNueva,
                (string) $faseNueva['porcentaje'],
                $entregadoEn
            );
            (new Actividad())->ajustarEstadosAFase($idProyecto, $idFaseNueva);
            (new HistorialFase())->registrar(
                $idProyecto,
                $idFaseAnterior,
                $idFaseNueva,
                (int) $usuario['id_usuario'],
                $motivo
            );

            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $error;
        }

        self::notificarCambio($proyecto, $faseNueva, (int) $usuario['id_usuario'], $esCorreccion);
    }

    /**
     * Genera las notificaciones del cambio de fase (SRS 5.2, 5.4 y
     * respuesta 13): las de la tabla 5.2 siempre, y en correcciones a
     * otras fases, a los trabajadores asignados que deben actuar ahí.
     *
     * Recibe: $proyecto; $faseNueva; $idUsuarioActor, quien hizo el cambio
     *         (no se le notifica); $esCorreccion.
     * Devuelve: nada.
     */
    private static function notificarCambio(
        array $proyecto,
        array $faseNueva,
        int $idUsuarioActor,
        bool $esCorreccion
    ): void {
        $idProyecto = (int) $proyecto['id_proyecto'];
        $idFase = (int) $faseNueva['id_fase'];
        $descripcion = $proyecto['folio'] . ' (' . $proyecto['tipo_mueble'] . ')';
        $actividades = new Actividad();
        $enlaceDetalle = '/proyectos/' . $idProyecto;

        switch ($idFase) {
            case Fase::ENSAMBLADO:
                NotificacionService::notificar(
                    $actividades->usuariosAsignados($idProyecto, 'LIJADO'),
                    'FASE',
                    "El proyecto {$descripcion} está ensamblado y listo para lijar",
                    '/mis-pendientes',
                    $idUsuarioActor
                );
                return;

            case Fase::LISTO_PARA_PINTURA:
                NotificacionService::notificar(
                    $actividades->usuariosAsignados($idProyecto, 'PINTURA'),
                    'FASE',
                    "El proyecto {$descripcion} está listo para pintura",
                    '/mis-pendientes',
                    $idUsuarioActor
                );
                return;

            case Fase::LISTO_PARA_ENTREGA:
                self::notificarListoParaEntrega($proyecto, $descripcion, $idUsuarioActor);
                return;

            case Fase::ENTREGADO:
                NotificacionService::notificar(
                    NotificacionService::usuariosDeRol(Auth::CONTADOR),
                    'FASE',
                    "El proyecto {$descripcion} fue entregado",
                    null,
                    $idUsuarioActor
                );
                return;
        }

        // Fases 1–3, 5, 7 y 8: solo se avisa cuando es una corrección
        if (!$esCorreccion) {
            return;
        }

        if ($idFase <= 3) {
            $tipo = 'ARMADO';
            $enlace = $enlaceDetalle;
        } elseif ($idFase === 5) {
            $tipo = 'LIJADO';
            $enlace = '/mis-pendientes';
        } else {
            $tipo = 'PINTURA';
            $enlace = '/mis-pendientes';
        }

        NotificacionService::notificar(
            $actividades->usuariosAsignados($idProyecto, $tipo),
            'FASE',
            "El proyecto {$descripcion} se movió a la fase \"{$faseNueva['nombre']}\"",
            $enlace,
            $idUsuarioActor
        );
    }

    /**
     * Notifica "Listo para entrega" al Carpintero, al Contador y al
     * Cliente (RF-28, RN-04).
     *
     * Recibe: $proyecto; $descripcion, "folio (tipo)"; $idUsuarioActor.
     * Devuelve: nada.
     */
    private static function notificarListoParaEntrega(
        array $proyecto,
        string $descripcion,
        int $idUsuarioActor
    ): void {
        $idProyecto = (int) $proyecto['id_proyecto'];
        $mensaje = "El proyecto {$descripcion} está listo para entrega";

        NotificacionService::notificar(
            NotificacionService::usuariosDeRol(Auth::CARPINTERO),
            'LISTO',
            $mensaje,
            '/proyectos/' . $idProyecto,
            $idUsuarioActor
        );

        NotificacionService::notificar(
            NotificacionService::usuariosDeRol(Auth::CONTADOR),
            'LISTO',
            $mensaje,
            null,
            $idUsuarioActor
        );

        NotificacionService::notificar(
            [(int) $proyecto['cliente_id_usuario']],
            'LISTO',
            self::mensajeListoCliente($proyecto['tipo_mueble']),
            '/mis-pedidos/proyecto/' . $idProyecto
        );
    }

    /**
     * Arma el mensaje para el cliente cuando su mueble está listo (RN-04).
     * En reparaciones se usa la descripción del mueble.
     *
     * Recibe: $tipoMueble, ej. "Ropero".
     * Devuelve: "Su ropero está listo, ya puede pasar a recogerlo".
     */
    public static function mensajeListoCliente(string $tipoMueble): string {
        $mueble = mb_strtolower(mb_substr($tipoMueble, 0, 1)) . mb_substr($tipoMueble, 1);

        return "Su {$mueble} está listo, ya puede pasar a recogerlo";
    }

}
