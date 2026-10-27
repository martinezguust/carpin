<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Actividad.php
 * Acceso a la tabla actividad: tareas de Armado, Lijado y
 * Pintura asignadas por el Carpintero a cada trabajador.
 * =====================================================
 */

class Actividad extends Model {

    /* ===== Tipo de actividad → rol que la realiza (RF-18) ===== */
    public const ROL_POR_TIPO = [
        'ARMADO'  => Auth::CARPINTERO,
        'LIJADO'  => Auth::LIJADOR,
        'PINTURA' => Auth::PINTOR,
    ];

    /* ===== Tipo de actividad → fase en la que se termina (SRS 5.4) ===== */
    public const FASE_DE_TERMINO = [
        'ARMADO'  => Fase::ENSAMBLADO,
        'LIJADO'  => Fase::LISTO_PARA_PINTURA,
        'PINTURA' => Fase::LISTO_PARA_ENTREGA,
    ];

    /**
     * Asigna una actividad a un trabajador.
     *
     * Recibe: $idProyecto; $idTrabajador; $tipo, ARMADO/LIJADO/PINTURA;
     *         $fechaCompromiso, "aaaa-mm-dd"; $estado, PENDIENTE o TERMINADA.
     * Devuelve: id de la actividad creada.
     */
    public function crear(
        int $idProyecto,
        int $idTrabajador,
        string $tipo,
        string $fechaCompromiso,
        string $estado
    ): int {
        $consulta = $this->db->prepare(
            'INSERT INTO actividad (id_proyecto, id_trabajador, tipo, fecha_compromiso, estado, '
            . 'terminada_en) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $terminadaEn = ($estado === 'TERMINADA') ? date('Y-m-d H:i:s') : null;
        $consulta->execute([
            $idProyecto,
            $idTrabajador,
            $tipo,
            $fechaCompromiso,
            $estado,
            $terminadaEn,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Indica si un trabajador ya tiene una actividad de ese tipo en el proyecto.
     *
     * Recibe: $idProyecto; $idTrabajador; $tipo.
     * Devuelve: true si ya existe (uq_actividad).
     */
    public function existe(int $idProyecto, int $idTrabajador, string $tipo): bool {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM actividad '
            . 'WHERE id_proyecto = ? AND id_trabajador = ? AND tipo = ?'
        );
        $consulta->execute([$idProyecto, $idTrabajador, $tipo]);

        return (int) $consulta->fetchColumn() > 0;
    }

    /**
     * Busca una actividad por id, con el rol del trabajador asignado.
     *
     * Recibe: $idActividad.
     * Devuelve: datos de la actividad, o null si no existe.
     */
    public function buscarPorId(int $idActividad): ?array {
        $consulta = $this->db->prepare(
            'SELECT a.*, t.nombre_completo AS trabajador_nombre '
            . 'FROM actividad a JOIN trabajador t ON t.id_trabajador = a.id_trabajador '
            . 'WHERE a.id_actividad = ?'
        );
        $consulta->execute([$idActividad]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Lista las actividades de un proyecto con el nombre del trabajador.
     *
     * Recibe: $idProyecto.
     * Devuelve: lista ordenada por tipo (Armado, Lijado, Pintura) y nombre.
     */
    public function listarPorProyecto(int $idProyecto): array {
        $consulta = $this->db->prepare(
            'SELECT a.*, t.nombre_completo AS trabajador_nombre '
            . 'FROM actividad a JOIN trabajador t ON t.id_trabajador = a.id_trabajador '
            . 'WHERE a.id_proyecto = ? '
            . 'ORDER BY a.tipo, t.nombre_completo'
        );
        $consulta->execute([$idProyecto]);

        return $consulta->fetchAll();
    }

    /**
     * Lista las actividades pendientes de un trabajador en proyectos que no
     * se han entregado, ordenadas por fecha de entrega (RF-19).
     *
     * Recibe: $idTrabajador.
     * Devuelve: lista con los datos de la actividad y de su proyecto.
     */
    public function pendientesDeTrabajador(int $idTrabajador): array {
        $consulta = $this->db->prepare(
            'SELECT a.id_actividad, a.tipo, a.fecha_compromiso, '
            . 'p.id_proyecto, p.folio, p.tipo_mueble, p.madera, p.granulado_lija, '
            . 'p.color, p.acabado, p.ficha_color, '
            . 'p.fecha_entrega, p.id_fase, f.nombre AS fase_nombre '
            . 'FROM actividad a '
            . 'JOIN proyecto p ON p.id_proyecto = a.id_proyecto '
            . 'JOIN fase f ON f.id_fase = p.id_fase '
            . 'WHERE a.id_trabajador = ? AND a.estado = \'PENDIENTE\' '
            . 'AND p.activo = 1 AND p.id_fase <> ? '
            . 'ORDER BY p.fecha_entrega, a.fecha_compromiso, p.folio'
        );
        $consulta->execute([$idTrabajador, Fase::ENTREGADO]);

        return $consulta->fetchAll();
    }

    /**
     * Cambia el trabajador de una actividad pendiente (CU-04, A2).
     *
     * Recibe: $idActividad; $idTrabajador, nuevo trabajador.
     * Devuelve: nada.
     */
    public function reasignar(int $idActividad, int $idTrabajador): void {
        $consulta = $this->db->prepare(
            'UPDATE actividad SET id_trabajador = ?, asignada_en = NOW() WHERE id_actividad = ?'
        );
        $consulta->execute([$idTrabajador, $idActividad]);
    }

    /**
     * Ajusta el estado de todas las actividades del proyecto a su nueva
     * fase (SRS 5.4 y respuesta 27): la actividad queda TERMINADA si el
     * proyecto llegó o pasó su fase de término, y PENDIENTE si quedó antes.
     *
     * Recibe: $idProyecto; $idFase, fase nueva del proyecto.
     * Devuelve: nada.
     */
    public function ajustarEstadosAFase(int $idProyecto, int $idFase): void {
        $terminar = $this->db->prepare(
            'UPDATE actividad SET estado = \'TERMINADA\', terminada_en = NOW() '
            . 'WHERE id_proyecto = ? AND tipo = ? AND estado = \'PENDIENTE\''
        );
        $reabrir = $this->db->prepare(
            'UPDATE actividad SET estado = \'PENDIENTE\', terminada_en = NULL '
            . 'WHERE id_proyecto = ? AND tipo = ? AND estado = \'TERMINADA\''
        );

        foreach (self::FASE_DE_TERMINO as $tipo => $faseTermino) {
            if ($idFase >= $faseTermino) {
                $terminar->execute([$idProyecto, $tipo]);
            } else {
                $reabrir->execute([$idProyecto, $tipo]);
            }
        }
    }

    /**
     * Devuelve las cuentas activas de los trabajadores asignados a un tipo
     * de actividad en un proyecto (destinatarios de notificaciones).
     *
     * Recibe: $idProyecto; $tipo, ARMADO/LIJADO/PINTURA.
     * Devuelve: lista de id_usuario.
     */
    public function usuariosAsignados(int $idProyecto, string $tipo): array {
        $consulta = $this->db->prepare(
            'SELECT DISTINCT t.id_usuario '
            . 'FROM actividad a '
            . 'JOIN trabajador t ON t.id_trabajador = a.id_trabajador '
            . 'JOIN usuario u ON u.id_usuario = t.id_usuario '
            . 'WHERE a.id_proyecto = ? AND a.tipo = ? AND u.activo = 1 AND t.activo = 1'
        );
        $consulta->execute([$idProyecto, $tipo]);

        return array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
    }

}
