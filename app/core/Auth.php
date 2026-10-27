<?php
/*
 * =====================================================
 * Carpintería Carpin – app/core/Auth.php
 * Manejo de la sesión del usuario: inicio seguro de la
 * sesión (cookies HttpOnly y SameSite=Lax), expiración por
 * 60 minutos de inactividad (RF-02), datos del usuario
 * autenticado y validación de rol en el servidor (RF-03).
 * =====================================================
 */

final class Auth {

    /* ===== Nombres de los roles (tabla rol) ===== */
    public const ADMINISTRADOR = 'ADMINISTRADOR';
    public const CARPINTERO    = 'CARPINTERO';
    public const LIJADOR       = 'LIJADOR';
    public const PINTOR        = 'PINTOR';
    public const CONTADOR      = 'CONTADOR';
    public const CLIENTE       = 'CLIENTE';

    /* ===== Nombre de la cookie de sesión ===== */
    private const NOMBRE_SESION = 'CARPINSESID';

    /* ===== Inicio y cierre de la sesión ===== */

    /* ===== Pantalla de inicio de cada rol ===== */
    private const RUTA_INICIO = [
        self::ADMINISTRADOR => '/admin',
        self::CARPINTERO    => '/tablero',
        self::LIJADOR       => '/mis-pendientes',
        self::PINTOR        => '/mis-pendientes',
        self::CONTADOR      => '/panel',
        self::CLIENTE       => '/mis-pedidos',
    ];

    /**
     * Inicia la sesión de PHP con cookies seguras y revisa la inactividad.
     * Si pasaron más minutos de los configurados desde la última petición,
     * la sesión se cierra y se deja un aviso para el usuario.
     * La consulta automática de la campana (encabezado X-Carpin-Sondeo)
     * no cuenta como actividad, para que la sesión sí pueda expirar.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public static function iniciarSesion(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $app = (require __DIR__ . '/../../config/config.php')['app'];
        $rutaCookie = parse_url($app['base_url'], PHP_URL_PATH) ?: '/';

        session_name(self::NOMBRE_SESION);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $rutaCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Cierre automático por inactividad (RF-02)
        $limiteSegundos = $app['session_mins'] * 60;
        $ultimaActividad = $_SESSION['ultima_actividad'] ?? null;

        if (self::estaAutenticado() && $ultimaActividad !== null) {
            if (time() - $ultimaActividad > $limiteSegundos) {
                self::cerrarSesion();
                session_start();
                Flash::agregar(
                    'aviso',
                    'Tu sesión se cerró por inactividad. Inicia sesión de nuevo.'
                );
            }
        }

        $esSondeo = ($_SERVER['HTTP_X_CARPIN_SONDEO'] ?? '') === '1';

        if (!$esSondeo || !isset($_SESSION['ultima_actividad'])) {
            $_SESSION['ultima_actividad'] = time();
        }
    }

    /**
     * Guarda en la sesión al usuario que acaba de iniciar sesión.
     * Regenera el identificador de sesión para evitar su robo (RNF-01).
     *
     * Recibe: $usuario, arreglo con id_usuario, username, rol y nombre.
     * Devuelve: nada.
     */
    public static function iniciarUsuario(array $usuario): void {
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario' => (int) $usuario['id_usuario'],
            'username'   => $usuario['username'],
            'rol'        => $usuario['rol'],
            'nombre'     => $usuario['nombre'],
        ];
        $_SESSION['ultima_actividad'] = time();
    }

    /**
     * Cierra la sesión: borra sus datos, su cookie y la destruye.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public static function cerrarSesion(): void {
        $_SESSION = [];

        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $parametros['path'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_destroy();
    }

    /* ===== Datos del usuario autenticado ===== */

    /**
     * Indica si hay un usuario con sesión iniciada.
     *
     * Recibe: nada.
     * Devuelve: true si hay sesión de usuario.
     */
    public static function estaAutenticado(): bool {
        return isset($_SESSION['usuario']);
    }

    /**
     * Devuelve los datos del usuario autenticado.
     *
     * Recibe: nada.
     * Devuelve: arreglo con id_usuario, username, rol y nombre, o null.
     */
    public static function usuario(): ?array {
        return $_SESSION['usuario'] ?? null;
    }

    /**
     * Devuelve el id del usuario autenticado.
     *
     * Recibe: nada.
     * Devuelve: id_usuario o null si no hay sesión.
     */
    public static function id(): ?int {
        return $_SESSION['usuario']['id_usuario'] ?? null;
    }

    /**
     * Devuelve el rol del usuario autenticado.
     *
     * Recibe: nada.
     * Devuelve: nombre del rol (ej. "CARPINTERO") o null si no hay sesión.
     */
    public static function rol(): ?string {
        return $_SESSION['usuario']['rol'] ?? null;
    }

    /* ===== Control de acceso ===== */

    /**
     * Exige que haya una sesión iniciada; si no, envía a la pantalla
     * de inicio de sesión.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public static function requireLogin(): void {
        // Si el Administrador desactivó la cuenta, la sesión abierta se cierra (RF-06)
        if (self::estaAutenticado() && !self::cuentaSigueActiva()) {
            self::cerrarSesion();
            session_start();
            Flash::agregar('error', 'Cuenta desactivada, contacte a la carpintería.');
        }

        if (self::estaAutenticado()) {
            return;
        }

        header('Location: ' . View::url('/login'));
        exit;
    }

    /**
     * Devuelve la ruta de la pantalla de inicio que corresponde a un rol.
     *
     * Recibe: $rol, nombre del rol (null toma el rol del usuario actual).
     * Devuelve: ruta interna, ej. "/tablero"; "/login" si no hay rol.
     */
    public static function rutaInicio(?string $rol = null): string {
        $rol = $rol ?? self::rol();

        return self::RUTA_INICIO[$rol] ?? '/login';
    }

    /**
     * Revisa en la base de datos que la cuenta del usuario siga activa.
     *
     * Recibe: nada (usa el usuario de la sesión).
     * Devuelve: true si la cuenta existe y tiene activo = 1.
     */
    private static function cuentaSigueActiva(): bool {
        $consulta = Database::get()->prepare('SELECT activo FROM usuario WHERE id_usuario = ?');
        $consulta->execute([self::id()]);

        return (int) $consulta->fetchColumn() === 1;
    }

    /**
     * Exige sesión iniciada y que el rol del usuario esté en la lista.
     * Si el rol no corresponde responde 403 (RF-03).
     *
     * Recibe: $roles, lista de roles permitidos, ej. [Auth::CARPINTERO].
     * Devuelve: nada.
     */
    public static function requireRol(array $roles): void {
        self::requireLogin();

        if (!in_array(self::rol(), $roles, true)) {
            View::error(403);
        }
    }

}
