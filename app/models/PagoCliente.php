<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/PagoCliente.php
 * Acceso a la tabla pago_cliente: pagos y anticipos hechos
 * en persona. El sistema NO cobra, solo registra (RF-48).
 * =====================================================
 */

class PagoCliente extends Model {

    /* ===== Métodos de pago permitidos ===== */
    public const METODOS = [
        'EFECTIVO'      => 'Efectivo',
        'TARJETA'       => 'Tarjeta',
        'TRANSFERENCIA' => 'Transferencia',
    ];

    /* ===== Situaciones del filtro de cuentas por cobrar ===== */
    public const SITUACIONES = [
        'CON_SALDO' => 'Con saldo pendiente',
        'VENCIDOS'  => 'Saldo vencido',
        'PAGADOS'   => 'Pagados',
        'TODOS'     => 'Todos',
    ];

    /* ===== Proyectos y reparaciones con su importe y lo pagado ===== */
    private const SELECT_CUENTAS = "SELECT 'PRY' AS tipo, p.id_proyecto AS id, p.folio, "
        . 'p.tipo_mueble AS mueble, c.nombre_completo AS cliente_nombre, '
        . 'p.precio AS importe, p.fecha_limite_pago, p.status_pago, '
        . 'p.id_fase = ' . Fase::ENTREGADO . ' AS entregado, '
        . '(SELECT COALESCE(SUM(pc.monto), 0) FROM pago_cliente pc '
        . 'WHERE pc.id_proyecto = p.id_proyecto) AS pagado '
        . 'FROM proyecto p JOIN cliente c ON c.id_cliente = p.id_cliente '
        . 'WHERE p.activo = 1 '
        . 'UNION ALL '
        . "SELECT 'REP', r.id_reparacion, r.folio, r.descripcion_mueble, c.nombre_completo, "
        . "r.costo, r.fecha_limite_pago, r.status_pago, r.estado = 'ENTREGADA', "
        . '(SELECT COALESCE(SUM(pc.monto), 0) FROM pago_cliente pc '
        . 'WHERE pc.id_reparacion = r.id_reparacion) '
        . 'FROM reparacion r JOIN cliente c ON c.id_cliente = r.id_cliente '
        . 'WHERE r.activo = 1';

    /**
     * Lista proyectos y reparaciones con su saldo (cuentas por cobrar).
     * Saldo vencido: saldo > 0 y fecha límite de pago anterior a hoy (RN-16).
     *
     * Recibe: $filtros, con 'tipo' ("", "PRY" o "REP"), 'situacion'
     *         (llave de SITUACIONES) y 'busqueda' (folio o cliente).
     * Devuelve: lista con tipo, id, folio, mueble, cliente, importe,
     *           pagado, saldo y si está vencido.
     */
    public function listarCuentas(array $filtros): array {
        $sql = 'SELECT x.*, (x.importe - x.pagado) AS saldo, '
            . '((x.importe - x.pagado) > 0 AND x.fecha_limite_pago < CURDATE()) AS vencido '
            . 'FROM (' . self::SELECT_CUENTAS . ') x WHERE 1 = 1';
        $valores = [];

        if (in_array($filtros['tipo'] ?? '', ['PRY', 'REP'], true)) {
            $sql .= ' AND x.tipo = ?';
            $valores[] = $filtros['tipo'];
        }

        $situacion = $filtros['situacion'] ?? 'CON_SALDO';

        if ($situacion === 'CON_SALDO') {
            $sql .= ' AND x.importe - x.pagado > 0';
        } elseif ($situacion === 'VENCIDOS') {
            $sql .= ' AND x.importe - x.pagado > 0 AND x.fecha_limite_pago < CURDATE()';
        } elseif ($situacion === 'PAGADOS') {
            $sql .= ' AND x.importe - x.pagado <= 0';
        }

        $busqueda = trim((string) ($filtros['busqueda'] ?? ''));

        if ($busqueda !== '') {
            $sql .= ' AND (x.folio LIKE ? OR x.cliente_nombre LIKE ?)';
            $valores[] = '%' . $busqueda . '%';
            $valores[] = '%' . $busqueda . '%';
        }

        $sql .= ' ORDER BY x.fecha_limite_pago, x.folio';

        $consulta = $this->db->prepare($sql);
        $consulta->execute($valores);

        return $consulta->fetchAll();
    }

