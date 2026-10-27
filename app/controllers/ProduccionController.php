<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/ProduccionController.php
 * Pantallas de producción del Lijador y del Pintor.
 * Fase 1: "Mis pendientes" (HU-01, RF-19). El avance de fase
 * por el trabajador, la ficha de color, las capas y el
 * secado se agregan en la Fase 2.
 * =====================================================
 */

class ProduccionController extends Controller {

    /**
     * Muestra las actividades pendientes del trabajador, ordenadas por
     * fecha de entrega (RF-19).
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function misPendientes(): void {
        Auth::requireRol([Auth::LIJADOR, Auth::PINTOR]);

        $trabajador = (new Trabajador())->buscarPorUsuario((int) Auth::id());

        if ($trabajador === null) {
            View::error(403);
        }

        $pendientes = (new Actividad())->pendientesDeTrabajador((int) $trabajador['id_trabajador']);
        $carpeta = (Auth::rol() === Auth::LIJADOR) ? 'lijador' : 'pintor';

        $this->vista($carpeta . '/pendientes', [
            'trabajador' => $trabajador,
            'pendientes' => $pendientes,
        ], 'panel');
    }

}
