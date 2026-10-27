<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/HistorialFase.php
 * Acceso a la tabla historial_fase: bitácora de cada
 * cambio de fase de un proyecto (RF-22).
 * =====================================================
 */

class HistorialFase extends Model {

    /**
     * Registra un cambio de fase.
     *
     * Recibe: $idProyecto; $idFaseAnterior, null al registrar el proyecto;
     *         $idFaseNueva; $idUsuario, quién hizo el cambio;
     *         $motivo, obligatorio en correcciones (null en avance normal).
     * Devuelve: nada.
     */
    public function registrar(
        int $idProyecto,
        ?int $idFaseAnterior,
        int $idFaseNueva,
        int $idUsuario,
        ?string $motivo
    ): void {
        $consulta = $this->db->prepare(
            'INSERT INTO historial_fase (id_proyecto, id_fase_anterior, id_fase_nueva, '
            . 'id_usuario, motivo) VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->execute([$idProyecto, $idFaseAnterior, $idFaseNueva, $idUsuario, $motivo]);
    }

    /**
     * Lista los cambios de fase de un proyecto, del más reciente al más antiguo.
     *
     * Recibe: $idProyecto.
     * Devuelve: lista con nombres de las fases y de quien hizo el cambio.
     */
    public function listarPorProyecto(int $idProyecto): array {
        $consulta = $this->db->prepare(
            'SELECT h.fecha, h.motivo, fa.nombre AS fase_anterior, fn.nombre AS fase_nueva, '
            . 'COALESCE(t.nombre_completo, u.username) AS usuario_nombre '
            . 'FROM historial_fase h '
            . 'LEFT JOIN fase fa ON fa.id_fase = h.id_fase_anterior '
            . 'JOIN fase fn ON fn.id_fase = h.id_fase_nueva '
            . 'JOIN usuario u ON u.id_usuario = h.id_usuario '
            . 'LEFT JOIN trabajador t ON t.id_usuario = u.id_usuario '
            . 'WHERE h.id_proyecto = ? '
            . 'ORDER BY h.fecha DESC, h.id_historial DESC'
        );
        $consulta->execute([$idProyecto]);

        return $consulta->fetchAll();
    }

}
