<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/UsuarioController.php
 * Gestión de usuarios por el Administrador (CU-02, HU-19):
 * lista con búsqueda y filtro por rol, alta de trabajadores,
 * edición de trabajadores y clientes, restablecimiento de
 * contraseña y activación / desactivación (baja lógica).
 * =====================================================
 */

class UsuarioController extends Controller {

    /* ===== Roles que el Administrador puede dar de alta (RF-04) ===== */
    private const ROLES_TRABAJADOR = [
        Auth::ADMINISTRADOR,
        Auth::CARPINTERO,
        Auth::LIJADOR,
        Auth::PINTOR,
        Auth::CONTADOR,
    ];

    /* ===== Lista y alta ===== */

    /**
     * Muestra la lista de usuarios y el formulario de alta (mockup 4).
     *
     * Recibe: nada (usa $_GET: q, rol).
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $this->mostrarLista([], [
            'nombre'     => '',
            'telefono'   => '',
            'username'   => '',
            'rol'        => '',
            'forma_pago' => '',
        ]);
    }

    /**
     * Da de alta un trabajador con su cuenta (CU-02).
     *
     * Recibe: nada (usa $_POST: nombre, telefono, username, password, rol,
     *         forma_pago).
     * Devuelve: nada.
     */
    public function guardar(): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $datos = [
            'nombre'     => $this->textoPost('nombre'),
            'telefono'   => $this->textoPost('telefono'),
            'username'   => $this->textoPost('username'),
            'rol'        => $this->textoPost('rol'),
            'forma_pago' => $this->textoPost('forma_pago'),
        ];
        $contrasena = (string) ($_POST['password'] ?? '');

        $usuarios = new Usuario();
        $errores = $this->validarTrabajador($datos, null);

        $errorContrasena = Usuario::validarContrasena($contrasena);

        if ($errorContrasena !== null) {
            $errores['password'] = $errorContrasena;
        }

        if ($errores !== []) {
            $this->mostrarLista($errores, $datos);
            return;
        }

        $formaPago = $this->formaPagoParaGuardar($datos);
        $db = Database::get();

        try {
            $db->beginTransaction();

            $idUsuario = $usuarios->crear($datos['username'], $contrasena, $datos['rol']);
            (new Trabajador())->crear($idUsuario, $datos['nombre'], $datos['telefono'], $formaPago);

            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $error;
        }

        Flash::agregar(
            'exito',
            "Usuario \"{$datos['username']}\" creado. Entrega las credenciales en persona."
        );

