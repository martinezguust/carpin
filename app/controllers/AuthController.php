<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/AuthController.php
 * Inicio y cierre de sesión (CU-01, HU-18), bloqueo por
 * intentos fallidos y cambio de la propia contraseña (RF-07).
 * =====================================================
 */

class AuthController extends Controller {

    /* ===== Reglas del bloqueo (CU-01, A3) ===== */
    private const MAX_INTENTOS = 5;
    private const MINUTOS_BLOQUEO = 10;

    /* ===== Mensajes de CU-01 ===== */
    private const MSJ_INCORRECTOS = 'Usuario o contraseña incorrectos';
    private const MSJ_DESACTIVADA = 'Cuenta desactivada, contacte a la carpintería';
    private const MSJ_BLOQUEADA = 'Demasiados intentos fallidos. Intenta de nuevo en 10 minutos.';

    /**
     * Raíz del sistema. En la Fase 1 lleva al inicio de sesión o, si ya hay
     * sesión, a la pantalla de inicio del rol (respuesta 4).
     *
     * Recibe: nada.
     * Devuelve: nada (redirige).
     */
    public function raiz(): void {
        if (Auth::estaAutenticado()) {
            $this->redirigir(Auth::rutaInicio());
        }

        $this->redirigir('/login');
    }

    /**
     * Muestra la pantalla de inicio de sesión (mockup 2).
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function formulario(): void {
        if (Auth::estaAutenticado()) {
            $this->redirigir(Auth::rutaInicio());
        }

        $this->vista('publico/login', ['username' => '', 'error' => null], 'publico');
    }

    /**
     * Valida usuario y contraseña e inicia la sesión (CU-01).
     *
     * Recibe: nada (usa $_POST: username, password).
     * Devuelve: nada (redirige al panel del rol o vuelve a mostrar el formulario).
     */
    public function iniciar(): void {
        $username = $this->textoPost('username');
        $contrasena = (string) ($_POST['password'] ?? '');

        $modelo = new Usuario();
        $cuenta = ($username !== '') ? $modelo->buscarParaLogin($username) : null;

        // A1: usuario inexistente → mensaje genérico
        if ($cuenta === null) {
            $this->mostrarError($username, self::MSJ_INCORRECTOS);
        }

        $idUsuario = (int) $cuenta['id_usuario'];

        // A3: cuenta bloqueada temporalmente
        $bloqueadoHasta = $cuenta['bloqueado_hasta'];

        if ($bloqueadoHasta !== null && strtotime($bloqueadoHasta) > time()) {
            $this->mostrarError($username, self::MSJ_BLOQUEADA);
        }

        // A1 y A3: contraseña incorrecta, se cuenta el intento
        if (!password_verify($contrasena, $cuenta['password_hash'])) {
            $intentos = (int) $cuenta['intentos_fallidos'] + 1;

            if ($intentos >= self::MAX_INTENTOS) {
                $hasta = date('Y-m-d H:i:s', time() + self::MINUTOS_BLOQUEO * 60);
                $modelo->registrarIntentoFallido($idUsuario, 0, $hasta);
                $this->mostrarError($username, self::MSJ_BLOQUEADA);
            }

            $modelo->registrarIntentoFallido($idUsuario, $intentos, null);
            $this->mostrarError($username, self::MSJ_INCORRECTOS);
        }

        // A2: cuenta desactivada (solo se informa con la contraseña correcta)
        if ((int) $cuenta['activo'] !== 1) {
            $this->mostrarError($username, self::MSJ_DESACTIVADA);
        }

        // Acceso correcto
        $modelo->registrarAccesoExitoso($idUsuario);
        Auth::iniciarUsuario($cuenta);

        $this->redirigir(Auth::rutaInicio($cuenta['rol']));
    }

    /**
     * Cierra la sesión (RF-02).
     *
     * Recibe: nada.
     * Devuelve: nada (redirige al inicio de sesión).
     */
    public function salir(): void {
        Auth::cerrarSesion();
        session_start();
        Flash::agregar('exito', 'Cerraste sesión correctamente.');

        $this->redirigir('/login');
    }

    /**
     * Muestra el formulario para cambiar la propia contraseña (RF-07).
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function formularioContrasena(): void {
        Auth::requireLogin();

        $this->vista('comun/contrasena', ['errores' => []], 'panel');
    }

    /**
     * Cambia la contraseña del usuario si indica la actual (RF-07, RN-07).
     *
     * Recibe: nada (usa $_POST: actual, nueva, confirmacion).
     * Devuelve: nada.
     */
    public function cambiarContrasena(): void {
        Auth::requireLogin();

        $actual = (string) ($_POST['actual'] ?? '');
        $nueva = (string) ($_POST['nueva'] ?? '');
        $confirmacion = (string) ($_POST['confirmacion'] ?? '');

        $modelo = new Usuario();
        $errores = [];

        if (!$modelo->verificarContrasena((int) Auth::id(), $actual)) {
            $errores['actual'] = 'La contraseña actual no es correcta.';
        }

        $errorNueva = Usuario::validarContrasena($nueva);

        if ($errorNueva !== null) {
            $errores['nueva'] = $errorNueva;
        } elseif ($nueva !== $confirmacion) {
            $errores['confirmacion'] = 'La confirmación no coincide con la nueva contraseña.';
        }

        if ($errores !== []) {
            $this->vista('comun/contrasena', ['errores' => $errores], 'panel');
            return;
        }

        $modelo->cambiarContrasena((int) Auth::id(), $nueva);
        Flash::agregar('exito', 'Tu contraseña se cambió correctamente.');

        $this->redirigir(Auth::rutaInicio());
    }

    /**
     * Vuelve a mostrar el inicio de sesión con un mensaje de error.
     *
     * Recibe: $username, usuario capturado; $mensaje, texto del error.
     * Devuelve: nada (termina la petición).
     */
    private function mostrarError(string $username, string $mensaje): never {
        $this->vista('publico/login', ['username' => $username, 'error' => $mensaje], 'publico');
        exit;
    }

}
