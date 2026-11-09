<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/InventarioController.php
 * Inventario (HU-07, HU-16, RF-37 a RF-39, RF-41):
 *   - Carpintero y Contador: alta, edición, baja / reactivar
 *     y movimientos de todos los materiales;
 *   - Pintor: consulta y movimientos solo de Pintura,
 *     Solvente y Barniz (SRS 5.4); no cambia costos (RN-08);
 *   - Contador: lista de materiales faltantes / por reemplazar.
 * =====================================================
 */

class InventarioController extends Controller {

    /* ===== Roles con acceso ===== */
    private const ROLES_GESTION = [Auth::CARPINTERO, Auth::CONTADOR];
    private const ROLES_CONSULTA = [Auth::CARPINTERO, Auth::CONTADOR, Auth::PINTOR];

    /* ===== Lista y alta ===== */

    /**
     * Muestra la lista de materiales; los bajos se marcan en rojo (RF-39).
     *
     * Recibe: nada (usa $_GET: categoria, bajas).
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireRol(self::ROLES_CONSULTA);

        $esPintor = Auth::rol() === Auth::PINTOR;
        $categoria = (string) ($_GET['categoria'] ?? '');
        $permitidas = $esPintor ? Material::CATEGORIAS_PINTOR : array_keys(Material::CATEGORIAS);

        if (!in_array($categoria, $permitidas, true)) {
            $categoria = '';
        }

        $incluirBajas = !$esPintor && ($_GET['bajas'] ?? '') === '1';

        $this->vista('comun/inventario', [
            'materiales'   => (new Material())->listar(
                $esPintor ? Material::CATEGORIAS_PINTOR : [],
                $categoria,
                $incluirBajas
            ),
            'categorias'   => array_intersect_key(Material::CATEGORIAS, array_flip($permitidas)),
            'categoria'    => $categoria,
            'incluirBajas' => $incluirBajas,
            'esPintor'     => $esPintor,
            'proyectoDestino' => ctype_digit((string) ($_GET['proyecto'] ?? ''))
                ? (int) $_GET['proyecto']
                : 0,
        ], 'panel');
    }

    /**
     * Muestra el formulario de material nuevo.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function nuevo(): void {
        Auth::requireRol(self::ROLES_GESTION);

        $datos = [
            'nombre'         => '',
            'categoria'      => '',
            'unidad'         => '',
            'minimo'         => '3',
            'costo_unitario' => '',
            'existencia'     => '',
        ];

        $this->mostrarFormulario(null, $datos, []);
    }

    /**
     * Registra un material con su existencia inicial (respuesta 2).
     *
     * Recibe: nada (usa $_POST).
     * Devuelve: nada.
     */
    public function guardar(): void {
        Auth::requireRol(self::ROLES_GESTION);

        $datos = $this->leerFormulario(true);
        $errores = $this->validar($datos, true);

        if ($errores !== []) {
            $this->mostrarFormulario(null, $datos, $errores);
            return;
        }

        $idMaterial = InventarioService::crearMaterial(
            $this->paraGuardar($datos),
            $this->decimal(($datos['existencia'] !== '') ? $datos['existencia'] : '0'),
            (int) Auth::id()
        );

        Flash::agregar('exito', "Material \"{$datos['nombre']}\" registrado.");
        $this->redirigir('/inventario/' . $idMaterial);
    }

    /* ===== Detalle, edición y baja ===== */

    /**
     * Muestra el detalle de un material con sus movimientos y los
     * formularios de entrada y salida.
     *
     * Recibe: $id, id del material (segmento de la ruta).
     * Devuelve: nada.
     */
    public function detalle(string $id): void {
        Auth::requireRol(self::ROLES_CONSULTA);

        $material = $this->materialVisible($this->idDeRuta($id));
        $esPintor = Auth::rol() === Auth::PINTOR;

        $this->vista('comun/material_detalle', [
            'material'    => $material,
            'movimientos' => (new MovimientoInventario())->listarPorMaterial(
                (int) $material['id_material']
            ),
            'esPintor'    => $esPintor,
            'destinos'    => $this->destinosDeSalida(),
            'destinoElegido' => $this->destinoDeConsulta(),
        ], 'panel');
    }

