<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/ReparacionController.php
 * Reparaciones del Carpintero (HU-26, CU-08, RF-33 a RF-35,
 * RN-05): registro con la cuenta del dueño y de 1 a 6 fotos
 * de "antes", lista, detalle, fotos de "después" y cambio de
 * estado Recibido → En reparación → Lista para entrega →
 * Entregada.
 * =====================================================
 */

class ReparacionController extends Controller {

    /* ===== Límite de las columnas de dinero (DECIMAL(10,2)) ===== */
    private const MAX_MONTO = 99999999.99;

    /* ===== Lista y registro ===== */

    /**
     * Muestra la lista de reparaciones con filtro por estado.
     *
     * Recibe: nada (usa $_GET['estado'], opcional).
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $estado = (string) ($_GET['estado'] ?? '');

        if (!isset(Reparacion::ESTADOS[$estado])) {
            $estado = '';
        }

        $this->vista('carpintero/reparaciones', [
            'reparaciones' => (new Reparacion())->listar($estado),
            'estado'       => $estado,
            'estados'      => Reparacion::ESTADOS,
        ], 'panel');
    }

    /**
     * Muestra el formulario de nueva reparación.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function nueva(): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $this->mostrarFormulario($this->datosVacios(), []);
    }

    /**
     * Registra la reparación con sus fotos y, si el dueño es nuevo, su
     * cuenta (CU-08). Las fotos inválidas se rechazan y se guardan las
     * válidas; debe quedar al menos una (respuesta 15 de la Fase 2).
     *
     * Recibe: nada (usa $_POST y $_FILES['fotos']).
     * Devuelve: nada (redirige a la hoja de credenciales o al detalle).
     */
    public function guardar(): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $datos = $this->leerFormulario();
        $errores = $this->validarFormulario($datos);
        $archivos = $this->archivosSubidos('fotos');

        if ($archivos === []) {
            $errores['fotos'] = 'Sube al menos una foto del mueble ("antes").';
        } elseif (count($archivos) > ImagenReparacion::MAXIMO) {
            $errores['fotos'] = 'Puedes subir máximo ' . ImagenReparacion::MAXIMO . ' fotos.';
        }

        if ($errores !== []) {
            $this->mostrarFormulario($datos, $errores);
            return;
        }

        // Fotos: se guardan las válidas (CU-08, A1)
        $rechazadas = [];
        $rutas = $this->guardarFotos($archivos, $rechazadas);

        if ($rutas === []) {
            $errores['fotos'] = 'Ninguna foto es válida. ' . implode(' ', $rechazadas);
            $this->mostrarFormulario($datos, $errores);
            return;
        }

        $idReparacion = $this->registrar($datos, $rutas);
        $folio = (new Reparacion())->buscarPorId($idReparacion)['folio'];

        if ($rechazadas !== []) {
            Flash::agregar('aviso', 'Algunas fotos se rechazaron: ' . implode(' ', $rechazadas));
        }

        if ($datos['tipo_cliente'] === 'nuevo') {
            // La contraseña se muestra una sola vez en la hoja de credenciales
            $_SESSION['credenciales']['REP' . $idReparacion] = [
                'username' => $datos['cliente_username'],
                'password' => $datos['cliente_password'],
            ];

            $this->redirigir('/reparaciones/' . $idReparacion . '/credenciales');
        }

