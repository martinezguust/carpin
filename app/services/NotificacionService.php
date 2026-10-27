<?php
/*
 * =====================================================
 * Carpintería Carpin – app/services/NotificacionService.php
 * Genera las notificaciones internas de los eventos del
 * sistema (SRS 5.2). Solo avisos dentro del sistema: no se
 * envían correos, SMS ni WhatsApp.
 * =====================================================
 */

final class NotificacionService {

    /**
     * Crea la misma notificación para varios usuarios.
     *
     * Recibe: $idsUsuario, destinatarios; $tipo, ej. "FASE"; $mensaje;
     *         $enlace, ruta interna o null;
     *         $excluir, id de usuario que no debe recibirla (quien hizo
     *         la acción), o null.
     * Devuelve: nada.
     */
    public static function notificar(
        array $idsUsuario,
        string $tipo,
        string $mensaje,
        ?string $enlace,
        ?int $excluir = null
    ): void {
        $modelo = new Notificacion();

        foreach (array_unique($idsUsuario) as $idUsuario) {
            if ($excluir !== null && (int) $idUsuario === $excluir) {
                continue;
            }

            $modelo->crear((int) $idUsuario, $tipo, $mensaje, $enlace);
        }
    }

    /**
     * Devuelve las cuentas activas de todas las personas de un rol
     * (respuesta 31: se notifica a todos los activos del rol).
     *
     * Recibe: $rol, nombre del rol, ej. "CONTADOR".
     * Devuelve: lista de id_usuario.
     */
    public static function usuariosDeRol(string $rol): array {
        $consulta = Database::get()->prepare(
            'SELECT u.id_usuario FROM usuario u '
            . 'JOIN rol r ON r.id_rol = u.id_rol '
            . 'WHERE r.nombre = ? AND u.activo = 1'
        );
        $consulta->execute([$rol]);

        return array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
    }

}
