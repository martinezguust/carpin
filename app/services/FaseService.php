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
     *
     * Recibe: $proyecto, datos completos del proyecto;
     *         $usuario, datos de la sesión;
     *         $confirmaSaldo, true si el usuario confirmó entregar con saldo.
     * Devuelve: datos de la fase nueva. Lanza DomainException si no se puede.
     */
    public static function avanzar(array $proyecto, array $usuario, bool $confirmaSaldo): array {
        $siguiente = self::siguienteFasePermitida($proyecto, $usuario);

        if ($siguiente === null) {
            throw new DomainException('No te corresponde avanzar este proyecto en su fase actual.');
        }

        self::cambiarFase($proyecto, $siguiente, $usuario, null, $confirmaSaldo, false);

        return $siguiente;
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
     *
     * Recibe: $tipoMueble, ej. "Ropero".
     * Devuelve: "Su ropero está listo, ya puede pasar a recogerlo".
     */
    public static function mensajeListoCliente(string $tipoMueble): string {
        $mueble = mb_strtolower(mb_substr($tipoMueble, 0, 1)) . mb_substr($tipoMueble, 1);

        return "Su {$mueble} está listo, ya puede pasar a recogerlo";
    }

}
