<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/NotificacionController.php
 * Centro de notificaciones (HU-29, RF-60 a RF-62):
 *   - página con todas las notificaciones del usuario;
 *   - datos de la campana en JSON (se consultan cada 60 s);
 *   - marcar como leída y abrir el elemento que indica.
 * =====================================================
 */

class NotificacionController extends Controller {

    /* ===== Notificaciones que se muestran en la campana (respuesta 17) ===== */
    private const LIMITE_CAMPANA = 10;

    /**
     * Muestra todas las notificaciones del usuario.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireLogin();

        $this->vista('comun/notificaciones', [
            'notificaciones' => (new Notificacion())->listar((int) Auth::id()),
        ], 'panel');
    }

    /**
     * Devuelve en JSON el número de notificaciones sin leer y las más
     * recientes, para actualizar la campana sin recargar (RF-62).
     * Si la sesión expiró responde 401 para que la página vaya al login.
     *
     * Recibe: nada.
     * Devuelve: nada (imprime JSON).
     */
    public function campana(): void {
        if (!Auth::estaAutenticado()) {
            $this->json(['sesion' => false], 401);
        }

        // Alertas periódicas en cada consulta de la campana (SRS 5.2)
        NotificacionService::revisarAlertasPeriodicas();

        $modelo = new Notificacion();
        $idUsuario = (int) Auth::id();
        $lista = [];

        foreach ($modelo->listar($idUsuario, self::LIMITE_CAMPANA) as $notificacion) {
            $lista[] = [
                'id'      => (int) $notificacion['id_notificacion'],
                'mensaje' => $notificacion['mensaje'],
                'fecha'   => fechaHora($notificacion['creado_en']),
                'leida'   => (int) $notificacion['leida'] === 1,
            ];
        }

        $this->json([
            'sesion'         => true,
            'sinLeer'        => $modelo->contarSinLeer($idUsuario),
            'notificaciones' => $lista,
        ]);
    }

    /**
     * Marca una notificación como leída y lleva al elemento que indica.
     * Solo funciona con notificaciones del propio usuario.
     *
     * Recibe: $id, id de la notificación (segmento de la ruta).
     * Devuelve: nada (redirige).
     */
    public function leer(string $id): void {
        Auth::requireLogin();

        $notificacion = (new Notificacion())->marcarLeida($this->idDeRuta($id), (int) Auth::id());

        if ($notificacion === null) {
            View::error(404);
        }

        // Solo se siguen rutas internas del sistema
        $enlace = (string) $notificacion['enlace'];
        $esInterno = str_starts_with($enlace, '/') && !str_starts_with($enlace, '//');

        $this->redirigir($esInterno ? $enlace : '/notificaciones');
    }

}
