<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/ClientePortalController.php
 * Portal del cliente (CU-06, HU-23, RF-29 a RF-32, RF-36):
 * lista de sus proyectos y reparaciones, y detalle con la
 * gráfica de avance. Un cliente nunca ve pedidos de otro
 * aunque cambie la URL (RF-32).
 * =====================================================
 */

class ClientePortalController extends Controller {

    /**
     * Muestra la lista de pedidos del cliente: proyectos y reparaciones
     * (RF-29).
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireRol([Auth::CLIENTE]);

        $cliente = $this->clienteActual();
        $idCliente = (int) $cliente['id_cliente'];

        $this->vista('cliente/pedidos', [
            'cliente'      => $cliente,
            'proyectos'    => (new Proyecto())->listarPorCliente($idCliente),
            'reparaciones' => (new Reparacion())->listarPorCliente($idCliente),
            'estados'      => Reparacion::ESTADOS,
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
     * Muestra el estado de una reparación del cliente: gráfica, estados,
     * fotos, costo y saldo (RF-36). Si es de otro cliente responde 403.
     *
     * Recibe: $id, id de la reparación (segmento de la ruta).
     * Devuelve: nada.
     */
    public function reparacion(string $id): void {
        Auth::requireRol([Auth::CLIENTE]);

        $cliente = $this->clienteActual();
        $reparacion = (new Reparacion())->buscarPorId($this->idDeRuta($id));

        if ($reparacion === null) {
            View::error(404);
        }

        // Validación de propietario en el servidor (RF-32)
        if ((int) $reparacion['id_cliente'] !== (int) $cliente['id_cliente']) {
            View::error(403);
        }

        $idReparacion = (int) $reparacion['id_reparacion'];

        $this->vista('cliente/reparacion', [
            'reparacion'   => $reparacion,
            'estados'      => Reparacion::ESTADOS,
            'imagenes'     => (new ImagenReparacion())->listarPorReparacion($idReparacion),
            'saldo'        => PagoService::saldo($reparacion['costo'], $reparacion['pagado']),
            'mensajeListo' => FaseService::mensajeListoCliente($reparacion['descripcion_mueble']),
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
