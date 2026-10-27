<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/CotizacionController.php
 * Bandeja de solicitudes de cotización del Carpintero
 * (HU-24, RF-14): Nueva → Contactado → Convertida en
 * proyecto / Descartada. "Convertir" precarga el formulario
 * de proyecto (ProyectoController::nuevo).
 * =====================================================
 */

class CotizacionController extends Controller {

    /**
     * Muestra la bandeja con filtro por estado.
     *
     * Recibe: nada (usa $_GET['estado'], opcional).
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $estado = (string) ($_GET['estado'] ?? '');

        if (!isset(SolicitudCotizacion::ESTADOS[$estado])) {
            $estado = '';
        }

        $this->vista('carpintero/cotizaciones', [
            'solicitudes' => (new SolicitudCotizacion())->listar($estado),
            'estado'      => $estado,
            'estados'     => SolicitudCotizacion::ESTADOS,
        ], 'panel');
    }

    /**
     * Muestra el detalle de una solicitud con sus acciones.
     *
     * Recibe: $id, id de la solicitud (segmento de la ruta).
     * Devuelve: nada.
     */
    public function detalle(string $id): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $solicitud = $this->buscarSolicitud($this->idDeRuta($id));

        $this->vista('carpintero/cotizacion_detalle', [
            'solicitud' => $solicitud,
            'estados'   => SolicitudCotizacion::ESTADOS,
            'abierta'   => in_array(
                $solicitud['estado'],
                SolicitudCotizacion::ESTADOS_ABIERTOS,
                true
            ),
        ], 'panel');
    }

    /**
     * Marca la solicitud como contactada o la descarta (RF-14,
     * respuesta 13 de la Fase 2). Una descartada no se reabre.
     *
     * Recibe: $id, id de la solicitud (segmento de la ruta);
     *         $_POST['accion'], "contactado" o "descartar".
     * Devuelve: nada (redirige al detalle).
     */
    public function cambiarEstado(string $id): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $solicitud = $this->buscarSolicitud($this->idDeRuta($id));
        $idSolicitud = (int) $solicitud['id_solicitud'];
        $accion = $this->textoPost('accion');
        $volver = '/cotizaciones/' . $idSolicitud;

        $abierta = in_array($solicitud['estado'], SolicitudCotizacion::ESTADOS_ABIERTOS, true);

        if (!$abierta) {
            Flash::agregar('error', 'Esta solicitud ya está cerrada.');
            $this->redirigir($volver);
        }

        $solicitudes = new SolicitudCotizacion();

        if ($accion === 'contactado' && $solicitud['estado'] === 'NUEVA') {
            $solicitudes->cambiarEstado($idSolicitud, 'CONTACTADO');
            Flash::agregar('exito', 'La solicitud quedó como "Contactado".');
        } elseif ($accion === 'descartar') {
            $solicitudes->cambiarEstado($idSolicitud, 'DESCARTADA');
            Flash::agregar('exito', 'La solicitud quedó como "Descartada".');
        } else {
            Flash::agregar('error', 'Esa acción no aplica a la solicitud.');
        }

        $this->redirigir($volver);
    }

    /**
     * Busca una solicitud o responde 404.
     *
     * Recibe: $idSolicitud.
     * Devuelve: datos de la solicitud.
     */
    private function buscarSolicitud(int $idSolicitud): array {
        $solicitud = (new SolicitudCotizacion())->buscarPorId($idSolicitud);

        if ($solicitud === null) {
            View::error(404);
        }

        return $solicitud;
    }

}