    /**
     * Muestra el formulario de edición de un material.
     *
     * Recibe: $id, id del material (segmento de la ruta).
     * Devuelve: nada.
     */
    public function editar(string $id): void {
        Auth::requireRol(self::ROLES_GESTION);

        $material = $this->buscarMaterial($this->idDeRuta($id));

        $datos = [
            'nombre'         => $material['nombre'],
            'categoria'      => $material['categoria'],
            'unidad'         => $material['unidad'],
            'minimo'         => (string) $material['minimo'],
            'costo_unitario' => (string) $material['costo_unitario'],
            'existencia'     => '',
        ];

        $this->mostrarFormulario($material, $datos, []);
    }

    /**
     * Guarda los cambios de un material (la existencia no se edita) y
     * revisa la alerta de mínimo (respuesta 6).
     *
     * Recibe: $id, id del material (segmento de la ruta).
     * Devuelve: nada.
     */
    public function actualizar(string $id): void {
        Auth::requireRol(self::ROLES_GESTION);

        $material = $this->buscarMaterial($this->idDeRuta($id));
        $idMaterial = (int) $material['id_material'];
        $datos = $this->leerFormulario(false);
        $errores = $this->validar($datos, false);

        if ($errores !== []) {
            $this->mostrarFormulario($material, $datos, $errores);
            return;
        }

        (new Material())->actualizar($idMaterial, $this->paraGuardar($datos));
        InventarioService::revisarAlerta($idMaterial, (int) Auth::id());

        Flash::agregar('exito', 'Los cambios del material se guardaron.');
        $this->redirigir('/inventario/' . $idMaterial);
    }

    /**
     * Da de baja o reactiva un material (respuesta 1).
     *
     * Recibe: $id, id del material; $_POST['accion'], "baja" o "reactivar".
     * Devuelve: nada.
     */
    public function cambiarEstado(string $id): void {
        Auth::requireRol(self::ROLES_GESTION);

        $material = $this->buscarMaterial($this->idDeRuta($id));
        $idMaterial = (int) $material['id_material'];
        $reactivar = $this->textoPost('accion') === 'reactivar';

        (new Material())->cambiarEstado($idMaterial, $reactivar);

        if ($reactivar) {
            InventarioService::revisarAlerta($idMaterial, (int) Auth::id());
            Flash::agregar('exito', "\"{$material['nombre']}\" se reactivó.");
        } else {
            Flash::agregar(
                'exito',
                "\"{$material['nombre']}\" se dio de baja. Sus movimientos se conservan."
            );
        }

        $this->redirigir('/inventario/' . $idMaterial);
    }

    /* ===== Movimientos (RF-38) ===== */

    /**
     * Registra una entrada (compra) o una salida (consumo en un proyecto o
     * una reparación) y actualiza la existencia.
     *
     * Recibe: $id, id del material; $_POST: tipo, cantidad, costo_unitario
     *         (entradas), destino (salidas: "PRY-id" o "REP-id") y nota.
     * Devuelve: nada (redirige al detalle del material).
     */
    public function registrarMovimiento(string $id): void {
        Auth::requireRol(self::ROLES_CONSULTA);

        $material = $this->materialVisible($this->idDeRuta($id));
        $idMaterial = (int) $material['id_material'];
        $volver = '/inventario/' . $idMaterial;
        $esPintor = Auth::rol() === Auth::PINTOR;

        $tipo = $this->textoPost('tipo');
        $cantidad = $this->textoPost('cantidad');
        $costo = $this->textoPost('costo_unitario');
        $nota = $this->textoPost('nota');
        $destino = $this->textoPost('destino');

        /* --- Validaciones --- */
        $error = null;
        $costoEntrada = null;
        $idProyecto = null;
        $idReparacion = null;

        if ((int) $material['activo'] !== 1) {
            $error = 'El material está dado de baja.';
        } elseif (!in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
            $error = 'Elige si es entrada o salida.';
        } elseif (!$this->numeroValido($cantidad, false)) {
            $error = 'Escribe una cantidad mayor a 0.';
        } elseif (mb_strlen($nota) > 255) {
            $error = 'La nota no puede pasar de 255 caracteres.';
        }

        // Entradas: el Carpintero y el Contador capturan el costo de compra;
        // el Pintor no cambia costos (RN-08), se usa el vigente
        if ($error === null && $tipo === 'ENTRADA' && !$esPintor) {
            if (!$this->numeroValido($costo, true)) {
                $error = 'Escribe el costo unitario de la compra (igual o mayor a $0.00).';
            } else {
                $costoEntrada = $this->decimal($costo);
            }
        }

        // Salidas: destino obligatorio entre los proyectos o reparaciones abiertos
        if ($error === null && $tipo === 'SALIDA') {
            $destinos = $this->destinosDeSalida();

            // El destino llega como "PRY-id" o "REP-id"
            $prefijo = substr($destino, 0, 4);
            $idDestino = substr($destino, 4);

            if ($prefijo === 'PRY-' && isset($destinos['PRY'][$idDestino])) {
                $idProyecto = (int) $idDestino;
            } elseif ($prefijo === 'REP-' && isset($destinos['REP'][$idDestino])) {
                $idReparacion = (int) $idDestino;
            } else {
                $error = 'Elige el proyecto o la reparación donde se usó el material.';
            }
        }

        if ($error !== null) {
            Flash::agregar('error', $error);
            $this->redirigir($volver);
        }

        try {
            InventarioService::registrarMovimiento(
                $idMaterial,
                $tipo,
                $this->decimal($cantidad),
                $costoEntrada,
                $idProyecto,
                $idReparacion,
                ($nota !== '') ? $nota : null,
                (int) Auth::id()
            );

            $texto = ($tipo === 'ENTRADA') ? 'Entrada registrada.' : 'Salida registrada.';
            Flash::agregar('exito', $texto);
        } catch (DomainException $excepcion) {
            Flash::agregar('error', $excepcion->getMessage());
        }

        $this->redirigir($volver);
    }

