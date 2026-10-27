<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Proyecto.php
 * Acceso a la tabla proyecto: muebles nuevos a pedido,
 * con su fase actual, avance y status de pago.
 * =====================================================
 */

class Proyecto extends Model {

    /* ===== Consulta base con cliente y fase ===== */
    private const SELECT_COMPLETO = 'SELECT p.*, f.nombre AS fase_nombre, f.orden AS fase_orden, '
        . 'c.nombre_completo AS cliente_nombre, c.telefono AS cliente_telefono, '
        . 'c.correo AS cliente_correo, c.direccion AS cliente_direccion, '
        . 'c.id_usuario AS cliente_id_usuario, '
        . '(SELECT COALESCE(SUM(pc.monto), 0) FROM pago_cliente pc '
        . 'WHERE pc.id_proyecto = p.id_proyecto) AS pagado '
        . 'FROM proyecto p '
        . 'JOIN fase f ON f.id_fase = p.id_fase '
        . 'JOIN cliente c ON c.id_cliente = p.id_cliente ';

    /**
     * Registra un proyecto nuevo en la fase "Cotizado".
     *
     * Recibe: $datos, arreglo con las columnas del proyecto (folio,
     *         id_cliente, tipo_mueble, …, precio, status_pago,
     *         id_usuario_registro).
     * Devuelve: id del proyecto creado.
     */
    public function crear(array $datos): int {
        $consulta = $this->db->prepare(
            'INSERT INTO proyecto (folio, id_cliente, tipo_mueble, descripcion, '
            . 'alto_cm, ancho_cm, fondo_cm, madera, acabado, color, granulado_lija, '
            . 'ficha_color, fecha_registro, fecha_entrega, fecha_limite_pago, precio, '
            . 'id_fase, porcentaje_avance, status_pago, id_usuario_registro) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            $datos['folio'],
            $datos['id_cliente'],
            $datos['tipo_mueble'],
            $datos['descripcion'],
            $datos['alto_cm'],
            $datos['ancho_cm'],
            $datos['fondo_cm'],
            $datos['madera'],
            $datos['acabado'],
            $datos['color'],
            $datos['granulado_lija'],
            $datos['ficha_color'],
            $datos['fecha_registro'],
            $datos['fecha_entrega'],
            $datos['fecha_limite_pago'],
            $datos['precio'],
            $datos['id_fase'],
            $datos['porcentaje_avance'],
            $datos['status_pago'],
            $datos['id_usuario_registro'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Busca un proyecto activo por id, con cliente, fase y total pagado.
     *
     * Recibe: $idProyecto, id del proyecto.
     * Devuelve: datos del proyecto, o null si no existe o está dado de baja.
     */
    public function buscarPorId(int $idProyecto): ?array {
        $consulta = $this->db->prepare(
            self::SELECT_COMPLETO . 'WHERE p.id_proyecto = ? AND p.activo = 1'
        );
        $consulta->execute([$idProyecto]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Lista los proyectos activos con filtros (RF-23).
     *
     * Recibe: $filtros, arreglo con las llaves opcionales id_fase,
     *         id_cliente, status_pago, desde y hasta (fecha de entrega).
     * Devuelve: lista de proyectos ordenados por fecha de entrega.
     */
    public function listar(array $filtros): array {
        $sql = self::SELECT_COMPLETO . 'WHERE p.activo = 1';
        $valores = [];

        if (!empty($filtros['id_fase'])) {
            $sql .= ' AND p.id_fase = ?';
            $valores[] = $filtros['id_fase'];
        }

        if (!empty($filtros['id_cliente'])) {
            $sql .= ' AND p.id_cliente = ?';
            $valores[] = $filtros['id_cliente'];
        }

        if (!empty($filtros['status_pago'])) {
            $sql .= ' AND p.status_pago = ?';
            $valores[] = $filtros['status_pago'];
        }

        if (!empty($filtros['desde'])) {
            $sql .= ' AND p.fecha_entrega >= ?';
            $valores[] = $filtros['desde'];
        }

        if (!empty($filtros['hasta'])) {
            $sql .= ' AND p.fecha_entrega <= ?';
            $valores[] = $filtros['hasta'];
        }

        $sql .= ' ORDER BY p.fecha_entrega, p.folio';

        $consulta = $this->db->prepare($sql);
        $consulta->execute($valores);

        return $consulta->fetchAll();
    }

    /**
     * Lista los proyectos que aún no se entregan, para el tablero.
     *
     * Recibe: nada.
     * Devuelve: lista de proyectos ordenados por fecha de entrega.
     */
    public function listarEnProduccion(): array {
        $consulta = $this->db->prepare(
            self::SELECT_COMPLETO
            . 'WHERE p.activo = 1 AND p.id_fase <> ? '
            . 'ORDER BY p.fecha_entrega, p.folio'
        );
        $consulta->execute([Fase::ENTREGADO]);

        return $consulta->fetchAll();
    }

    /**
     * Lista los proyectos activos de un cliente (portal del cliente).
     *
     * Recibe: $idCliente, id del cliente.
     * Devuelve: lista de proyectos, los más recientes primero.
     */
    public function listarPorCliente(int $idCliente): array {
        $consulta = $this->db->prepare(
            self::SELECT_COMPLETO
            . 'WHERE p.activo = 1 AND p.id_cliente = ? '
            . 'ORDER BY p.fecha_registro DESC, p.folio DESC'
        );
        $consulta->execute([$idCliente]);

        return $consulta->fetchAll();
    }

    /**
     * Cuenta los proyectos activos que todavía no se entregan.
     *
     * Recibe: nada.
     * Devuelve: número de proyectos activos.
     */
    public function contarActivos(): int {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM proyecto WHERE activo = 1 AND id_fase <> ?'
        );
        $consulta->execute([Fase::ENTREGADO]);

        return (int) $consulta->fetchColumn();
    }

    /**
     * Cuenta los proyectos sin entregar con fecha de entrega en un rango.
     *
     * Recibe: $desde y $hasta, fechas "aaaa-mm-dd" (inclusive).
     * Devuelve: número de proyectos.
     */
    public function contarEntregasEntre(string $desde, string $hasta): int {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM proyecto '
            . 'WHERE activo = 1 AND id_fase <> ? AND fecha_entrega BETWEEN ? AND ?'
        );
        $consulta->execute([Fase::ENTREGADO, $desde, $hasta]);

        return (int) $consulta->fetchColumn();
    }

    /**
     * Cambia la fase, el avance y la fecha de entrega real de un proyecto.
     *
     * Recibe: $idProyecto; $idFase, nueva fase; $porcentaje, nuevo avance;
     *         $entregadoEn, fecha y hora de entrega o null.
     * Devuelve: nada.
     */
    public function actualizarFase(
        int $idProyecto,
        int $idFase,
        string $porcentaje,
        ?string $entregadoEn
    ): void {
        $consulta = $this->db->prepare(
            'UPDATE proyecto SET id_fase = ?, porcentaje_avance = ?, entregado_en = ? '
            . 'WHERE id_proyecto = ?'
        );
        $consulta->execute([$idFase, $porcentaje, $entregadoEn, $idProyecto]);
    }

}
