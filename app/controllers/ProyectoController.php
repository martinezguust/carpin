<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/ProyectoController.php
 * Pantallas del Carpintero para los proyectos:
 *   - tablero del taller (mockup 3);
 *   - lista con filtros y detalle (RF-23);
 *   - registro de proyecto con cuenta del cliente (CU-03,
 *     RF-15 a RF-17) y hoja de credenciales (RF-16);
 *   - avance normal y corrección de fase (HU-17, RF-20, RF-21).
 * =====================================================
 */

class ProyectoController extends Controller {

    /* ===== Listas fijas del formulario ===== */
    private const ACABADOS = ['Mate', 'Satinado', 'Brillante'];
    private const PRODUCTOS_FICHA = ['Barniz', 'Laca', 'Tinta', 'Pintura'];
    private const STATUS_PAGO = ['PENDIENTE', 'CON_ANTICIPO', 'PAGADO'];

    /* ===== Límites de las columnas numéricas ===== */
    private const MAX_MEDIDA = 99999.9;      // DECIMAL(6,1)
    private const MAX_MONTO = 99999999.99;   // DECIMAL(10,2)

    /* ===== Tablero y lista ===== */

    /**
     * Muestra el tablero del taller: indicadores y proyectos por fase.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function tablero(): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $proyectos = new Proyecto();
        $fases = (new Fase())->todas();
        unset($fases[Fase::ENTREGADO]);

        // Proyectos agrupados en las 9 columnas (respuesta 24)
        $columnas = [];

        foreach ($fases as $idFase => $fase) {
            $columnas[$idFase] = [];
        }

        foreach ($proyectos->listarEnProduccion() as $proyecto) {
            $columnas[(int) $proyecto['id_fase']][] = $proyecto;
        }

        // Semana actual de lunes a domingo (respuesta 15)
        $lunes = date('Y-m-d', strtotime('monday this week'));
        $domingo = date('Y-m-d', strtotime('sunday this week'));

        $this->vista('carpintero/tablero', [
            'fases'            => $fases,
            'columnas'         => $columnas,
            'totalActivos'     => $proyectos->contarActivos(),
            'entregasSemana'   => $proyectos->contarEntregasEntre($lunes, $domingo),
        ], 'panel');
    }

    /**
     * Muestra la lista de proyectos con filtros por fase, cliente, status
     * de pago y rango de fechas de entrega (RF-23, respuesta 9).
     *
     * Recibe: nada (usa $_GET: fase, cliente, status, desde, hasta).
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireRol([Auth::CARPINTERO]);

        // Valores recibidos; los que no son válidos se ignoran
        $fase = (string) ($_GET['fase'] ?? '');
        $cliente = (string) ($_GET['cliente'] ?? '');
        $status = (string) ($_GET['status'] ?? '');
        $desde = (string) ($_GET['desde'] ?? '');
        $hasta = (string) ($_GET['hasta'] ?? '');

        $filtros = [
            'id_fase'     => ctype_digit($fase) ? (int) $fase : 0,
            'id_cliente'  => ctype_digit($cliente) ? (int) $cliente : 0,
            'status_pago' => in_array($status, self::STATUS_PAGO, true) ? $status : '',
            'desde'       => $this->fechaValida($desde) ? $desde : '',
            'hasta'       => $this->fechaValida($hasta) ? $hasta : '',
        ];

        $this->vista('carpintero/proyectos', [
            'proyectos' => (new Proyecto())->listar($filtros),
            'filtros'   => $filtros,
            'fases'     => (new Fase())->todas(),
            'clientes'  => (new Cliente())->listarTodos(),
        ], 'panel');
    }

    /* ===== Registro (CU-03) ===== */

    /**
     * Muestra el formulario de nuevo proyecto. Si viene de "Convertir en
     * proyecto", precarga los datos de la solicitud de cotización (RF-14,
     * respuesta 13 de la Fase 2).
     *
     * Recibe: nada (usa $_GET['cotizacion'], opcional).
     * Devuelve: nada.
     */
    public function nuevo(): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $datos = $this->datosVacios();
        $idCotizacion = (string) ($_GET['cotizacion'] ?? '');

