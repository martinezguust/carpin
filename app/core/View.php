<?php
/*
 * =====================================================
 * Carpintería Carpin – app/core/View.php
 * Dibuja las vistas PHP de app/views, opcionalmente dentro
 * de un layout, arma las URL internas del sistema y muestra
 * las páginas de error (403, 404 y 500).
 * También define la función global e() para escapar toda
 * salida HTML (SRS 14.3, regla 3).
 * =====================================================
 */

/**
 * Escapa un texto para mostrarlo en HTML de forma segura.
 *
 * Recibe: $texto, valor a mostrar (null se convierte en texto vacío).
 * Devuelve: texto escapado con htmlspecialchars.
 */
function e(mixed $texto): string {
    return htmlspecialchars((string) ($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

final class View {

    /* ===== Rutas de las vistas ===== */
    private const CARPETA_VISTAS = __DIR__ . '/../views/';

    /* ===== URL base, leída de config.php ===== */
    private static ?string $urlBase = null;

    /* ===== Dibujo de vistas ===== */

    /**
     * Genera el HTML de una vista, opcionalmente dentro de un layout.
     * El layout recibe el HTML de la vista en la variable $contenido.
     *
     * Recibe: $vista, ruta dentro de app/views sin ".php";
     *         $datos, variables para la vista;
     *         $layout, nombre del layout en app/views/layouts o null.
     * Devuelve: el HTML generado.
     */
    public static function render(
        string $vista,
        array $datos = [],
        ?string $layout = null
    ): string {
        $contenido = self::incluir(self::CARPETA_VISTAS . $vista . '.php', $datos);

        if ($layout === null) {
            return $contenido;
        }

        $datos['contenido'] = $contenido;

        return self::incluir(self::CARPETA_VISTAS . 'layouts/' . $layout . '.php', $datos);
    }

    /**
     * Imprime una vista (atajo de render).
     *
     * Recibe: los mismos parámetros que render().
     * Devuelve: nada.
     */
    public static function mostrar(string $vista, array $datos = [], ?string $layout = null): void {
        echo self::render($vista, $datos, $layout);
    }

    /**
     * Muestra la página de error indicada con su código HTTP y termina.
     *
     * Recibe: $codigo, 403, 404 o 500.
     * Devuelve: nada (no regresa).
     */
    public static function error(int $codigo): never {
        if (!in_array($codigo, [403, 404, 500], true)) {
            $codigo = 500;
        }

        http_response_code($codigo);
        echo self::render('errores/' . $codigo);
        exit;
    }

    /* ===== URL del sistema ===== */

    /**
     * Arma la URL completa de una ruta interna o de un archivo de public/.
     *
     * Recibe: $ruta, ej. "/proyectos" o "assets/css/carpin.css".
     * Devuelve: URL completa, ej. "http://localhost/carpin/public/proyectos".
     */
    public static function url(string $ruta = '/'): string {
        if (self::$urlBase === null) {
            $app = (require __DIR__ . '/../../config/config.php')['app'];
            self::$urlBase = rtrim($app['base_url'], '/');
        }

        return self::$urlBase . '/' . ltrim($ruta, '/');
    }

    /* ===== Funciones internas ===== */

    /**
     * Ejecuta un archivo de vista con sus variables y captura su salida.
     *
     * Recibe: $archivo, ruta completa del .php; $datos, variables de la vista.
     * Devuelve: el HTML que generó la vista.
     */
    private static function incluir(string $archivo, array $datos): string {
        if (!is_file($archivo)) {
            throw new RuntimeException("No existe la vista: {$archivo}");
        }

        extract($datos, EXTR_SKIP);

        ob_start();
        require $archivo;

        return (string) ob_get_clean();
    }

}
