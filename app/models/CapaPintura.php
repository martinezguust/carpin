<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/CapaPintura.php
 * Acceso a la tabla capa_pintura: capas aplicadas por el
 * Pintor para el control de calidad (HU-08, RF-26).
 * =====================================================
 */

class CapaPintura extends Model {

    /**
     * Registra una capa con el siguiente número consecutivo del proyecto.
     *
     * Recibe: $idProyecto; $producto, producto usado; $observaciones, o null;
     *         $idTrabajador, pintor que la aplicó.
     * Devuelve: número de la capa registrada.
     */
    public function registrar(
        int $idProyecto,
        string $producto,
        ?string $observaciones,
        int $idTrabajador
    ): int {
        $numero = $this->contar($idProyecto) + 1;

        $consulta = $this->db->prepare(
            'INSERT INTO capa_pintura '
            . '(id_proyecto, numero_capa, producto, observaciones, id_trabajador) '
            . 'VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->execute([$idProyecto, $numero, $producto, $observaciones, $idTrabajador]);

        return $numero;
    }

    /**
     * Cuenta las capas aplicadas a un proyecto.
     *
     * Recibe: $idProyecto.
     * Devuelve: total de capas.
     */
    public function contar(int $idProyecto): int {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM capa_pintura WHERE id_proyecto = ?'
        );
        $consulta->execute([$idProyecto]);

        return (int) $consulta->fetchColumn();
    }

    /**
     * Lista las capas de un proyecto con el nombre del pintor.
     *
     * Recibe: $idProyecto.
     * Devuelve: lista ordenada por número de capa.
     */
    public function listarPorProyecto(int $idProyecto): array {
        $consulta = $this->db->prepare(
            'SELECT c.*, t.nombre_completo AS pintor_nombre '
            . 'FROM capa_pintura c JOIN trabajador t ON t.id_trabajador = c.id_trabajador '
            . 'WHERE c.id_proyecto = ? ORDER BY c.numero_capa'
        );
        $consulta->execute([$idProyecto]);

        return $consulta->fetchAll();
    }

}
