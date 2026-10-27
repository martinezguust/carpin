<?php
/*
 * =====================================================
 * Carpintería Carpin – app/services/NotificacionService.php
 * Genera las notificaciones internas de los eventos del
 * sistema (SRS 5.2) y las alertas periódicas. Solo avisos
 * dentro del sistema: no se envían correos, SMS ni WhatsApp.
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
     * Genera las alertas periódicas pendientes (SRS 5.2): se revisan al
     * iniciar sesión cualquier usuario y en cada consulta de la campana,
     * sin tareas programadas del servidor.
     * Fase 2: secados cumplidos. Los saldos vencidos y los comprobantes
     * de proveedores por vencer se agregan en las Fases 3 y 4.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public static function revisarAlertasPeriodicas(): void {
        self::avisarSecadosCumplidos();
    }

    /**
     * Avisa a los pintores asignados que terminó el tiempo de secado
     * (RF-27). Solo si el proyecto sigue en "Pintado" o "Secado"; el
     * secado queda marcado como avisado en todos los casos (respuesta 6).
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    private static function avisarSecadosCumplidos(): void {
        $secados = new Secado();
        $actividades = new Actividad();

        foreach ($secados->cumplidosSinAviso() as $secado) {
            $secados->marcarNotificado((int) $secado['id_secado']);

            $sigueEnPintura = (int) $secado['activo'] === 1
                && in_array((int) $secado['id_fase'], [Fase::PINTADO, Fase::SECADO], true);

            if (!$sigueEnPintura) {
                continue;
            }

            $modelo = new Notificacion();
            $mensaje = 'Terminó el tiempo de secado del proyecto '
                . $secado['folio'] . ' (' . $secado['tipo_mueble'] . ')';

            $pintores = $actividades->usuariosAsignados((int) $secado['id_proyecto'], 'PINTURA');

            foreach ($pintores as $idUsuario) {
                $modelo->crear(
                    $idUsuario,
                    'SECADO',
                    $mensaje,
                    '/mis-pendientes',
                    'SEC-' . $secado['id_secado']
                );
            }
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
