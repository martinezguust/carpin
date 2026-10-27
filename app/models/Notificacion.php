<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Notificacion.php
 * Acceso a la tabla notificacion: avisos internos de cada
 * usuario (HU-29). No se envían correos ni mensajes.
 * =====================================================
 */

class Notificacion extends Model {

    /**
     * Crea una notificación para un usuario. Si trae clave única y ya
     * existe otra igual para ese usuario, no se duplica.
     *
     * Recibe: $idUsuario, destinatario; $tipo, ej. "FASE"; $mensaje;
     *         $enlace, ruta interna o null; $claveUnica, o null.
     * Devuelve: nada.
     */
    public function crear(
        int $idUsuario,
        string $tipo,
        string $mensaje,
        ?string $enlace,
        ?string $claveUnica = null
    ): void {
        $consulta = $this->db->prepare(
            'INSERT IGNORE INTO notificacion (id_usuario, tipo, mensaje, enlace, clave_unica) '
            . 'VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->execute([$idUsuario, $tipo, mb_substr($mensaje, 0, 255), $enlace, $claveUnica]);
    }

    /**
     * Cuenta las notificaciones sin leer de un usuario.
     *
     * Recibe: $idUsuario.
     * Devuelve: número de notificaciones con leida = 0.
     */
    public function contarSinLeer(int $idUsuario): int {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM notificacion WHERE id_usuario = ? AND leida = 0'
        );
        $consulta->execute([$idUsuario]);

        return (int) $consulta->fetchColumn();
    }

    /**
     * Lista las notificaciones de un usuario, de la más reciente a la más antigua.
     *
     * Recibe: $idUsuario; $limite, máximo de registros (null para todas).
     * Devuelve: lista de notificaciones.
     */
    public function listar(int $idUsuario, ?int $limite = null): array {
        $sql = 'SELECT id_notificacion, tipo, mensaje, enlace, leida, creado_en '
            . 'FROM notificacion WHERE id_usuario = ? '
            . 'ORDER BY creado_en DESC, id_notificacion DESC';

        if ($limite !== null) {
            $sql .= ' LIMIT ' . max(1, $limite);
        }

        $consulta = $this->db->prepare($sql);
        $consulta->execute([$idUsuario]);

        return $consulta->fetchAll();
    }

    /**
     * Marca como leída una notificación, solo si es del usuario indicado.
     *
     * Recibe: $idNotificacion; $idUsuario, dueño de la notificación.
     * Devuelve: la notificación (para seguir su enlace), o null si no es suya.
     */
    public function marcarLeida(int $idNotificacion, int $idUsuario): ?array {
        $consulta = $this->db->prepare(
            'SELECT id_notificacion, enlace FROM notificacion '
            . 'WHERE id_notificacion = ? AND id_usuario = ?'
        );
        $consulta->execute([$idNotificacion, $idUsuario]);
        $notificacion = $consulta->fetch();

        if ($notificacion === false) {
            return null;
        }

        $actualizar = $this->db->prepare(
            'UPDATE notificacion SET leida = 1 WHERE id_notificacion = ?'
        );
        $actualizar->execute([$idNotificacion]);

        return $notificacion;
    }

}