    /* ===== Faltantes (RF-41) ===== */

    /**
     * Muestra los materiales faltantes o por reemplazar: existencias bajas
     * y reportes de daño no resueltos con acción "Reemplazar" (respuesta 7).
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function faltantes(): void {
        Auth::requireRol([Auth::CONTADOR]);

        $this->vista('contador/faltantes', [
            'bajos'      => (new Material())->listarBajos(),
            'reemplazos' => (new ReporteDanio())->listarPorReemplazar(),
        ], 'panel');
    }

    /* ===== Funciones internas ===== */

    /**
     * Dibuja el formulario de material (nuevo o edición).
     *
     * Recibe: $material, datos guardados o null si es nuevo; $datos; $errores.
     * Devuelve: nada.
     */
    private function mostrarFormulario(?array $material, array $datos, array $errores): void {
        $this->vista('comun/material_formulario', [
            'material'   => $material,
            'datos'      => $datos,
            'errores'    => $errores,
            'categorias' => Material::CATEGORIAS,
            'unidades'   => Material::UNIDADES,
        ], 'panel');
    }

    /**
     * Lee del POST los campos del formulario de material.
     *
     * Recibe: $conExistencia, true en el alta (pide la existencia inicial).
     * Devuelve: valores capturados.
     */
    private function leerFormulario(bool $conExistencia): array {
        return [
            'nombre'         => $this->textoPost('nombre'),
            'categoria'      => $this->textoPost('categoria'),
            'unidad'         => $this->textoPost('unidad'),
            'minimo'         => $this->textoPost('minimo'),
            'costo_unitario' => $this->textoPost('costo_unitario'),
            'existencia'     => $conExistencia ? $this->textoPost('existencia') : '',
        ];
    }

    /**
     * Valida los datos del material.
     *
     * Recibe: $datos; $esAlta, true para validar también la existencia inicial.
     * Devuelve: errores por campo.
     */
    private function validar(array $datos, bool $esAlta): array {
        $errores = [];

        if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 100) {
            $errores['nombre'] = 'Escribe el nombre del material (máximo 100 caracteres).';
        }

        if (!isset(Material::CATEGORIAS[$datos['categoria']])) {
            $errores['categoria'] = 'Elige una categoría.';
        }

        if (!isset(Material::UNIDADES[$datos['unidad']])) {
            $errores['unidad'] = 'Elige una unidad.';
        }

        if (!$this->numeroValido($datos['minimo'], true)) {
            $errores['minimo'] = 'Escribe el mínimo (igual o mayor a 0).';
        }

        if (!$this->numeroValido($datos['costo_unitario'], true)) {
            $errores['costo_unitario'] = 'Escribe el costo unitario (igual o mayor a $0.00).';
        }

        $existenciaInvalida = $datos['existencia'] !== ''
            && !$this->numeroValido($datos['existencia'], true);

