<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/ActividadController.php
 * Asignación y reasignación de actividades por el
 * Carpintero (CU-04, HU-25, RF-18). Cada trabajador
 * asignado recibe una notificación.
 * =====================================================
 */

class ActividadController extends Controller {

    /**
     * Asigna una actividad a uno o varios trabajadores del rol que le
     * corresponde, con su fecha compromiso (CU-04).
     *
     * Recibe: $id, id del proyecto (segmento de la ruta);
     *         $_POST: tipo, trabajadores[] y fecha_compromiso.
     * Devuelve: nada (redirige a la pestaña Actividades).
     */
    public function asignar(string $id): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $proyecto = $this->buscarProyecto($this->idDeRuta($id));
        $idProyecto = (int) $proyecto['id_proyecto'];
        $volver = '/proyectos/' . $idProyecto . '#actividades';

        $tipo = $this->textoPost('tipo');
        $fechaCompromiso = $this->textoPost('fecha_compromiso');
        $seleccionados = $_POST['trabajadores'] ?? [];

        /* --- Validaciones --- */
        $error = $this->validarAsignacion($proyecto, $tipo, $fechaCompromiso);

        if ($error === null && (!is_array($seleccionados) || $seleccionados === [])) {
            $error = 'Elige al menos un trabajador.';
        }

        if ($error !== null) {
            Flash::agregar('error', $error);
            $this->redirigir($volver);
        }

        // Solo trabajadores activos del rol que corresponde al tipo (RF-18)
        $disponibles = [];

        foreach ((new Trabajador())->activosPorRol(Actividad::ROL_POR_TIPO[$tipo]) as $trabajador) {
            $disponibles[(int) $trabajador['id_trabajador']] = $trabajador['nombre_completo'];
        }

        $idsTrabajador = [];

        foreach ($seleccionados as $valor) {
            if (!is_string($valor) || !ctype_digit($valor) || !isset($disponibles[(int) $valor])) {
                Flash::agregar('error', 'Uno de los trabajadores elegidos no es válido.');
                $this->redirigir($volver);
            }

            $idsTrabajador[] = (int) $valor;
        }

        /* --- Registro --- */
        $actividades = new Actividad();
        $repetidos = [];
        $asignados = [];

        // Si el proyecto ya pasó la fase de término, la actividad nace terminada (SRS 5.4)
        $estado = ((int) $proyecto['id_fase'] >= Actividad::FASE_DE_TERMINO[$tipo])
            ? 'TERMINADA'
            : 'PENDIENTE';

        $db = Database::get();