        $this->redirigir('/usuarios');
    }

    /* ===== Edición ===== */

    /**
     * Muestra el formulario de edición de un trabajador o de un cliente.
     *
     * Recibe: $id, id de la cuenta (segmento de la ruta).
     * Devuelve: nada.
     */
    public function editar(string $id): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $cuenta = $this->buscarCuenta($this->idDeRuta($id));

        $datos = [
            'nombre'     => $cuenta['nombre'],
            'telefono'   => $cuenta['telefono'],
            'username'   => $cuenta['username'],
            'rol'        => $cuenta['rol'],
            'forma_pago' => (string) $cuenta['forma_pago'],
            'correo'     => (string) $cuenta['correo'],
            'direccion'  => (string) $cuenta['direccion'],
        ];

        $this->mostrarEdicion($cuenta, $datos, []);
    }

    /**
     * Guarda los cambios de datos de una cuenta (CU-02, A2).
     *
     * Recibe: $id, id de la cuenta (segmento de la ruta).
     * Devuelve: nada.
     */
    public function actualizar(string $id): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $cuenta = $this->buscarCuenta($this->idDeRuta($id));
        $idUsuario = (int) $cuenta['id_usuario'];
        $esCliente = $cuenta['rol'] === Auth::CLIENTE;

        $datos = [
            'nombre'     => $this->textoPost('nombre'),
            'telefono'   => $this->textoPost('telefono'),
            'username'   => $this->textoPost('username'),
            'rol'        => $esCliente ? Auth::CLIENTE : $this->textoPost('rol'),
            'forma_pago' => $this->textoPost('forma_pago'),
            'correo'     => $this->textoPost('correo'),
            'direccion'  => $this->textoPost('direccion'),
        ];

        if ($esCliente) {
            $errores = $this->validarCliente($datos, $idUsuario);
        } else {
            $errores = $this->validarTrabajador($datos, $idUsuario);
        }

        // El Administrador no puede quitarse a sí mismo el rol de Administrador
        if ($idUsuario === Auth::id() && $datos['rol'] !== Auth::ADMINISTRADOR) {
            $errores['rol'] = 'No puedes cambiar tu propio rol.';
        }

        if ($errores !== []) {
            $this->mostrarEdicion($cuenta, $datos, $errores);
            return;
        }

        $db = Database::get();

        try {
            $db->beginTransaction();

            (new Usuario())->actualizarCuenta($idUsuario, $datos['username'], $datos['rol']);

            if ($esCliente) {
                (new Cliente())->actualizar(
                    $idUsuario,
                    $datos['nombre'],
                    $datos['telefono'],
                    $datos['correo'] !== '' ? $datos['correo'] : null,
                    $datos['direccion'] !== '' ? $datos['direccion'] : null
                );
            } else {
                (new Trabajador())->actualizar(
                    $idUsuario,
                    $datos['nombre'],
                    $datos['telefono'],
                    $this->formaPagoParaGuardar($datos)
                );
            }

            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $error;
        }

        Flash::agregar('exito', 'Los datos del usuario se guardaron correctamente.');

        $this->redirigir('/usuarios/' . $idUsuario . '/editar');
    }

    /**
     * Restablece la contraseña de cualquier cuenta, incluidos clientes
     * (RF-06, respuesta 29).
     *
     * Recibe: $id, id de la cuenta (segmento de la ruta).
     * Devuelve: nada.
     */
    public function restablecerContrasena(string $id): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $cuenta = $this->buscarCuenta($this->idDeRuta($id));
        $nueva = (string) ($_POST['nueva'] ?? '');
        $confirmacion = (string) ($_POST['confirmacion'] ?? '');

        $error = Usuario::validarContrasena($nueva);

        if ($error === null && $nueva !== $confirmacion) {
            $error = 'La confirmación no coincide con la nueva contraseña.';
        }

        if ($error !== null) {
            Flash::agregar('error', $error);
            $this->redirigir('/usuarios/' . $cuenta['id_usuario'] . '/editar');
        }

        (new Usuario())->cambiarContrasena((int) $cuenta['id_usuario'], $nueva);
        Flash::agregar('exito', 'La contraseña se restableció. Entrégala en persona.');

        $this->redirigir('/usuarios/' . $cuenta['id_usuario'] . '/editar');
    }

    /**
     * Activa o desactiva una cuenta (CU-02, A3). El historial se conserva.
     *
     * Recibe: $id, id de la cuenta (segmento de la ruta);
     *         $_POST['accion'], "activar" o "desactivar".
     * Devuelve: nada.
     */
    public function cambiarEstado(string $id): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $cuenta = $this->buscarCuenta($this->idDeRuta($id));
        $idUsuario = (int) $cuenta['id_usuario'];
        $activar = $this->textoPost('accion') === 'activar';

        if (!$activar && $idUsuario === Auth::id()) {
            Flash::agregar('error', 'No puedes desactivar tu propia cuenta.');
            $this->redirigir('/usuarios/' . $idUsuario . '/editar');
        }

        $db = Database::get();

        try {
            $db->beginTransaction();

            (new Usuario())->cambiarEstado($idUsuario, $activar);

            if ($cuenta['id_trabajador'] !== null) {
                (new Trabajador())->cambiarEstado($idUsuario, $activar);
            }

            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $error;
        }

        if ($activar) {
            Flash::agregar('exito', 'La cuenta se activó.');
        } else {
            Flash::agregar('exito', 'La cuenta se desactivó: ya no podrá iniciar sesión.');
        }

        $this->redirigir('/usuarios/' . $idUsuario . '/editar');
    }

    /* ===== Funciones internas ===== */

    /**
     * Dibuja la pantalla de lista de usuarios con el formulario de alta.
     *
     * Recibe: $errores, errores por campo; $datos, valores del formulario.
     * Devuelve: nada.
     */
    private function mostrarLista(array $errores, array $datos): void {
        $busqueda = trim((string) ($_GET['q'] ?? ''));
        $filtroRol = trim((string) ($_GET['rol'] ?? ''));

        $this->vista('admin/usuarios', [
            'usuarios'        => (new Usuario())->listar($busqueda, $filtroRol),
            'busqueda'        => $busqueda,
            'filtroRol'       => $filtroRol,
            'rolesFiltro'     => array_merge(self::ROLES_TRABAJADOR, [Auth::CLIENTE]),
            'rolesTrabajador' => self::ROLES_TRABAJADOR,
            'formasPorRol'    => Trabajador::FORMAS_POR_ROL,
            'errores'         => $errores,
            'datos'           => $datos,
        ], 'panel');
    }

    /**
     * Dibuja la pantalla de edición de una cuenta.
     *
     * Recibe: $cuenta, datos guardados; $datos, valores del formulario;
     *         $errores, errores por campo.
     * Devuelve: nada.
     */
    private function mostrarEdicion(array $cuenta, array $datos, array $errores): void {
        $this->vista('admin/usuario_editar', [
            'cuenta'          => $cuenta,
            'datos'           => $datos,
            'errores'         => $errores,
            'rolesTrabajador' => self::ROLES_TRABAJADOR,
            'formasPorRol'    => Trabajador::FORMAS_POR_ROL,
            'esPropia'        => (int) $cuenta['id_usuario'] === Auth::id(),
        ], 'panel');
    }

    /**
     * Busca la cuenta indicada o responde 404 si no existe.
     *
     * Recibe: $idUsuario.
     * Devuelve: datos de la cuenta.
     */
    private function buscarCuenta(int $idUsuario): array {
        $cuenta = (new Usuario())->buscarPorId($idUsuario);

        if ($cuenta === null) {
            View::error(404);
        }

        return $cuenta;
    }

    /**
     * Valida los datos de un trabajador (alta o edición).
     *
     * Recibe: $datos, valores capturados; $idUsuario, cuenta editada o null.
     * Devuelve: errores por campo (vacío si todo es válido).
     */
    private function validarTrabajador(array $datos, ?int $idUsuario): array {
        $errores = $this->validarDatosComunes($datos, $idUsuario);

        if (!in_array($datos['rol'], self::ROLES_TRABAJADOR, true)) {
            $errores['rol'] = 'Elige un rol.';
            return $errores;
        }

        // Forma de pago según el rol (SRS 5.4 y respuesta 30)
        $permitidas = Trabajador::FORMAS_POR_ROL[$datos['rol']];

        if ($permitidas !== [] && !in_array($datos['forma_pago'], $permitidas, true)) {
            $errores['forma_pago'] = 'Elige una forma de pago válida para el rol '
                . etiquetaRol($datos['rol']) . '.';
        }

        return $errores;
    }

    /**
     * Valida los datos de un cliente (edición por el Administrador).
     *
     * Recibe: $datos, valores capturados; $idUsuario, cuenta editada.
     * Devuelve: errores por campo (vacío si todo es válido).
     */
    private function validarCliente(array $datos, int $idUsuario): array {
        $errores = $this->validarDatosComunes($datos, $idUsuario);

        $errorCorreo = Usuario::validarCorreo($datos['correo']);

        if ($errorCorreo !== null) {
            $errores['correo'] = $errorCorreo;
        }

        if (mb_strlen($datos['direccion']) > 255) {
            $errores['direccion'] = 'La dirección no puede pasar de 255 caracteres.';
        }

        return $errores;
    }

    /**
     * Valida nombre, teléfono y nombre de usuario (únicos en todo el sistema).
     *
     * Recibe: $datos, valores capturados; $idUsuario, cuenta editada o null.
     * Devuelve: errores por campo.
     */
    private function validarDatosComunes(array $datos, ?int $idUsuario): array {
        $errores = [];

        if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 120) {
            $errores['nombre'] = 'Escribe el nombre completo (máximo 120 caracteres).';
        }

        $errorTelefono = Usuario::validarTelefono($datos['telefono']);

        if ($errorTelefono !== null) {
            $errores['telefono'] = $errorTelefono;
        }

        $errorUsername = Usuario::validarUsername($datos['username']);

        if ($errorUsername !== null) {
            $errores['username'] = $errorUsername;
        } elseif ((new Usuario())->existeUsername($datos['username'], $idUsuario)) {
            // CU-02, A1
            $errores['username'] = 'Ese nombre de usuario ya existe, elija otro';
        }

        return $errores;
    }

    /**
     * Devuelve la forma de pago que se guarda: el Administrador siempre
     * queda sin forma de pago (SRS 5.4).
     *
     * Recibe: $datos, con rol y forma_pago.
     * Devuelve: HORA/DIA/PROYECTO, o null para el Administrador.
     */
    private function formaPagoParaGuardar(array $datos): ?string {
        if ($datos['rol'] === Auth::ADMINISTRADOR) {
            return null;
        }

        return $datos['forma_pago'];
    }

}
