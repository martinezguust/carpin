<?php
/*
 * =====================================================
 * Carpintería Carpin – app/core/Csrf.php
 * Protección contra CSRF (RNF-03): genera un token por
 * sesión, lo coloca en los formularios y lo valida en
 * cada petición POST.
 * =====================================================
 */

final class Csrf {

    /* ===== Nombre del campo en los formularios ===== */
    public const CAMPO = '_csrf';

    /**
     * Devuelve el token CSRF de la sesión; lo crea si todavía no existe.
     *
     * Recibe: nada.
     * Devuelve: token en hexadecimal.
     */
    public static function token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Devuelve el campo oculto que se coloca dentro de cada formulario POST.
     *
     * Recibe: nada.
     * Devuelve: HTML del <input type="hidden"> con el token.
     */
    public static function campo(): string {
        return '<input type="hidden" name="' . self::CAMPO . '" value="' . e(self::token()) . '">';
    }

    /**
     * Valida el token recibido en el formulario (o en el encabezado
     * X-CSRF-Token para peticiones hechas con fetch).
     *
     * Recibe: nada (usa $_POST y $_SERVER).
     * Devuelve: true si el token coincide con el de la sesión.
     */
    public static function validar(): bool {
        $tokenSesion = $_SESSION['csrf_token'] ?? '';
        $tokenRecibido = $_POST[self::CAMPO] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        if ($tokenSesion === '' || !is_string($tokenRecibido)) {
            return false;
        }

        return hash_equals($tokenSesion, $tokenRecibido);
    }

}
