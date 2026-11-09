<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Asistencia.php
 * Acceso a la tabla asistencia: tarjetas de entrada y
 * salida que captura el Contador (HU-04, RF-42, RN-09).
 * =====================================================
 */

class Asistencia extends Model {

    /**
     * Devuelve las tarjetas de una fecha, indexadas por trabajador.
     *
     * Recibe: $fecha, "aaaa-mm-dd".
     * Devuelve: arreglo [id_trabajador => tarjeta].
     */
    public function listarPorFecha(string $fecha): array {
        $consulta = $this->db->prepare('SELECT * FROM asistencia WHERE fecha = ?');
        $consulta->execute([$fecha]);

        $tarjetas = [];

        foreach ($consulta->fetchAll() as $tarjeta) {
            $tarjetas[(int) $tarjeta['id_trabajador']] = $tarjeta;
        }

        return $tarjetas;
    }

    /**
     * Guarda la tarjeta de un trabajador en una fecha. Si ya existe
     * (trabajador + fecha), la actualiza (CU-09, A2).
     *
     * Recibe: $idTrabajador; $fecha; $entrada y $salida, "hh:mm";
     *         $horas, texto con 2 decimales; $idUsuario, quien captura.
     * Devuelve: nada.
     */
    public function guardar(
        int $idTrabajador,
        string $fecha,
        string $entrada,
        string $salida,
        string $horas,
        int $idUsuario
    ): void {
        $consulta = $this->db->prepare(
            'INSERT INTO asistencia '
            . '(id_trabajador, fecha, hora_entrada, hora_salida, horas, id_usuario_captura) '
            . 'VALUES (?, ?, ?, ?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE hora_entrada = VALUES(hora_entrada), '
            . 'hora_salida = VALUES(hora_salida), horas = VALUES(horas), '
            . 'id_usuario_captura = VALUES(id_usuario_captura)'
        );
        $consulta->execute([$idTrabajador, $fecha, $entrada, $salida, $horas, $idUsuario]);
    }

    /**
     * Busca una tarjeta por id.
     *
     * Recibe: $idAsistencia.
     * Devuelve: datos de la tarjeta, o null si no existe.
     */
    public function buscarPorId(int $idAsistencia): ?array {
        $consulta = $this->db->prepare('SELECT * FROM asistencia WHERE id_asistencia = ?');
        $consulta->execute([$idAsistencia]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Elimina una tarjeta capturada por error (respuesta 12).
     *
     * Recibe: $idAsistencia.
     * Devuelve: nada.
     */
    public function eliminar(int $idAsistencia): void {
        $consulta = $this->db->prepare('DELETE FROM asistencia WHERE id_asistencia = ?');
        $consulta->execute([$idAsistencia]);
    }

    /**
     * Total de horas y días con asistencia por trabajador en un periodo
     * (RF-47). Incluye a los trabajadores sin tarjetas (con 0).
     *
     * Recibe: $desde y $hasta, fechas "aaaa-mm-dd" (inclusive).
     * Devuelve: lista con nombre, rol, forma de pago, días y horas.
     */
    public function resumenPorPeriodo(string $desde, string $hasta): array {
        $consulta = $this->db->prepare(
            'SELECT t.id_trabajador, t.nombre_completo, t.forma_pago, t.activo, r.nombre AS rol, '
            . 'COUNT(a.id_asistencia) AS dias, COALESCE(SUM(a.horas), 0) AS horas '
            . 'FROM trabajador t '
            . 'JOIN usuario u ON u.id_usuario = t.id_usuario '
            . 'JOIN rol r ON r.id_rol = u.id_rol '
            . 'LEFT JOIN asistencia a ON a.id_trabajador = t.id_trabajador '
            . 'AND a.fecha BETWEEN ? AND ? '
            . 'WHERE r.nombre <> ? '
            . 'GROUP BY t.id_trabajador, t.nombre_completo, t.forma_pago, t.activo, r.nombre '
            . 'HAVING t.activo = 1 OR dias > 0 '
            . 'ORDER BY t.nombre_completo'
        );
        $consulta->execute([$desde, $hasta, Auth::ADMINISTRADOR]);

        return $consulta->fetchAll();
    }

}
