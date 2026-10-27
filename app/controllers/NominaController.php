<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/NominaController.php
 * Configuración de la nómina (HU-30, RF-43, RF-44, RF-55,
 * RN-08). Solo el Contador y el Carpintero cambian tarifas y
 * porcentajes:
 *   - forma de pago y tarifa de cada trabajador (el Carpintero
 *     no puede editar su propia tarifa: respuesta 16);
 *   - porcentaje de mano de obra por rol (deben sumar 100 %);
 *   - porcentajes de mano de obra y ganancia de presupuestos.
 * El cálculo de la nómina se agrega en la Fase 4.
 * =====================================================
 */

class NominaController extends Controller {

    /* ===== Roles que configuran tarifas y porcentajes (RN-08) ===== */
    private const ROLES = [Auth::CONTADOR, Auth::CARPINTERO];

    /* ===== Límites ===== */
    private const MAX_TARIFA = 99999999.99;   // DECIMAL(10,2)
    private const MAX_PORCENTAJE = 100;       // respuesta 17

    /* ===== Formas de pago de los trabajadores ===== */

    /**
     * Muestra la lista de trabajadores con su forma de pago y tarifa.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function trabajadores(): void {
        Auth::requireRol(self::ROLES);

        $this->vista('comun/trabajadores', [
            'trabajadores' => (new Trabajador())->listarConFormaPago(),
        ], 'panel');
    }

    /**
     * Muestra el formulario de forma de pago de un trabajador.
     *
     * Recibe: $id, id del trabajador (segmento de la ruta).
     * Devuelve: nada.
     */
    public function editarTrabajador(string $id): void {
        Auth::requireRol(self::ROLES);

        $trabajador = $this->trabajadorEditable($this->idDeRuta($id));

        $datos = [
            'forma_pago'  => (string) $trabajador['forma_pago'],
            'tarifa_hora' => (string) $trabajador['tarifa_hora'],
            'salario_dia' => (string) $trabajador['salario_dia'],
        ];

        $this->mostrarTrabajador($trabajador, $datos, []);
    }

    /**
     * Guarda la forma de pago y la tarifa de un trabajador (RF-43).
     *
     * Recibe: $id, id del trabajador (segmento de la ruta);
     *         $_POST: forma_pago, tarifa_hora y salario_dia.
     * Devuelve: nada.
     */
    public function guardarTrabajador(string $id): void {
        Auth::requireRol(self::ROLES);

        $trabajador = $this->trabajadorEditable($this->idDeRuta($id));

        $datos = [
            'forma_pago'  => $this->textoPost('forma_pago'),
            'tarifa_hora' => $this->textoPost('tarifa_hora'),
            'salario_dia' => $this->textoPost('salario_dia'),
        ];

        $errores = [];
        $permitidas = Trabajador::FORMAS_POR_ROL[$trabajador['rol']] ?? [];

        if (!in_array($datos['forma_pago'], $permitidas, true)) {
            $errores['forma_pago'] = 'Elige una forma de pago válida para el rol '
                . etiquetaRol($trabajador['rol']) . '.';
        }

        // La tarifa que se pide depende de la forma de pago
        $tarifaHora = null;
        $salarioDia = null;

        if ($datos['forma_pago'] === 'HORA') {
            $tarifaHora = $this->montoValido($datos['tarifa_hora']);

            if ($tarifaHora === null) {
                $errores['tarifa_hora'] = 'Escribe la tarifa por hora (mayor a $0.00).';
            }
        }

        if ($datos['forma_pago'] === 'DIA') {
            $salarioDia = $this->montoValido($datos['salario_dia']);

            if ($salarioDia === null) {
                $errores['salario_dia'] = 'Escribe el salario diario (mayor a $0.00).';
            }
        }

        if ($errores !== []) {
            $this->mostrarTrabajador($trabajador, $datos, $errores);
            return;
        }

        (new Trabajador())->actualizarFormaPago(
            (int) $trabajador['id_trabajador'],
            $datos['forma_pago'],
            $tarifaHora,
            $salarioDia
        );

        Flash::agregar(
            'exito',
            "Forma de pago de {$trabajador['nombre_completo']} guardada."
        );
        $this->redirigir($this->rutaLista());
    }

