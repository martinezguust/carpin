<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/PanelController.php
 * Panel del Contador. En la Fase 1 es una pantalla de
 * bienvenida sin indicadores (respuesta 2): las cuentas por
 * cobrar, comprobantes por vencer y existencias bajas se
 * agregan en las fases siguientes.
 * =====================================================
 */

class PanelController extends Controller {

    /**
     * Muestra el panel del Contador.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function contador(): void {
        Auth::requireRol([Auth::CONTADOR]);

        $this->vista('contador/panel', [], 'panel');
    }

}
