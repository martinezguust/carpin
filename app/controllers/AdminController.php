<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/AdminController.php
 * Panel del Administrador (SRS 10.3): totales de usuarios,
 * productos del catálogo y proyectos activos.
 * =====================================================
 */

class AdminController extends Controller {

    /**
     * Muestra el panel del Administrador.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function panel(): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $this->vista('admin/panel', [
            'totalUsuarios'  => (new Usuario())->contarActivos(),
            'totalProductos' => (new ProductoCatalogo())->contarVisibles(),
            'totalProyectos' => (new Proyecto())->contarActivos(),
        ], 'panel');
    }

}