    /**
     * Lista los pagos de un proyecto o de una reparación, del más antiguo
     * al más reciente.
     *
     * Recibe: $tipo, "PRY" o "REP"; $id, id del proyecto o reparación.
     * Devuelve: lista de pagos con el nombre de quien los registró.
     */
    public function listarDe(string $tipo, int $id): array {
        $columna = ($tipo === 'REP') ? 'id_reparacion' : 'id_proyecto';

        // $columna sale de los dos valores fijos de arriba
        $consulta = $this->db->prepare(
            'SELECT pc.*, COALESCE(t.nombre_completo, u.username) AS registro_nombre '
            . 'FROM pago_cliente pc '
            . 'JOIN usuario u ON u.id_usuario = pc.id_usuario '
            . 'LEFT JOIN trabajador t ON t.id_usuario = u.id_usuario '
            . "WHERE pc.{$columna} = ? "
            . 'ORDER BY pc.fecha, pc.id_pago'
        );
        $consulta->execute([$id]);

        return $consulta->fetchAll();
    }

    /**
     * Registra un pago de un proyecto o de una reparación (RF-48).
     *
     * Recibe: $tipo, "PRY" o "REP"; $id; $monto, texto con 2 decimales;
     *         $metodo, llave de METODOS; $fecha, "aaaa-mm-dd";
     *         $referencia, o null; $idUsuario, quien lo registra.
     * Devuelve: id del pago.
     */
    public function registrar(
        string $tipo,
        int $id,
        string $monto,
        string $metodo,
        string $fecha,
        ?string $referencia,
        int $idUsuario
    ): int {
        $consulta = $this->db->prepare(
            'INSERT INTO pago_cliente '
            . '(id_proyecto, id_reparacion, monto, metodo, fecha, referencia, id_usuario) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            ($tipo === 'PRY') ? $id : null,
            ($tipo === 'REP') ? $id : null,
            $monto,
            $metodo,
            $fecha,
            $referencia,
            $idUsuario,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Registra un pago de un proyecto.
     *
     * Recibe: $idProyecto; $monto, mayor a 0; $metodo, llave de METODOS;
     *         $fecha, "aaaa-mm-dd"; $idUsuario, quién lo registró.
     * Devuelve: id del pago.
     */
    public function registrarDeProyecto(
        int $idProyecto,
        string $monto,
        string $metodo,
        string $fecha,
        int $idUsuario
    ): int {
        $consulta = $this->db->prepare(
            'INSERT INTO pago_cliente (id_proyecto, monto, metodo, fecha, id_usuario) '
            . 'VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->execute([$idProyecto, $monto, $metodo, $fecha, $idUsuario]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Registra un pago de una reparación.
     *
     * Recibe: $idReparacion; $monto, mayor a 0; $metodo, llave de METODOS;
     *         $fecha, "aaaa-mm-dd"; $idUsuario, quién lo registró.
     * Devuelve: id del pago.
     */
    public function registrarDeReparacion(
        int $idReparacion,
        string $monto,
        string $metodo,
        string $fecha,
        int $idUsuario
    ): int {
        $consulta = $this->db->prepare(
            'INSERT INTO pago_cliente (id_reparacion, monto, metodo, fecha, id_usuario) '
            . 'VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->execute([$idReparacion, $monto, $metodo, $fecha, $idUsuario]);

        return (int) $this->db->lastInsertId();
    }

}