    /* ===== Porcentajes ===== */

    /**
     * Muestra los porcentajes por rol y de presupuestos. Para el
     * Carpintero ("Precios y porcentajes") también muestra la lista de
     * tarifas de los trabajadores (respuesta 16).
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function porcentajes(): void {
        Auth::requireRol(self::ROLES);

        $negocio = (new Configuracion())->obtener();
        $porcentajesRol = (new PorcentajeRol())->listar();
        $datosRol = [];

        foreach ($porcentajesRol as $fila) {
            $datosRol[(int) $fila['id_rol']] = (string) $fila['porcentaje'];
        }

        $this->mostrarPorcentajes($porcentajesRol, $datosRol, [
            'pct_mano_obra' => (string) $negocio['pct_mano_obra'],
            'pct_ganancia'  => (string) $negocio['pct_ganancia'],
        ], []);
    }

    /**
     * Guarda el porcentaje de mano de obra de cada rol; entre todos deben
     * sumar 100 % (RF-44).
     *
     * Recibe: nada (usa $_POST['porcentaje'][id_rol]).
     * Devuelve: nada.
     */
    public function guardarPorcentajesRol(): void {
        Auth::requireRol(self::ROLES);

        $modelo = new PorcentajeRol();
        $porcentajesRol = $modelo->listar();
        $recibidos = $_POST['porcentaje'] ?? [];
        $datosRol = [];
        $errores = [];
        $sumaCentesimas = 0;

        foreach ($porcentajesRol as $fila) {
            $idRol = (int) $fila['id_rol'];
            $valor = is_array($recibidos) ? trim((string) ($recibidos[$idRol] ?? '')) : '';
            $datosRol[$idRol] = $valor;

            if (!$this->porcentajeValido($valor)) {
                $errores['rol_' . $idRol] = 'Escribe un porcentaje de 0 a 100.';
                continue;
            }

            $sumaCentesimas += (int) round((float) $valor * 100);
        }

        if ($errores === [] && $sumaCentesimas !== 10000) {
            $errores['suma_roles'] = 'Los porcentajes por rol deben sumar 100 % (ahora suman '
                . number_format($sumaCentesimas / 100, 2) . ' %).';
        }

        if ($errores !== []) {
            $negocio = (new Configuracion())->obtener();

            $this->mostrarPorcentajes($porcentajesRol, $datosRol, [
                'pct_mano_obra' => (string) $negocio['pct_mano_obra'],
                'pct_ganancia'  => (string) $negocio['pct_ganancia'],
            ], $errores);
            return;
        }

        foreach ($datosRol as $idRol => $valor) {
            $modelo->actualizar($idRol, number_format(round((float) $valor, 2), 2, '.', ''));
        }

        Flash::agregar('exito', 'Porcentajes de mano de obra por rol guardados.');
        $this->redirigir('/porcentajes');
    }

    /**
     * Guarda los porcentajes de mano de obra y ganancia de los
     * presupuestos (RF-55).
     *
     * Recibe: nada (usa $_POST: pct_mano_obra y pct_ganancia).
     * Devuelve: nada.
     */
    public function guardarPorcentajesPresupuesto(): void {
        Auth::requireRol(self::ROLES);

        $datos = [
            'pct_mano_obra' => $this->textoPost('pct_mano_obra'),
            'pct_ganancia'  => $this->textoPost('pct_ganancia'),
        ];
        $errores = [];

        foreach ($datos as $campo => $valor) {
            if (!$this->porcentajeValido($valor)) {
                $errores[$campo] = 'Escribe un porcentaje de 0 a 100.';
            }
        }

        if ($errores !== []) {
            $porcentajesRol = (new PorcentajeRol())->listar();
            $datosRol = [];

            foreach ($porcentajesRol as $fila) {
                $datosRol[(int) $fila['id_rol']] = (string) $fila['porcentaje'];
            }

            $this->mostrarPorcentajes($porcentajesRol, $datosRol, $datos, $errores);
            return;
        }

        (new Configuracion())->actualizarPorcentajes(
            number_format(round((float) $datos['pct_mano_obra'], 2), 2, '.', ''),
            number_format(round((float) $datos['pct_ganancia'], 2), 2, '.', '')
        );

        Flash::agregar('exito', 'Porcentajes de presupuestos guardados.');
        $this->redirigir('/porcentajes');
    }