        Flash::agregar('exito', "Reparación {$folio} registrada.");
        $this->redirigir('/reparaciones/' . $idReparacion);
    }

    /**
     * Muestra la hoja imprimible con las credenciales del dueño nuevo.
     * La contraseña solo se ve una vez.
     *
     * Recibe: $id, id de la reparación (segmento de la ruta).
     * Devuelve: nada.
     */
    public function credenciales(string $id): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $reparacion = $this->buscarReparacion($this->idDeRuta($id));
        $llave = 'REP' . $reparacion['id_reparacion'];
        $credenciales = $_SESSION['credenciales'][$llave] ?? null;

        // Se borra de la sesión: si se recarga la página ya no se muestra
        unset($_SESSION['credenciales'][$llave]);

        $this->vista('carpintero/credenciales', [
            'folio'         => $reparacion['folio'],
            'mueble'        => $reparacion['descripcion_mueble'],
            'clienteNombre' => $reparacion['cliente_nombre'],
            'tipoPedido'    => 'Reparación',
            'urlDetalle'    => '/reparaciones/' . $reparacion['id_reparacion'],
            'credenciales'  => $credenciales,
            'negocio'       => (new Configuracion())->obtener(),
        ], 'panel');
    }

    /* ===== Detalle, estado y fotos ===== */

    /**
     * Muestra el detalle de la reparación con sus fotos y su estado.
     *
     * Recibe: $id, id de la reparación (segmento de la ruta).
     * Devuelve: nada.
     */
    public function detalle(string $id): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $reparacion = $this->buscarReparacion($this->idDeRuta($id));
        $idReparacion = (int) $reparacion['id_reparacion'];
        $imagenes = (new ImagenReparacion())->listarPorReparacion($idReparacion);

        $this->vista('carpintero/reparacion_detalle', [
            'reparacion'      => $reparacion,
            'imagenes'        => $imagenes,
            'saldo'           => PagoService::saldo($reparacion['costo'], $reparacion['pagado']),
            'siguienteEstado' => Reparacion::siguienteEstado($reparacion['estado']),
            'estados'         => Reparacion::ESTADOS,
            'tamanos'         => Reparacion::TAMANOS,
            'lugaresLibres'   => ImagenReparacion::MAXIMO - count($imagenes),
        ], 'panel');
    }

    /**
     * Pasa la reparación a su siguiente estado (RF-35, RN-05).
     *
     * Recibe: $id, id de la reparación (segmento de la ruta);
     *         $_POST['confirmar_saldo'], opcional.
     * Devuelve: nada (redirige al detalle).
     */
    public function avanzarEstado(string $id): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $reparacion = $this->buscarReparacion($this->idDeRuta($id));

        try {
            $estado = FaseService::avanzarReparacion(
                $reparacion,
                Auth::usuario(),
                $this->textoPost('confirmar_saldo') === '1'
            );
            Flash::agregar(
                'exito',
                'La reparación pasó a "' . Reparacion::ESTADOS[$estado]['nombre'] . '".'
            );
        } catch (DomainException $error) {
            Flash::agregar('error', $error->getMessage());
        }

        $this->redirigir('/reparaciones/' . $reparacion['id_reparacion']);
    }

    /**
     * Sube fotos de "después" sin pasar de 6 fotos en total
     * (respuesta 33 de la Fase 1).
     *
     * Recibe: $id, id de la reparación (segmento de la ruta);
     *         $_FILES['fotos'].
     * Devuelve: nada (redirige al detalle).
     */
    public function subirFotos(string $id): void {
        Auth::requireRol([Auth::CARPINTERO]);

        $reparacion = $this->buscarReparacion($this->idDeRuta($id));
        $idReparacion = (int) $reparacion['id_reparacion'];
        $volver = '/reparaciones/' . $idReparacion;
        $imagenes = new ImagenReparacion();
        $libres = ImagenReparacion::MAXIMO - $imagenes->contar($idReparacion);
        $archivos = $this->archivosSubidos('fotos');

        if ($archivos === []) {
            Flash::agregar('error', 'Elige al menos una foto.');
            $this->redirigir($volver);
        }

        if (count($archivos) > $libres) {
            Flash::agregar(
                'error',
                "Solo puedes subir {$libres} foto(s) más: el máximo es "
                . ImagenReparacion::MAXIMO . ' por reparación.'
            );
            $this->redirigir($volver);
        }

        $rechazadas = [];

        foreach ($this->guardarFotos($archivos, $rechazadas) as $ruta) {
            $imagenes->crear($idReparacion, $ruta, 'DESPUES');
        }

        if ($rechazadas !== []) {
            Flash::agregar('aviso', 'Algunas fotos se rechazaron: ' . implode(' ', $rechazadas));
        }

        if (count($rechazadas) < count($archivos)) {
            Flash::agregar('exito', 'Fotos de "después" agregadas.');
        }

        $this->redirigir($volver);
    }

    /* ===== Funciones internas ===== */

    /**
     * Guarda la reparación, la cuenta del dueño nuevo, las fotos y el
     * anticipo en una sola transacción. Si algo falla, borra las fotos.
     *
     * Recibe: $datos, valores validados; $rutas, fotos ya subidas.
     * Devuelve: id de la reparación creada.
     */
    private function registrar(array $datos, array $rutas): int {
        $costo = round((float) $datos['costo'], 2);
        $anticipo = ($datos['anticipo'] === '') ? 0.0 : round((float) $datos['anticipo'], 2);
        $hoy = date('Y-m-d');
        $idUsuarioActual = (int) Auth::id();

        $db = Database::get();

        try {
            $db->beginTransaction();

            if ($datos['tipo_cliente'] === 'nuevo') {
                $idCliente = (new Cliente())->crearConCuenta($datos);
            } else {
                $idCliente = (int) $datos['id_cliente'];
            }

            $idReparacion = (new Reparacion())->crear([
                'folio'               => FolioService::siguiente('REP'),
                'id_cliente'          => $idCliente,
                'descripcion_mueble'  => $datos['descripcion_mueble'],
                'tamano'              => $datos['tamano'],
                'tipo_reparacion'     => $datos['tipo_reparacion'],
                'descripcion_danio'   => ($datos['descripcion_danio'] !== '')
                    ? $datos['descripcion_danio']
                    : null,
                'costo'               => number_format($costo, 2, '.', ''),
                'fecha_recepcion'     => $hoy,
                'fecha_estimada'      => $datos['fecha_estimada'],
                'fecha_limite_pago'   => $datos['fecha_limite_pago'],
                'status_pago'         => PagoService::status($costo, $anticipo),
                'id_usuario_registro' => $idUsuarioActual,
            ]);

            $imagenes = new ImagenReparacion();

            foreach ($rutas as $ruta) {
                $imagenes->crear($idReparacion, $ruta, 'ANTES');
            }

            if ($anticipo > 0) {
                (new PagoCliente())->registrarDeReparacion(
                    $idReparacion,
                    number_format($anticipo, 2, '.', ''),
                    $datos['metodo_anticipo'],
                    $hoy,
                    $idUsuarioActual
                );
            }

            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            // Las fotos ya subidas se borran para no dejar archivos sueltos
            foreach ($rutas as $ruta) {
                $archivo = RAIZ . '/public/' . $ruta;

                if (is_file($archivo)) {
                    unlink($archivo);
                }
            }

            throw $error;
        }

        return $idReparacion;
    }

    /**
     * Sube cada foto con Upload (JPG, PNG o WEBP, máximo 5 MB). Las que
     * no son válidas se anotan en $rechazadas.
     *
     * Recibe: $archivos, lista de archivos de $_FILES;
     *         $rechazadas, lista donde se anotan los rechazos.
     * Devuelve: rutas de las fotos guardadas.
     */
    private function guardarFotos(array $archivos, array &$rechazadas): array {
        $rutas = [];

        foreach ($archivos as $archivo) {
            try {
                $rutas[] = Upload::guardar($archivo, 'reparaciones', Upload::IMAGENES);
            } catch (RuntimeException $error) {
                $rechazadas[] = '"' . $archivo['name'] . '": ' . $error->getMessage();
            }
        }

        return $rutas;
    }

    /**
     * Convierte un campo de archivos múltiples de $_FILES en una lista de
     * archivos individuales, sin los espacios vacíos.
     *
     * Recibe: $campo, nombre del campo (ej. "fotos").
     * Devuelve: lista con name, type, tmp_name, error y size de cada archivo.
     */
    private function archivosSubidos(string $campo): array {
        $entrada = $_FILES[$campo] ?? null;

        if ($entrada === null || !is_array($entrada['name'] ?? null)) {
            return [];
        }

        $archivos = [];

        foreach (array_keys($entrada['name']) as $indice) {
            if ($entrada['error'][$indice] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $archivos[] = [
                'name'     => $entrada['name'][$indice],
                'type'     => $entrada['type'][$indice],
                'tmp_name' => $entrada['tmp_name'][$indice],
                'error'    => $entrada['error'][$indice],
                'size'     => $entrada['size'][$indice],
            ];
        }

        return $archivos;
    }

    /**
     * Dibuja el formulario de nueva reparación.
     *
     * Recibe: $datos, valores del formulario; $errores, errores por campo.
     * Devuelve: nada.
     */
    private function mostrarFormulario(array $datos, array $errores): void {
        $this->vista('carpintero/reparacion_nueva', [
            'datos'    => $datos,
            'errores'  => $errores,
            'clientes' => (new Cliente())->listarActivos(),
            'tamanos'  => Reparacion::TAMANOS,
            'metodos'  => PagoCliente::METODOS,
            'hoy'      => date('Y-m-d'),
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
            'descripcion_mueble', 'tamano', 'tipo_reparacion', 'descripcion_danio', 'costo',
            'fecha_estimada', 'fecha_limite_pago', 'anticipo', 'metodo_anticipo',
        ];

        $datos = array_fill_keys($campos, '');
        $datos['tipo_cliente'] = 'nuevo';

        return $datos;
    }

    /**
     * Lee del POST todos los campos del formulario.
     *
     * Recibe: nada.
     * Devuelve: valores capturados (la contraseña sin recortar).
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
     * Valida el formulario de nueva reparación (RF-33 y SRS 5.4).
     *
     * Recibe: $datos, valores capturados.
     * Devuelve: errores por campo (vacío si todo es válido).
     */
    private function validarFormulario(array $datos): array {
        $errores = [];
        $hoy = date('Y-m-d');

        /* --- Dueño --- */
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

        /* --- Mueble y reparación --- */
        $descripcionMueble = $datos['descripcion_mueble'];

        if ($descripcionMueble === '' || mb_strlen($descripcionMueble) > 150) {
            $errores['descripcion_mueble'] = 'Describe el mueble (máximo 150 caracteres).';
        }

        if (!isset(Reparacion::TAMANOS[$datos['tamano']])) {
            $errores['tamano'] = 'Elige el tamaño del mueble.';
        }

        if ($datos['tipo_reparacion'] === '' || mb_strlen($datos['tipo_reparacion']) > 80) {
            $errores['tipo_reparacion'] = 'Escribe el tipo de reparación (máximo 80 caracteres).';
        }

        if (mb_strlen($datos['descripcion_danio']) > 2000) {
            $errores['descripcion_danio'] = 'La descripción del daño no puede pasar '
                . 'de 2000 caracteres.';
        }

        /* --- Fechas --- */
        if (!$this->fechaValida($datos['fecha_estimada'])) {
            $errores['fecha_estimada'] = 'Elige la fecha estimada de entrega.';
        } elseif ($datos['fecha_estimada'] < $hoy) {
            $errores['fecha_estimada'] = 'La fecha estimada no puede ser anterior a hoy.';
        }

        if (!$this->fechaValida($datos['fecha_limite_pago'])) {
            $errores['fecha_limite_pago'] = 'Elige la fecha límite de pago.';
        } elseif ($datos['fecha_limite_pago'] < $hoy) {
            $errores['fecha_limite_pago'] = 'La fecha límite de pago no puede ser anterior a hoy.';
        }

        /* --- Costo y anticipo --- */
        $costoValido = is_numeric($datos['costo'])
            && (float) $datos['costo'] > 0
            && (float) $datos['costo'] <= self::MAX_MONTO;

        if (!$costoValido) {
            $errores['costo'] = 'Escribe un costo mayor a $0.00.';
        }

        $anticipo = $datos['anticipo'];

        if ($anticipo !== '' && (!is_numeric($anticipo) || (float) $anticipo < 0)) {
            $errores['anticipo'] = 'El anticipo debe ser un número igual o mayor a $0.00.';
        } elseif ($costoValido && $anticipo !== ''
            && PagoService::aCentavos($anticipo) > PagoService::aCentavos($datos['costo'])) {
            $errores['anticipo'] = 'El anticipo no puede ser mayor al costo.';
        }

        // Método del anticipo: obligatorio solo si el anticipo es mayor a 0 (SRS 5.4)
        $hayAnticipo = $anticipo !== '' && is_numeric($anticipo) && (float) $anticipo > 0;

        if ($hayAnticipo && !isset(PagoCliente::METODOS[$datos['metodo_anticipo']])) {
            $errores['metodo_anticipo'] = 'Elige el método del anticipo.';
        }

        return $errores;
    }

    /**
     * Busca una reparación activa o responde 404.
     *
     * Recibe: $idReparacion.
     * Devuelve: datos completos de la reparación.
     */
    private function buscarReparacion(int $idReparacion): array {
        $reparacion = (new Reparacion())->buscarPorId($idReparacion);

        if ($reparacion === null) {
            View::error(404);
        }

        return $reparacion;
    }

}