        try {
            $db->beginTransaction();

            foreach (array_unique($idsTrabajador) as $idTrabajador) {
                if ($actividades->existe($idProyecto, $idTrabajador, $tipo)) {
                    $repetidos[] = $disponibles[$idTrabajador];
                    continue;
                }

                $actividades->crear($idProyecto, $idTrabajador, $tipo, $fechaCompromiso, $estado);
                $asignados[] = $idTrabajador;
            }

            $db->commit();
        } catch (Throwable $excepcion) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $excepcion;
        }

        /* --- Notificaciones y mensajes --- */
        foreach ($asignados as $idTrabajador) {
            $this->notificarAsignacion($proyecto, $idTrabajador, $tipo);
        }

        if ($asignados !== []) {
            Flash::agregar('exito', 'Actividad de ' . etiquetaActividad($tipo) . ' asignada.');
        }

        if ($repetidos !== []) {
            Flash::agregar(
                'aviso',
                'Ya tenían esta actividad en el proyecto: ' . implode(', ', $repetidos) . '.'
            );
        }

        $this->redirigir($volver);
    }

    /**
     * Cambia el trabajador de una actividad pendiente y notifica al nuevo
     * (CU-04, A2).
     *
     * Recibe: $id, id de la actividad (segmento de la ruta);
     *         $_POST['id_trabajador'], nuevo trabajador.
     * Devuelve: nada (redirige a la pestaña Actividades).
     */
    public function reasignar(string $id): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $actividades = new Actividad();
        $actividad = $actividades->buscarPorId($this->idDeRuta($id));

        if ($actividad === null) {
            View::error(404);
        }

        $proyecto = $this->buscarProyecto((int) $actividad['id_proyecto']);
        $volver = '/proyectos/' . $proyecto['id_proyecto'] . '#actividades';
        $tipo = $actividad['tipo'];
        $idNuevo = ctype_digit($this->textoPost('id_trabajador'))
            ? (int) $this->textoPost('id_trabajador')
            : 0;

        /* --- Validaciones --- */
        $nuevo = (new Trabajador())->buscarPorId($idNuevo);
        $rolValido = $nuevo !== null
            && $nuevo['rol'] === Actividad::ROL_POR_TIPO[$tipo]
            && (int) $nuevo['activo'] === 1
            && (int) $nuevo['cuenta_activa'] === 1;

        $error = null;

        if ($actividad['estado'] !== 'PENDIENTE') {
            $error = 'Solo se pueden reasignar actividades pendientes.';
        } elseif ((int) $proyecto['id_fase'] === Fase::ENTREGADO) {
            $error = 'El proyecto ya fue entregado.';
        } elseif (!$rolValido) {
            $error = 'Elige un trabajador activo con el rol que corresponde.';
        } elseif ($idNuevo === (int) $actividad['id_trabajador']) {
            $error = 'La actividad ya está asignada a ese trabajador.';
        } elseif ($actividades->existe((int) $proyecto['id_proyecto'], $idNuevo, $tipo)) {
            $error = $nuevo['nombre_completo'] . ' ya tiene esta actividad en el proyecto.';
        }

        if ($error !== null) {
            Flash::agregar('error', $error);
            $this->redirigir($volver);
        }

        $actividades->reasignar((int) $actividad['id_actividad'], $idNuevo);
        $this->notificarAsignacion($proyecto, $idNuevo, $tipo);

        Flash::agregar('exito', 'Actividad reasignada a ' . $nuevo['nombre_completo'] . '.');
        $this->redirigir($volver);
    }

    /* ===== Funciones internas ===== */

    /**
     * Valida el tipo, la fecha compromiso y que el proyecto siga abierto.
     *
     * Recibe: $proyecto; $tipo; $fechaCompromiso, texto "aaaa-mm-dd".
     * Devuelve: mensaje de error, o null si todo es válido.
     */
    private function validarAsignacion(
        array $proyecto,
        string $tipo,
        string $fechaCompromiso
    ): ?string {
        if ((int) $proyecto['id_fase'] === Fase::ENTREGADO) {
            return 'El proyecto ya fue entregado.';
        }

        if (!isset(Actividad::ROL_POR_TIPO[$tipo])) {
            return 'Elige el tipo de actividad.';
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $fechaCompromiso);

        if ($fecha === false || $fecha->format('Y-m-d') !== $fechaCompromiso) {
            return 'Elige la fecha compromiso.';
        }

        if ($fechaCompromiso < date('Y-m-d')) {
            return 'La fecha compromiso no puede ser anterior a hoy.';
        }

        return null;
    }

    /**
     * Notifica al trabajador que se le asignó una actividad (SRS 5.2).
     * No se notifica a quien hace la asignación.
     *
     * Recibe: $proyecto; $idTrabajador; $tipo.
     * Devuelve: nada.
     */
    private function notificarAsignacion(array $proyecto, int $idTrabajador, string $tipo): void {
        $trabajador = (new Trabajador())->buscarPorId($idTrabajador);

        if ($trabajador === null) {
            return;
        }

        // El Carpintero va al detalle del proyecto; Lijador y Pintor a sus pendientes
        if ($tipo === 'ARMADO') {
            $enlace = '/proyectos/' . $proyecto['id_proyecto'];
        } else {
            $enlace = '/mis-pendientes';
        }

        $mensaje = 'Se te asignó la actividad de ' . etiquetaActividad($tipo)
            . ' del proyecto ' . $proyecto['folio'] . ' (' . $proyecto['tipo_mueble'] . ')';

        NotificacionService::notificar(
            [(int) $trabajador['id_usuario']],
            'ACTIVIDAD',
            $mensaje,
            $enlace,
            Auth::id()
        );
    }

    /**
     * Busca un proyecto activo o responde 404.
     *
     * Recibe: $idProyecto.
     * Devuelve: datos completos del proyecto.
     */
    private function buscarProyecto(int $idProyecto): array {
        $proyecto = (new Proyecto())->buscarPorId($idProyecto);

        if ($proyecto === null) {
            View::error(404);
        }

        return $proyecto;
    }

}