    /* ===== Funciones internas ===== */

    /**
     * Busca un trabajador que se puede configurar. Responde 404 si no
     * existe o es Administrador, y 403 si el Carpintero intenta editar
     * su propia tarifa (respuesta 16).
     *
     * Recibe: $idTrabajador.
     * Devuelve: datos del trabajador con su rol.
     */
    private function trabajadorEditable(int $idTrabajador): array {
        $trabajador = (new Trabajador())->buscarPorId($idTrabajador);

        if ($trabajador === null || $trabajador['rol'] === Auth::ADMINISTRADOR) {
            View::error(404);
        }

        $esPropia = (int) $trabajador['id_usuario'] === Auth::id();

        if ($esPropia && Auth::rol() === Auth::CARPINTERO) {
            View::error(403);
        }

        return $trabajador;
    }

    /**
     * Dibuja el formulario de forma de pago.
     *
     * Recibe: $trabajador; $datos, valores del formulario; $errores.
     * Devuelve: nada.
     */
    private function mostrarTrabajador(array $trabajador, array $datos, array $errores): void {
        $this->vista('comun/trabajador_pago', [
            'trabajador' => $trabajador,
            'datos'      => $datos,
            'errores'    => $errores,
            'permitidas' => Trabajador::FORMAS_POR_ROL[$trabajador['rol']] ?? [],
            'rutaLista'  => $this->rutaLista(),
        ], 'panel');
    }

    /**
     * Dibuja la pantalla de porcentajes.
     *
     * Recibe: $porcentajesRol, filas de porcentaje_rol; $datosRol, valores
     *         por id_rol; $datosPresupuesto, % MO y % ganancia; $errores.
     * Devuelve: nada.
     */
    private function mostrarPorcentajes(
        array $porcentajesRol,
        array $datosRol,
        array $datosPresupuesto,
        array $errores
    ): void {
        $esCarpintero = Auth::rol() === Auth::CARPINTERO;

        $this->vista('comun/porcentajes', [
            'porcentajesRol'   => $porcentajesRol,
            'datosRol'         => $datosRol,
            'datosPresupuesto' => $datosPresupuesto,
            'errores'          => $errores,
            'esCarpintero'     => $esCarpintero,
            'trabajadores'     => $esCarpintero ? (new Trabajador())->listarConFormaPago() : [],
        ], 'panel');
    }

    /**
     * Devuelve la pantalla a la que se regresa después de editar una tarifa:
     * "Precios y porcentajes" para el Carpintero y "Trabajadores y formas
     * de pago" para el Contador.
     *
     * Recibe: nada.
     * Devuelve: ruta interna.
     */
    private function rutaLista(): string {
        return (Auth::rol() === Auth::CARPINTERO) ? '/porcentajes' : '/trabajadores';
    }

    /**
     * Revisa un monto de tarifa: número mayor a 0 dentro del límite.
     *
     * Recibe: $valor, texto capturado.
     * Devuelve: monto con 2 decimales, o null si no es válido.
     */
    private function montoValido(string $valor): ?string {
        if (!is_numeric($valor) || (float) $valor <= 0 || (float) $valor > self::MAX_TARIFA) {
            return null;
        }

        return number_format(round((float) $valor, 2), 2, '.', '');
    }

    /**
     * Revisa un porcentaje: número de 0 a 100 (respuesta 17).
     *
     * Recibe: $valor, texto capturado.
     * Devuelve: true si es válido.
     */
    private function porcentajeValido(string $valor): bool {
        return is_numeric($valor)
            && (float) $valor >= 0
            && (float) $valor <= self::MAX_PORCENTAJE;
    }

}