        if (ctype_digit($idCotizacion)) {
            $solicitud = $this->solicitudConvertible((int) $idCotizacion);

            if ($solicitud === null) {
                Flash::agregar('error', 'Esa solicitud de cotización ya no se puede convertir.');
                $this->redirigir('/cotizaciones');
            }

            $datos = array_merge($datos, $this->precargaDeSolicitud($solicitud));
        }

        $this->mostrarFormulario($datos, []);
    }

    /**
     * Registra el proyecto y, si el cliente es nuevo, su cuenta (CU-03).
     * Todo se guarda en una sola transacción (SRS 12.5).
     *
     * Recibe: nada (usa $_POST con los campos del formulario).
     * Devuelve: nada (redirige a la hoja de credenciales o al detalle).
     */
    public function guardar(): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $datos = $this->leerFormulario();
        $errores = $this->validarFormulario($datos);

        if ($errores !== []) {
            $this->mostrarFormulario($datos, $errores);
            return;
        }

        $esClienteNuevo = $datos['tipo_cliente'] === 'nuevo';
        $precio = round((float) $datos['precio'], 2);
        $anticipo = ($datos['anticipo'] === '') ? 0.0 : round((float) $datos['anticipo'], 2);
        $hoy = date('Y-m-d');
        $idUsuarioActual = (int) Auth::id();
        $idSolicitud = ($datos['id_solicitud'] !== '') ? (int) $datos['id_solicitud'] : null;

        $db = Database::get();

        try {
            $db->beginTransaction();

            // 1. Cuenta y datos del cliente nuevo (RF-16)
            if ($esClienteNuevo) {
                $idCliente = (new Cliente())->crearConCuenta($datos);
            } else {
                $idCliente = (int) $datos['id_cliente'];
            }

            // 2. Proyecto en "Cotizado" con 11.11 % (RF-17)
            $faseInicial = (new Fase())->buscarPorId(Fase::COTIZADO);
            $folio = FolioService::siguiente('PRY');

            $idProyecto = (new Proyecto())->crear([
                'folio'               => $folio,
                'id_cliente'          => $idCliente,
                'id_solicitud'        => $idSolicitud,
                'tipo_mueble'         => $datos['tipo_mueble'],
                'descripcion'         => $this->nuloSiVacio($datos['descripcion']),
                'alto_cm'             => $this->nuloSiVacio($datos['alto_cm']),
                'ancho_cm'            => $this->nuloSiVacio($datos['ancho_cm']),
                'fondo_cm'            => $this->nuloSiVacio($datos['fondo_cm']),
                'madera'              => $datos['madera'],
                'acabado'             => $this->nuloSiVacio($datos['acabado']),
                'color'               => $this->nuloSiVacio($datos['color']),
                'granulado_lija'      => $this->nuloSiVacio($datos['granulado_lija']),
                'ficha_color'         => $this->armarFichaColor($datos),
                'fecha_registro'      => $hoy,
                'fecha_entrega'       => $datos['fecha_entrega'],
                'fecha_limite_pago'   => $datos['fecha_limite_pago'],
                'precio'              => number_format($precio, 2, '.', ''),
                'id_fase'             => Fase::COTIZADO,
                'porcentaje_avance'   => (string) $faseInicial['porcentaje'],
                'status_pago'         => PagoService::status($precio, $anticipo),
                'id_usuario_registro' => $idUsuarioActual,
            ]);

            // 3. Historial: alta del proyecto en la primera fase (RF-22)
            (new HistorialFase())->registrar(
                $idProyecto,
                null,
                Fase::COTIZADO,
                $idUsuarioActual,
                null
            );

            // 4. Anticipo como primer pago (SRS 12.5)
            if ($anticipo > 0) {
                (new PagoCliente())->registrarDeProyecto(
                    $idProyecto,
                    number_format($anticipo, 2, '.', ''),
                    $datos['metodo_anticipo'],
                    $hoy,
                    $idUsuarioActual
                );
            }

            // 5. Solicitud de cotización convertida en proyecto (RF-14)
            if ($idSolicitud !== null) {
                (new SolicitudCotizacion())->cambiarEstado($idSolicitud, 'CONVERTIDA');
            }

            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $error;
        }

        if ($esClienteNuevo) {
            // La contraseña se muestra una sola vez en la hoja de credenciales
            $_SESSION['credenciales']['PRY' . $idProyecto] = [
                'username' => $datos['cliente_username'],
                'password' => $datos['cliente_password'],
            ];

            $this->redirigir('/proyectos/' . $idProyecto . '/credenciales');
        }

        Flash::agregar('exito', "Proyecto {$folio} registrado.");
        $this->redirigir('/proyectos/' . $idProyecto);
    }

    /**
     * Muestra la hoja imprimible con las credenciales del cliente nuevo
     * (RF-16, respuesta 8). La contraseña solo se ve una vez.
     *
     * Recibe: $id, id del proyecto (segmento de la ruta).
     * Devuelve: nada.
     */
    public function credenciales(string $id): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $idProyecto = $this->idDeRuta($id);
        $proyecto = $this->buscarProyecto($idProyecto);
        $llave = 'PRY' . $idProyecto;
        $credenciales = $_SESSION['credenciales'][$llave] ?? null;

        // Se borra de la sesión: si se recarga la página ya no se muestra
        unset($_SESSION['credenciales'][$llave]);

        $this->vista('carpintero/credenciales', [
            'folio'         => $proyecto['folio'],
            'mueble'        => $proyecto['tipo_mueble'],
            'clienteNombre' => $proyecto['cliente_nombre'],
            'tipoPedido'    => 'Proyecto',
            'urlDetalle'    => '/proyectos/' . $idProyecto,
            'credenciales'  => $credenciales,
            'negocio'       => (new Configuracion())->obtener(),
        ], 'panel');
    }

    /* ===== Detalle y fases ===== */

    /**
     * Muestra el detalle del proyecto con las pestañas Datos, Actividades
     * e Historial (SRS 10.3).
     *
     * Recibe: $id, id del proyecto (segmento de la ruta).
     * Devuelve: nada.
     */
    public function detalle(string $id): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $proyecto = $this->buscarProyecto($this->idDeRuta($id));
        $idProyecto = (int) $proyecto['id_proyecto'];
        $trabajadores = new Trabajador();
        $siguienteFase = FaseService::siguienteFasePermitida($proyecto, Auth::usuario());

        // Trabajadores activos que pueden recibir cada tipo de actividad
        $trabajadoresPorTipo = [];

        foreach (Actividad::ROL_POR_TIPO as $tipo => $rol) {
            $trabajadoresPorTipo[$tipo] = $trabajadores->activosPorRol($rol);
        }

        $this->vista('carpintero/proyecto_detalle', [
            'proyecto'            => $proyecto,
            'saldo'               => PagoService::saldo($proyecto['precio'], $proyecto['pagado']),
            'fases'               => (new Fase())->todas(),
            'siguienteFase'       => $siguienteFase,
            'actividades'         => (new Actividad())->listarPorProyecto($idProyecto),
            'historial'           => (new HistorialFase())->listarPorProyecto($idProyecto),
            'trabajadoresPorTipo' => $trabajadoresPorTipo,
            'rolPorTipo'          => Actividad::ROL_POR_TIPO,
            'capas'               => (new CapaPintura())->listarPorProyecto($idProyecto),
            'secados'             => (new Secado())->listarPorProyecto($idProyecto),
        ], 'panel');
    }

    /**
     * Avance normal de fase (RF-20, RN-02, SRS 5.4): el Carpintero
     * 1→2→3→4 y 9→10, el Lijador 4→5→6 y el Pintor 6→7→8→9.
     * Si no le corresponde responde 403 (CU-05, A1).
     *
     * Recibe: $id, id del proyecto (segmento de la ruta);
     *         $_POST: confirmar_saldo y confirmar_secado (opcionales).
     * Devuelve: nada (redirige al detalle o a "Mis pendientes").
     */
    public function avanzar(string $id): void {
        Auth::requireRol([Auth::CARPINTERO, Auth::LIJADOR, Auth::PINTOR]);

        $proyecto = $this->buscarProyecto($this->idDeRuta($id));

        if (FaseService::siguienteFasePermitida($proyecto, Auth::usuario()) === null) {
            View::error(403);
        }

        try {
            $faseNueva = FaseService::avanzar($proyecto, Auth::usuario(), [
                'saldo'  => $this->textoPost('confirmar_saldo') === '1',
                'secado' => $this->textoPost('confirmar_secado') === '1',
            ]);

            // Mensaje del diagrama 12.4 al enviar a pintura (respuesta 19)
            if ((int) $faseNueva['id_fase'] === Fase::LISTO_PARA_PINTURA) {
                Flash::agregar('exito', 'Proyecto enviado a pintura.');
            } else {
                Flash::agregar('exito', "El proyecto pasó a la fase \"{$faseNueva['nombre']}\".");
            }
        } catch (DomainException $error) {
            Flash::agregar('error', $error->getMessage());
        }

        if (Auth::rol() === Auth::CARPINTERO) {
            $this->redirigir('/proyectos/' . $proyecto['id_proyecto']);
        }

        $this->redirigir('/mis-pendientes');
    }

    /**
     * Corrige la fase del proyecto con motivo obligatorio (RF-21, RN-03).
     *
     * Recibe: $id, id del proyecto (segmento de la ruta);
     *         $_POST: id_fase, motivo, confirmar_saldo.
     * Devuelve: nada (redirige al detalle).
     */
    public function corregir(string $id): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $proyecto = $this->buscarProyecto($this->idDeRuta($id));
        $idFase = ctype_digit($this->textoPost('id_fase')) ? (int) $this->textoPost('id_fase') : 0;

        try {
            $faseNueva = FaseService::corregir(
                $proyecto,
                $idFase,
                $this->textoPost('motivo'),
                Auth::usuario(),
                $this->textoPost('confirmar_saldo') === '1'
            );
            Flash::agregar(
                'exito',
                "Fase corregida: el proyecto está en \"{$faseNueva['nombre']}\"."
            );
        } catch (DomainException $error) {
            Flash::agregar('error', $error->getMessage());
        }

        $this->redirigir('/proyectos/' . $proyecto['id_proyecto'] . '#historial');
    }

    /* ===== Funciones internas ===== */

    /**
     * Busca un proyecto activo o responde 404.
     *
     * Recibe: $idProyecto.
     * Devuelve: datos completos del proyecto.
     */
    private function buscarProyecto(int $idProyecto): array {
        $proyecto = (new Proyecto())->buscarPorId($idProyecto);

        if ($proyecto === null) {
            View::error(404);
        }

        return $proyecto;
    }

    /**
     * Dibuja el formulario de nuevo proyecto.
     *
     * Recibe: $datos, valores del formulario; $errores, errores por campo.
     * Devuelve: nada.
     */
    private function mostrarFormulario(array $datos, array $errores): void {
        $this->vista('carpintero/proyecto_nuevo', [
            'datos'          => $datos,
            'errores'        => $errores,
            'clientes'       => (new Cliente())->listarActivos(),
            'acabados'       => self::ACABADOS,
            'productosFicha' => self::PRODUCTOS_FICHA,
            'metodos'        => PagoCliente::METODOS,
            'hoy'            => date('Y-m-d'),
        ], 'panel');
    }

    /**
     * Devuelve los valores iniciales (vacíos) del formulario.
     *
     * Recibe: nada.
     * Devuelve: arreglo con todos los campos del formulario.
     */
    private function datosVacios(): array {
        $campos = [
            'tipo_cliente', 'id_cliente', 'cliente_nombre', 'cliente_telefono',
            'cliente_correo', 'cliente_direccion', 'cliente_username', 'cliente_password',
            'tipo_mueble', 'descripcion', 'alto_cm', 'ancho_cm', 'fondo_cm', 'madera',
            'acabado', 'color', 'granulado_lija', 'ficha_producto', 'ficha_notas',
            'fecha_entrega', 'fecha_limite_pago', 'precio', 'anticipo', 'metodo_anticipo',
            'id_solicitud',
        ];

        $datos = array_fill_keys($campos, '');
        $datos['tipo_cliente'] = 'nuevo';

        return $datos;
    }

    /**
     * Lee del POST todos los campos del formulario.
     *
     * Recibe: nada.
     * Devuelve: arreglo con los valores capturados (la contraseña sin recortar).
     */
    private function leerFormulario(): array {
        $datos = [];

        foreach (array_keys($this->datosVacios()) as $campo) {
            $datos[$campo] = $this->textoPost($campo);
        }

        $datos['cliente_password'] = (string) ($_POST['cliente_password'] ?? '');

        if ($datos['tipo_cliente'] !== 'existente') {
            $datos['tipo_cliente'] = 'nuevo';
        }

        return $datos;
    }

    /**
     * Valida el formulario de nuevo proyecto (CU-03 y SRS 5.4).
     *
     * Recibe: $datos, valores capturados.
     * Devuelve: errores por campo (vacío si todo es válido).
     */
    private function validarFormulario(array $datos): array {
        $errores = [];
        $hoy = date('Y-m-d');

        /* --- Cliente --- */
        if ($datos['tipo_cliente'] === 'existente') {
            $cliente = ctype_digit($datos['id_cliente'])
                ? (new Cliente())->buscarPorId((int) $datos['id_cliente'])
                : null;

            if ($cliente === null || (int) $cliente['cuenta_activa'] !== 1) {
                $errores['id_cliente'] = 'Elige un cliente de la lista.';
            }
        } else {
            $errores = array_merge($errores, (new Cliente())->validarNuevo($datos));
        }

        /* --- Solicitud de cotización de origen (opcional) --- */
        $solicitudValida = $datos['id_solicitud'] === ''
            || (ctype_digit($datos['id_solicitud'])
                && $this->solicitudConvertible((int) $datos['id_solicitud']) !== null);

        if (!$solicitudValida) {
            $errores['id_solicitud'] = 'La solicitud de cotización ya no se puede convertir.';
        }

        /* --- Mueble --- */
        if ($datos['tipo_mueble'] === '' || mb_strlen($datos['tipo_mueble']) > 60) {
            $errores['tipo_mueble'] = 'Escribe el tipo de mueble (máximo 60 caracteres).';
        }

        if ($datos['madera'] === '' || mb_strlen($datos['madera']) > 60) {
            $errores['madera'] = 'Escribe el tipo de madera (máximo 60 caracteres).';
        }

        if (mb_strlen($datos['descripcion']) > 2000) {
            $errores['descripcion'] = 'La descripción no puede pasar de 2000 caracteres.';
        }

        $medidas = [
            'alto_cm'  => 'alto',
            'ancho_cm' => 'ancho',
            'fondo_cm' => 'fondo',
        ];

        foreach ($medidas as $campo => $nombre) {
            $valor = $datos[$campo];

            if ($valor === '') {
                continue;
            }

            if (!is_numeric($valor) || (float) $valor <= 0 || (float) $valor > self::MAX_MEDIDA) {
                $errores[$campo] = "El {$nombre} debe ser un número mayor a 0.";
            }
        }

        if ($datos['acabado'] !== '' && !in_array($datos['acabado'], self::ACABADOS, true)) {
            $errores['acabado'] = 'Elige un acabado de la lista.';
        }

        if (mb_strlen($datos['color']) > 60) {
            $errores['color'] = 'El color no puede pasar de 60 caracteres.';
        }

        if (mb_strlen($datos['granulado_lija']) > 40) {
            $errores['granulado_lija'] = 'El granulado no puede pasar de 40 caracteres.';
        }

        $productoValido = in_array($datos['ficha_producto'], self::PRODUCTOS_FICHA, true);

        if ($datos['ficha_producto'] !== '' && !$productoValido) {
            $errores['ficha_producto'] = 'Elige un tipo de producto de la lista.';
        }

        if (mb_strlen($datos['ficha_notas']) > 1000) {
            $errores['ficha_notas'] = 'Las notas no pueden pasar de 1000 caracteres.';
        }

        /* --- Fechas (CU-03, A3 y respuesta 7) --- */
        if (!$this->fechaValida($datos['fecha_entrega'])) {
            $errores['fecha_entrega'] = 'Elige la fecha de entrega.';
        } elseif ($datos['fecha_entrega'] < $hoy) {
            $errores['fecha_entrega'] = 'La fecha de entrega no puede ser anterior a hoy.';
        }

        if (!$this->fechaValida($datos['fecha_limite_pago'])) {
            $errores['fecha_limite_pago'] = 'Elige la fecha límite de pago.';
        } elseif ($datos['fecha_limite_pago'] < $hoy) {
            $errores['fecha_limite_pago'] = 'La fecha límite de pago no puede ser anterior a hoy.';
        }

        /* --- Precio y anticipo (CU-03, A2) --- */
        $precioValido = is_numeric($datos['precio'])
            && (float) $datos['precio'] > 0
            && (float) $datos['precio'] <= self::MAX_MONTO;

        if (!$precioValido) {
            $errores['precio'] = 'Escribe un precio mayor a $0.00.';
        }

        $anticipo = $datos['anticipo'];

        if ($anticipo !== '' && (!is_numeric($anticipo) || (float) $anticipo < 0)) {
            $errores['anticipo'] = 'El anticipo debe ser un número igual o mayor a $0.00.';
        } elseif ($precioValido && $anticipo !== ''
            && PagoService::aCentavos($anticipo) > PagoService::aCentavos($datos['precio'])) {
            $errores['anticipo'] = 'El anticipo no puede ser mayor al precio.';
        }

        // Método del anticipo: obligatorio solo si el anticipo es mayor a 0 (SRS 5.4)
        $hayAnticipo = $anticipo !== '' && is_numeric($anticipo) && (float) $anticipo > 0;

        if ($hayAnticipo && !isset(PagoCliente::METODOS[$datos['metodo_anticipo']])) {
            $errores['metodo_anticipo'] = 'Elige el método del anticipo.';
        }

        return $errores;
    }

    /**
     * Busca una solicitud de cotización que todavía se pueda convertir en
     * proyecto (estado Nueva o Contactado).
     *
     * Recibe: $idSolicitud.
     * Devuelve: datos de la solicitud, o null si no existe o ya se cerró.
     */
    private function solicitudConvertible(int $idSolicitud): ?array {
        $solicitud = (new SolicitudCotizacion())->buscarPorId($idSolicitud);

        if ($solicitud === null) {
            return null;
        }

        if (!in_array($solicitud['estado'], SolicitudCotizacion::ESTADOS_ABIERTOS, true)) {
            return null;
        }

        return $solicitud;
    }

    /**
     * Arma los valores que se precargan al convertir una solicitud
     * (respuesta 13): nombre, teléfono y correo para el cliente nuevo; el
     * producto como tipo de mueble; la descripción y las medidas juntas.
     *
     * Recibe: $solicitud, datos de la solicitud de cotización.
     * Devuelve: valores del formulario a precargar.
     */
    private function precargaDeSolicitud(array $solicitud): array {
        $descripcion = $solicitud['descripcion'];

        if ($solicitud['medidas'] !== null && $solicitud['medidas'] !== '') {
            $descripcion .= "\nMedidas: " . $solicitud['medidas'];
        }

        return [
            'id_solicitud'     => (string) $solicitud['id_solicitud'],
            'tipo_cliente'     => 'nuevo',
            'cliente_nombre'   => $solicitud['nombre'],
            'cliente_telefono' => $solicitud['telefono'],
            'cliente_correo'   => (string) $solicitud['correo'],
            'tipo_mueble'      => mb_substr((string) $solicitud['producto_nombre'], 0, 60),
            'descripcion'      => $descripcion,
        ];
    }

    /**
     * Combina el tipo de producto y las notas de la ficha de color con el
     * formato "Producto: Barniz | Notas: …" (SRS 5.4).
     *
     * Recibe: $datos, con ficha_producto y ficha_notas.
     * Devuelve: texto combinado, o null si ambos están vacíos.
     */
    private function armarFichaColor(array $datos): ?string {
        $partes = [];

        if ($datos['ficha_producto'] !== '') {
            $partes[] = 'Producto: ' . $datos['ficha_producto'];
        }

        if ($datos['ficha_notas'] !== '') {
            $partes[] = 'Notas: ' . $datos['ficha_notas'];
        }

        return ($partes === []) ? null : implode(' | ', $partes);
    }

    /**
     * Convierte un texto vacío en null (columnas opcionales).
     *
     * Recibe: $texto.
     * Devuelve: el mismo texto, o null si estaba vacío.
     */
    private function nuloSiVacio(string $texto): ?string {
        return ($texto === '') ? null : $texto;
    }

}
