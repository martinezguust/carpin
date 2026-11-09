<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/ReporteDanio.php
 * Acceso a la tabla reporte_danio: material o herramienta
 * dañada (HU-03, RF-40).
 * =====================================================
 */

class ReporteDanio extends Model {

    /* ===== Estados en orden (solo avanzan, respuesta 9) ===== */
    public const ESTADOS = [
        'REPORTADO'   => 'Reportado',
        'EN_ATENCION' => 'En atención',
        'RESUELTO'    => 'Resuelto',
    ];

    /* ===== Tipos y acciones ===== */
    public const TIPOS = [
        'MATERIAL'    => 'Material',
        'HERRAMIENTA' => 'Herramienta',
    ];

    public const ACCIONES = [
        'REPARAR'    => 'Reparar',
        'REEMPLAZAR' => 'Reemplazar',
    ];

    /* ===== Consulta base con el nombre de quien reporta ===== */
    private const SELECT_COMPLETO = 'SELECT d.*, '
        . 'COALESCE(t.nombre_completo, u.username) AS reporta_nombre '
        . 'FROM reporte_danio d '
        . 'JOIN usuario u ON u.id_usuario = d.id_usuario_reporta '
        . 'LEFT JOIN trabajador t ON t.id_usuario = u.id_usuario ';

    /**
     * Devuelve el estado que sigue a otro.
     *
     * Recibe: $estado, llave de ESTADOS.
     * Devuelve: el siguiente estado, o null si ya está resuelto.
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
     * Registra un reporte nuevo con estado "Reportado".
     *
     * Recibe: $datos, con elemento, tipo, descripcion, accion, foto_ruta
     *         (o null) e id_usuario_reporta.
     * Devuelve: id del reporte.
     */
    public function crear(array $datos): int {
        $consulta = $this->db->prepare(
            'INSERT INTO reporte_danio '
            . '(elemento, tipo, descripcion, accion, foto_ruta, id_usuario_reporta) '
            . 'VALUES (?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            $datos['elemento'],
            $datos['tipo'],
            $datos['descripcion'],
            $datos['accion'],
            $datos['foto_ruta'],
            $datos['id_usuario_reporta'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Lista los reportes, los más recientes primero.
     *
     * Recibe: $estado, filtro ("" para todos);
     *         $idUsuario, solo los de esa persona (null para todos).
     * Devuelve: lista de reportes.
     */
    public function listar(string $estado, ?int $idUsuario): array {
        $sql = self::SELECT_COMPLETO . 'WHERE 1 = 1';
        $valores = [];

        if ($estado !== '') {
            $sql .= ' AND d.estado = ?';
            $valores[] = $estado;
        }

        if ($idUsuario !== null) {
            $sql .= ' AND d.id_usuario_reporta = ?';
            $valores[] = $idUsuario;
        }

        $sql .= ' ORDER BY d.fecha DESC, d.id_reporte DESC';

        $consulta = $this->db->prepare($sql);
        $consulta->execute($valores);

        return $consulta->fetchAll();
    }

    /**
     * Lista los reportes no resueltos con acción "Reemplazar" (RF-41,
     * respuesta 7).
     *
     * Recibe: nada.
     * Devuelve: lista de reportes.
     */
    public function listarPorReemplazar(): array {
        return $this->db->query(
            self::SELECT_COMPLETO
            . "WHERE d.estado <> 'RESUELTO' AND d.accion = 'REEMPLAZAR' "
            . 'ORDER BY d.fecha'
        )->fetchAll();
    }

    /**
     * Busca un reporte por id.
     *
     * Recibe: $idReporte.
     * Devuelve: datos del reporte, o null si no existe.
     */
    public function buscarPorId(int $idReporte): ?array {
        $consulta = $this->db->prepare(self::SELECT_COMPLETO . 'WHERE d.id_reporte = ?');
        $consulta->execute([$idReporte]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Cambia el estado; al resolverse guarda la fecha y hora.
     *
     * Recibe: $idReporte; $estado, llave de ESTADOS.
     * Devuelve: nada.
     */
    public function cambiarEstado(int $idReporte, string $estado): void {
        $resueltoEn = ($estado === 'RESUELTO') ? date('Y-m-d H:i:s') : null;

        $consulta = $this->db->prepare(
            'UPDATE reporte_danio SET estado = ?, resuelto_en = ? WHERE id_reporte = ?'
        );
        $consulta->execute([$estado, $resueltoEn, $idReporte]);
    }

}