        if ($esAlta && $existenciaInvalida) {
            $errores['existencia'] = 'La existencia inicial debe ser igual o mayor a 0.';
        }

        return $errores;
    }

    /**
     * Arma los valores que se guardan del material.
     *
     * Recibe: $datos, valores validados.
     * Devuelve: arreglo para Material::crear o actualizar.
     */
    private function paraGuardar(array $datos): array {
        return [
            'nombre'         => $datos['nombre'],
            'categoria'      => $datos['categoria'],
            'unidad'         => $datos['unidad'],
            'minimo'         => $this->decimal($datos['minimo']),
            'costo_unitario' => $this->decimal($datos['costo_unitario']),
        ];
    }

    /**
     * Devuelve los proyectos y reparaciones abiertos a los que se puede dar
     * salida de material (respuestas 4 y 5): el Pintor solo ve los
     * proyectos donde tiene actividad de Pintura y ninguna reparación.
     *
     * Recibe: nada.
     * Devuelve: ['PRY' => [id => texto], 'REP' => [id => texto]].
     */
    private function destinosDeSalida(): array {
        $destinos = [
            'PRY' => [],
            'REP' => [],
        ];

        $idPintor = null;

        if (Auth::rol() === Auth::PINTOR) {
            $trabajador = (new Trabajador())->buscarPorUsuario((int) Auth::id());
            $idPintor = (int) ($trabajador['id_trabajador'] ?? 0);
        }

        foreach ((new Proyecto())->listarAbiertos($idPintor) as $proyecto) {
            $destinos['PRY'][$proyecto['id_proyecto']] = $proyecto['folio'] . ' · '
                . $proyecto['tipo_mueble'] . ' · ' . $proyecto['cliente_nombre'];
        }

        if ($idPintor === null) {
            foreach ((new Reparacion())->listarAbiertas() as $reparacion) {
                $destinos['REP'][$reparacion['id_reparacion']] = $reparacion['folio'] . ' · '
                    . $reparacion['descripcion_mueble'] . ' · ' . $reparacion['cliente_nombre'];
            }
        }

        return $destinos;
    }

    /**
     * Lee el destino precargado desde la pestaña "Materiales" del proyecto
     * (respuesta 8): ?proyecto=id.
     *
     * Recibe: nada (usa $_GET['proyecto']).
     * Devuelve: "PRY-id" o "".
     */
    private function destinoDeConsulta(): string {
        $proyecto = (string) ($_GET['proyecto'] ?? '');

        return ctype_digit($proyecto) ? 'PRY-' . $proyecto : '';
    }

    /**
     * Busca un material o responde 404.
     *
     * Recibe: $idMaterial.
     * Devuelve: datos del material.
     */
    private function buscarMaterial(int $idMaterial): array {
        $material = (new Material())->buscarPorId($idMaterial);

        if ($material === null) {
            View::error(404);
        }

        return $material;
    }

    /**
     * Busca un material que el usuario puede ver: el Pintor solo los
     * activos de Pintura, Solvente y Barniz (403 si no).
     *
     * Recibe: $idMaterial.
     * Devuelve: datos del material.
     */
    private function materialVisible(int $idMaterial): array {
        $material = $this->buscarMaterial($idMaterial);

        if (Auth::rol() === Auth::PINTOR) {
            $esDePintura = in_array($material['categoria'], Material::CATEGORIAS_PINTOR, true);

            if (!$esDePintura || (int) $material['activo'] !== 1) {
                View::error(403);
            }
        }

        return $material;
    }

    /**
     * Revisa que un texto sea un número dentro del límite de DECIMAL(10,2).
     *
     * Recibe: $valor; $permiteCero, true si 0 es válido.
     * Devuelve: true si es válido.
     */
    private function numeroValido(string $valor, bool $permiteCero): bool {
        if (!is_numeric($valor)) {
            return false;
        }

        // Se redondea a 2 decimales como se guarda (ej. 0.001 cuenta como 0)
        $numero = round((float) $valor, 2);

        if ($numero < 0 || $numero > InventarioService::MAX_CANTIDAD) {
            return false;
        }

        return $permiteCero ? true : $numero > 0;
    }

    /**
     * Da formato de 2 decimales a un número para guardarlo.
     *
     * Recibe: $valor, texto numérico.
     * Devuelve: texto como "12.50".
     */
    private function decimal(string $valor): string {
        return number_format(round((float) $valor, 2), 2, '.', '');
    }

}
