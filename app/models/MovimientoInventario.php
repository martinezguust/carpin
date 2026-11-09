<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/MovimientoInventario.php
 * Acceso a la tabla movimiento_inventario: entradas
 * (compras) y salidas (consumo en un proyecto o una
 * reparación) de material (RF-38).
 * =====================================================
 */

class MovimientoInventario extends Model {

    /**
     * Registra un movimiento.
     *
     * Recibe: $datos, con id_material, tipo, cantidad, costo_unitario,
     *         id_proyecto, id_reparacion, id_usuario y nota.
     * Devuelve: id del movimiento.
     */
    public function crear(array $datos): int {
        $consulta = $this->db->prepare(
            'INSERT INTO movimiento_inventario (id_material, tipo, cantidad, costo_unitario, '
            . 'id_proyecto, id_reparacion, id_usuario, nota) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            $datos['id_material'],
            $datos['tipo'],
            $datos['cantidad'],
            $datos['costo_unitario'],
            $datos['id_proyecto'],
            $datos['id_reparacion'],
            $datos['id_usuario'],
            $datos['nota'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Lista los movimientos de un material, del más reciente al más antiguo.
     *
     * Recibe: $idMaterial.
     * Devuelve: lista con folio del destino y nombre de quien lo registró.
     */
    public function listarPorMaterial(int $idMaterial): array {
        $consulta = $this->db->prepare(
            'SELECT m.*, p.folio AS folio_proyecto, r.folio AS folio_reparacion, '
            . 'COALESCE(t.nombre_completo, u.username) AS usuario_nombre '
            . 'FROM movimiento_inventario m '
            . 'LEFT JOIN proyecto p ON p.id_proyecto = m.id_proyecto '
            . 'LEFT JOIN reparacion r ON r.id_reparacion = m.id_reparacion '
            . 'JOIN usuario u ON u.id_usuario = m.id_usuario '
            . 'LEFT JOIN trabajador t ON t.id_usuario = u.id_usuario '
            . 'WHERE m.id_material = ? '
            . 'ORDER BY m.fecha DESC, m.id_movimiento DESC'
        );
        $consulta->execute([$idMaterial]);

        return $consulta->fetchAll();
    }

    /**
     * Lista las salidas de material de un proyecto (pestaña "Materiales").
     *
     * Recibe: $idProyecto.
     * Devuelve: lista con nombre y unidad del material.
     */
    public function listarSalidasDeProyecto(int $idProyecto): array {
        $consulta = $this->db->prepare(
            'SELECT m.*, mt.nombre AS material_nombre, mt.unidad '
            . 'FROM movimiento_inventario m '
            . 'JOIN material mt ON mt.id_material = m.id_material '
            . "WHERE m.id_proyecto = ? AND m.tipo = 'SALIDA' "
            . 'ORDER BY m.fecha DESC, m.id_movimiento DESC'
        );
        $consulta->execute([$idProyecto]);

        return $consulta->fetchAll();
    }

}
