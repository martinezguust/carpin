<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Reparacion.php
 * Acceso a la tabla reparacion: muebles de clientes que la
 * carpintería repara (HU-26, RF-33 a RF-36, RN-05).
 * =====================================================
 */

class Reparacion extends Model {

    /* ===== Estados en orden, con su texto y su avance (RN-05) ===== */
    public const ESTADOS = [
        'RECIBIDO'      => ['nombre' => 'Recibido', 'avance' => '25.00'],
        'EN_REPARACION' => ['nombre' => 'En reparación', 'avance' => '50.00'],
        'LISTA'         => ['nombre' => 'Lista para entrega', 'avance' => '100.00'],
        'ENTREGADA'     => ['nombre' => 'Entregada', 'avance' => '100.00'],
    ];

    /* ===== Tamaños del mueble ===== */
    public const TAMANOS = [
        'CHICO'   => 'Chico',
        'MEDIANO' => 'Mediano',
        'GRANDE'  => 'Grande',
    ];

    /* ===== Consulta base con cliente y total pagado ===== */
    private const SELECT_COMPLETO = 'SELECT r.*, '
        . 'c.nombre_completo AS cliente_nombre, c.telefono AS cliente_telefono, '
        . 'c.correo AS cliente_correo, c.direccion AS cliente_direccion, '
        . 'c.id_usuario AS cliente_id_usuario, '
        . '(SELECT COALESCE(SUM(pc.monto), 0) FROM pago_cliente pc '
        . 'WHERE pc.id_reparacion = r.id_reparacion) AS pagado '
        . 'FROM reparacion r '
        . 'JOIN cliente c ON c.id_cliente = r.id_cliente ';

    /**
     * Devuelve el estado que sigue a otro (RN-05).
     *
     * Recibe: $estado, llave de ESTADOS.
     * Devuelve: llave del siguiente estado, o null si ya está entregada.
     */
    public static function siguienteEstado(string $estado): ?string {
        $llaves = array_keys(self::ESTADOS);
        $posicion = array_search($estado, $llaves, true);

        if ($posicion === false || !isset($llaves[$posicion + 1])) {
            return null;
        }

        return $llaves[$posicion + 1];
    }

    /**
     * Registra una reparación nueva con estado "RECIBIDO" y 25 % de avance.
     *
     * Recibe: $datos, con folio, id_cliente, descripcion_mueble, tamano,
     *         tipo_reparacion, descripcion_danio, costo, fecha_recepcion,
     *         fecha_estimada, fecha_limite_pago, status_pago e
     *         id_usuario_registro.
     * Devuelve: id de la reparación.
     */
    public function crear(array $datos): int {
        $consulta = $this->db->prepare(
            'INSERT INTO reparacion (folio, id_cliente, descripcion_mueble, tamano, '
            . 'tipo_reparacion, descripcion_danio, costo, estado, porcentaje_avance, '
            . 'fecha_recepcion, fecha_estimada, fecha_limite_pago, status_pago, '
            . 'id_usuario_registro) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            $datos['folio'],
            $datos['id_cliente'],
            $datos['descripcion_mueble'],
            $datos['tamano'],
            $datos['tipo_reparacion'],
            $datos['descripcion_danio'],
            $datos['costo'],
            'RECIBIDO',
            self::ESTADOS['RECIBIDO']['avance'],
            $datos['fecha_recepcion'],
            $datos['fecha_estimada'],
            $datos['fecha_limite_pago'],
            $datos['status_pago'],
            $datos['id_usuario_registro'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Busca una reparación activa por id, con cliente y total pagado.
     *
     * Recibe: $idReparacion.
     * Devuelve: datos de la reparación, o null si no existe.
     */
    public function buscarPorId(int $idReparacion): ?array {
        $consulta = $this->db->prepare(
            self::SELECT_COMPLETO . 'WHERE r.id_reparacion = ? AND r.activo = 1'
        );
        $consulta->execute([$idReparacion]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Lista las reparaciones activas, opcionalmente de un estado.
     *
     * Recibe: $estado, llave de ESTADOS ("" para todas).
     * Devuelve: lista ordenada por fecha estimada.
     */
    public function listar(string $estado): array {
        $sql = self::SELECT_COMPLETO . 'WHERE r.activo = 1';
        $valores = [];

        if ($estado !== '') {
            $sql .= ' AND r.estado = ?';
            $valores[] = $estado;
        }

        $sql .= ' ORDER BY r.fecha_estimada, r.folio';

        $consulta = $this->db->prepare($sql);
        $consulta->execute($valores);

        return $consulta->fetchAll();
    }

    /**
     * Lista las reparaciones activas de un cliente (portal del cliente).
     *
     * Recibe: $idCliente.
     * Devuelve: lista, las más recientes primero.
     */
    public function listarPorCliente(int $idCliente): array {
        $consulta = $this->db->prepare(
            self::SELECT_COMPLETO
            . 'WHERE r.activo = 1 AND r.id_cliente = ? '
            . 'ORDER BY r.fecha_recepcion DESC, r.folio DESC'
        );
        $consulta->execute([$idCliente]);

        return $consulta->fetchAll();
    }

    /**
     * Cambia el estado, el avance y la fecha de entrega real.
     *
     * Recibe: $idReparacion; $estado, llave de ESTADOS;
     *         $entregadaEn, fecha y hora de entrega o null.
     * Devuelve: nada.
     */
    public function actualizarEstado(
        int $idReparacion,
        string $estado,
        ?string $entregadaEn
    ): void {
        $consulta = $this->db->prepare(
            'UPDATE reparacion SET estado = ?, porcentaje_avance = ?, entregada_en = ? '
            . 'WHERE id_reparacion = ?'
        );
        $consulta->execute([
            $estado,
            self::ESTADOS[$estado]['avance'],
            $entregadaEn,
            $idReparacion,
        ]);
    }

}
