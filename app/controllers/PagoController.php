<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/PagoController.php
 * Pagos de clientes (HU-27, HU-15, RF-48, RF-49, RF-51,
 * CU-11): cuentas por cobrar de proyectos y reparaciones,
 * detalle con sus pagos y registro de pagos.
 *   - Contador: "Pagos de clientes" (consulta y registra);
 *   - Carpintero: "Finanzas" (solo consulta, SRS 5.4).
 * El sistema NO cobra: solo registra pagos hechos en persona.
 * =====================================================
 */

class PagoController extends Controller {

    /* ===== Roles ===== */
    private const ROLES = [Auth::CONTADOR, Auth::CARPINTERO];

    /* ===== Tipo de pedido en la ruta → tipo interno ===== */
    private const TIPOS_RUTA = [
        'proyecto'   => 'PRY',
        'reparacion' => 'REP',
    ];

    /* ===== Límite de la referencia ===== */
    private const MAX_REFERENCIA = 60;

    /**
     * Muestra las cuentas por cobrar con filtros por tipo, situación y
     * búsqueda (folio o cliente).
     *
     * Recibe: nada (usa $_GET: tipo, situacion, q).
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireRol(self::ROLES);

        $situacion = (string) ($_GET['situacion'] ?? 'CON_SALDO');

        if (!isset(PagoCliente::SITUACIONES[$situacion])) {
            $situacion = 'CON_SALDO';
        }

        $filtros = [
            'tipo'      => in_array($_GET['tipo'] ?? '', ['PRY', 'REP'], true) ? $_GET['tipo'] : '',
            'situacion' => $situacion,
            'busqueda'  => trim((string) ($_GET['q'] ?? '')),
        ];

        $this->vista('comun/pagos', [
            'cuentas'      => (new PagoCliente())->listarCuentas($filtros),
            'filtros'      => $filtros,
            'situaciones'  => PagoCliente::SITUACIONES,
            'esContador'   => Auth::rol() === Auth::CONTADOR,
        ], 'panel');
    }

    /**
     * Muestra un proyecto o una reparación con sus pagos y, para el
     * Contador, el formulario "Registrar pago" (CU-11).
     *
     * Recibe: $tipo, "proyecto" o "reparacion"; $id (segmentos de la ruta).
     * Devuelve: nada.
     */
    public function detalle(string $tipo, string $id): void {
        Auth::requireRol(self::ROLES);

        [$tipoInterno, $pedido] = $this->buscarPedido($tipo, $this->idDeRuta($id));

        $this->mostrarDetalle($tipoInterno, $pedido, [
            'monto'      => '',
            'metodo'     => '',
            'fecha'      => date('Y-m-d'),
            'referencia' => '',
        ], []);
    }

    /**
     * Registra un pago del cliente (CU-11): monto mayor a 0 y que no pase
     * del saldo, método, fecha (no futura) y referencia opcional.
     *
     * Recibe: $tipo y $id (segmentos de la ruta); $_POST con los datos.
     * Devuelve: nada.
     */
    public function registrar(string $tipo, string $id): void {
        Auth::requireRol([Auth::CONTADOR]);

        [$tipoInterno, $pedido] = $this->buscarPedido($tipo, $this->idDeRuta($id));

        $datos = [
            'monto'      => $this->textoPost('monto'),
            'metodo'     => $this->textoPost('metodo'),
            'fecha'      => $this->textoPost('fecha'),
            'referencia' => $this->textoPost('referencia'),
        ];

        $errores = [];

        if (!is_numeric($datos['monto']) || round((float) $datos['monto'], 2) <= 0) {
            $errores['monto'] = 'El monto debe ser mayor a $0.00.';
        }

        if (!isset(PagoCliente::METODOS[$datos['metodo']])) {
            $errores['metodo'] = 'Elige el método de pago.';
        }

        if (!$this->fechaValida($datos['fecha'])) {
            $errores['fecha'] = 'Elige la fecha del pago.';
        } elseif ($datos['fecha'] > date('Y-m-d')) {
            $errores['fecha'] = 'La fecha del pago no puede ser futura.';
        }

        if (mb_strlen($datos['referencia']) > self::MAX_REFERENCIA) {
            $errores['referencia'] = 'La referencia no puede pasar de 60 caracteres.';
        }

        if ($errores === []) {
            try {
                $status = PagoService::registrarPago(
                    $tipoInterno,
                    $this->idDelPedido($tipoInterno, $pedido),
                    number_format(round((float) $datos['monto'], 2), 2, '.', ''),
                    $datos['metodo'],
                    $datos['fecha'],
                    ($datos['referencia'] !== '') ? $datos['referencia'] : null,
                    (int) Auth::id()
                );

                Flash::agregar(
                    'exito',
                    'Pago registrado. Status de pago: ' . etiquetaStatusPago($status) . '.'
                );
                $idPedido = $this->idDelPedido($tipoInterno, $pedido);
                $this->redirigir('/pagos/' . $tipo . '/' . $idPedido);
            } catch (DomainException $excepcion) {
                $errores['monto'] = $excepcion->getMessage();
            }
        }

        $this->mostrarDetalle($tipoInterno, $pedido, $datos, $errores);
    }

    /* ===== Funciones internas ===== */

    /**
     * Dibuja el detalle de pagos de un pedido.
     *
     * Recibe: $tipo, "PRY" o "REP"; $pedido, datos completos;
     *         $datos, valores del formulario; $errores.
     * Devuelve: nada.
     */
    private function mostrarDetalle(
        string $tipo,
        array $pedido,
        array $datos,
        array $errores
    ): void {
        $importe = ($tipo === 'REP') ? $pedido['costo'] : $pedido['precio'];
        $idPedido = $this->idDelPedido($tipo, $pedido);
        $saldo = PagoService::saldo($importe, $pedido['pagado']);

        $this->vista('comun/pago_detalle', [
            'tipo'       => $tipo,
            'tipoRuta'   => array_search($tipo, self::TIPOS_RUTA, true),
            'pedido'     => $pedido,
            'importe'    => $importe,
            'saldo'      => $saldo,
            'vencido'    => PagoService::estaVencido($saldo, $pedido['fecha_limite_pago']),
            'pagos'      => (new PagoCliente())->listarDe($tipo, $idPedido),
            'metodos'    => PagoCliente::METODOS,
            'datos'      => $datos,
            'errores'    => $errores,
            'esContador' => Auth::rol() === Auth::CONTADOR,
        ], 'panel');
    }

    /**
     * Busca el proyecto o la reparación de la ruta, o responde 404.
     *
     * Recibe: $tipo, "proyecto" o "reparacion"; $id.
     * Devuelve: [tipo interno "PRY"/"REP", datos completos del pedido].
     */
    private function buscarPedido(string $tipo, int $id): array {
        $tipoInterno = self::TIPOS_RUTA[$tipo] ?? null;

        if ($tipoInterno === null) {
            View::error(404);
        }

        if ($tipoInterno === 'REP') {
            $pedido = (new Reparacion())->buscarPorId($id);
        } else {
            $pedido = (new Proyecto())->buscarPorId($id);
        }

        if ($pedido === null) {
            View::error(404);
        }

        return [$tipoInterno, $pedido];
    }

    /**
     * Devuelve el id del pedido según su tipo.
     *
     * Recibe: $tipo, "PRY" o "REP"; $pedido.
     * Devuelve: id_proyecto o id_reparacion.
     */
    private function idDelPedido(string $tipo, array $pedido): int {
        return (int) (($tipo === 'REP') ? $pedido['id_reparacion'] : $pedido['id_proyecto']);
    }

}
