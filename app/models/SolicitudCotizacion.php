<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/SolicitudCotizacion.php
 * Acceso a la tabla solicitud_cotizacion: solicitudes
 * enviadas desde la página pública (RF-11, RF-14).
 * =====================================================
 */

class SolicitudCotizacion extends Model {

    /* ===== Estados y su texto (RF-14) ===== */
    public const ESTADOS = [
        'NUEVA'      => 'Nueva',
        'CONTACTADO' => 'Contactado',
        'CONVERTIDA' => 'Convertida en proyecto',
        'DESCARTADA' => 'Descartada',
    ];

    /* ===== Estados desde los que se puede contactar, convertir o descartar ===== */
    public const ESTADOS_ABIERTOS = ['NUEVA', 'CONTACTADO'];

    /* ===== Consulta base con el nombre del producto ===== */
    private const SELECT_COMPLETO = 'SELECT s.*, p.nombre AS producto_nombre '
        . 'FROM solicitud_cotizacion s '
        . 'LEFT JOIN producto_catalogo p ON p.id_producto = s.id_producto ';

    /**
     * Registra una solicitud nueva con estado "NUEVA" (CU-07).
     *
     * Recibe: $datos, con nombre, telefono, correo, id_producto, medidas
     *         y descripcion (los opcionales como null).
     * Devuelve: id de la solicitud.
     */
    public function crear(array $datos): int {
        $consulta = $this->db->prepare(
            'INSERT INTO solicitud_cotizacion '
            . '(nombre, telefono, correo, id_producto, medidas, descripcion) '
            . 'VALUES (?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            $datos['nombre'],
            $datos['telefono'],
            $datos['correo'],
            $datos['id_producto'],
            $datos['medidas'],
            $datos['descripcion'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Lista las solicitudes, opcionalmente de un estado, las más recientes primero.
     *
     * Recibe: $estado, llave de ESTADOS ("" para todas).
     * Devuelve: lista de solicitudes.
     */
    public function listar(string $estado): array {
        $sql = self::SELECT_COMPLETO;
        $valores = [];

        if ($estado !== '') {
            $sql .= 'WHERE s.estado = ? ';
            $valores[] = $estado;
        }

        $sql .= 'ORDER BY s.creado_en DESC, s.id_solicitud DESC';

        $consulta = $this->db->prepare($sql);
        $consulta->execute($valores);

        return $consulta->fetchAll();
    }

    /**
     * Busca una solicitud por id.
     *
     * Recibe: $idSolicitud.
     * Devuelve: datos de la solicitud, o null si no existe.
     */
    public function buscarPorId(int $idSolicitud): ?array {
        $consulta = $this->db->prepare(self::SELECT_COMPLETO . 'WHERE s.id_solicitud = ?');
        $consulta->execute([$idSolicitud]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Cuenta las solicitudes con estado "NUEVA" (contador del menú).
     *
     * Recibe: nada.
     * Devuelve: número de solicitudes nuevas.
     */
    public function contarNuevas(): int {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM solicitud_cotizacion WHERE estado = 'NUEVA'"
        )->fetchColumn();
    }

    /**
     * Cambia el estado de una solicitud.
     *
     * Recibe: $idSolicitud; $estado, llave de ESTADOS.
     * Devuelve: nada.
     */
    public function cambiarEstado(int $idSolicitud, string $estado): void {
        $consulta = $this->db->prepare(
            'UPDATE solicitud_cotizacion SET estado = ? WHERE id_solicitud = ?'
        );
        $consulta->execute([$estado, $idSolicitud]);
    }

}
