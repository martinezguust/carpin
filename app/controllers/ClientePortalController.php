<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/ClientePortalController.php
 * Portal del cliente (CU-06, HU-23): lista de sus pedidos y
 * detalle con la gráfica de avance. Un cliente nunca ve
 * pedidos de otro aunque cambie la URL (RF-32).
 * =====================================================
 */

class ClientePortalController extends Controller {

    /**
     * Muestra la lista de pedidos del cliente (RF-29).
     * Fase 1: proyectos; las reparaciones se agregan en la Fase 2.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireRol([Auth::CLIENTE]);

        $cliente = $this->clienteActual();

        $this->vista('cliente/pedidos', [
            'cliente'   => $cliente,
            'proyectos' => (new Proyecto())->listarPorCliente((int) $cliente['id_cliente']),
        ], 'panel');
    }

    /**
     * Muestra el avance de un proyecto del cliente (RF-30, RF-31, mockup 8).
     * Si el proyecto es de otro cliente responde 403 (CU-06, A2).
     *
     * Recibe: $id, id del proyecto (segmento de la ruta).
     * Devuelve: nada.
     */
    public function proyecto(string $id): void {
        Auth::requireRol([Auth::CLIENTE]);

        $cliente = $this->clienteActual();
        $proyecto = (new Proyecto())->buscarPorId($this->idDeRuta($id));

        if ($proyecto === null) {
            View::error(404);
        }

        // Validación de propietario en el servidor (RF-32)
        if ((int) $proyecto['id_cliente'] !== (int) $cliente['id_cliente']) {
            View::error(403);
        }

        $this->vista('cliente/proyecto', [
            'proyecto'     => $proyecto,
            'fases'        => (new Fase())->todas(),
            'saldo'        => PagoService::saldo($proyecto['precio'], $proyecto['pagado']),
            'mensajeListo' => FaseService::mensajeListoCliente($proyecto['tipo_mueble']),
        ], 'panel');
    }

    /**
     * Devuelve los datos de cliente de la cuenta con sesión.
     *
     * Recibe: nada.
     * Devuelve: datos del cliente (403 si la cuenta no tiene cliente).
     */
    private function clienteActual(): array {
        $cliente = (new Cliente())->buscarPorUsuario((int) Auth::id());

        if ($cliente === null) {
            View::error(403);
        }

        return $cliente;
    }

}
