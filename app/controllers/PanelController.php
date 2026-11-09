<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/PanelController.php
 * Panel del Contador (SRS 10.3, respuesta 19 de la Fase 3):
 * total por cobrar, saldos vencidos y existencias bajas,
 * con listas cortas. "Comprobantes por vencer" se agrega en
 * la Fase 4.
 * =====================================================
 */

class PanelController extends Controller {

    /* ===== Renglones de las listas cortas ===== */
    private const LIMITE_LISTA = 5;

    /**
     * Muestra el panel del Contador.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function contador(): void {
        Auth::requireRol([Auth::CONTADOR]);

        $pagos = new PagoCliente();
        $porCobrar = $pagos->listarCuentas(['situacion' => 'CON_SALDO']);
        $vencidos = $pagos->listarCuentas(['situacion' => 'VENCIDOS']);
        $bajos = (new Material())->listarBajos();

        $totalPorCobrar = 0;

        foreach ($porCobrar as $cuenta) {
            $totalPorCobrar += PagoService::aCentavos($cuenta['saldo']);
        }

        $this->vista('contador/panel', [
            'totalPorCobrar' => $totalPorCobrar / 100,
            'cuentasConSaldo' => count($porCobrar),
            'vencidos'       => array_slice($vencidos, 0, self::LIMITE_LISTA),
            'totalVencidos'  => count($vencidos),
            'bajos'          => array_slice($bajos, 0, self::LIMITE_LISTA),
            'totalBajos'     => count($bajos),
        ], 'panel');
    }

}
