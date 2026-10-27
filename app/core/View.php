<?php
/*
 * =====================================================
 * Carpintería Carpin – app/core/View.php
 * Dibuja las vistas PHP de app/views, opcionalmente dentro
 * de un layout, arma las URL internas del sistema y muestra
 * las páginas de error (403, 404 y 500).
 * También define las funciones globales que usan las vistas:
 * e() para escapar toda salida HTML (SRS 14.3, regla 3) y
 * los formatos de México: dinero, fechas y horas (RNF-06).
 * =====================================================
 */

/* ===== Escape de HTML ===== */

/**
 * Escapa un texto para mostrarlo en HTML de forma segura.
 *
 * Recibe: $texto, valor a mostrar (null se convierte en texto vacío).
 * Devuelve: texto escapado con htmlspecialchars.
 */
function e(mixed $texto): string {
    return htmlspecialchars((string) ($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ===== Formatos de México (RNF-06, SRS 8.2) ===== */

/**
 * Da formato de moneda en pesos mexicanos con 2 decimales.
 *
 * Recibe: $monto, número o texto numérico (null se toma como 0).
 * Devuelve: texto como "$12,500.00".
 */
function dinero(mixed $monto): string {
    return '$' . number_format((float) ($monto ?? 0), 2, '.', ',');
}

/**
 * Da formato dd/mm/aaaa a una fecha de la base de datos.
 *
 * Recibe: $fecha, texto ISO "aaaa-mm-dd" o "aaaa-mm-dd hh:mm:ss", o null.
 * Devuelve: texto como "04/11/2026", o "" si no hay fecha.
 */
function fecha(?string $fecha): string {
    if ($fecha === null || $fecha === '') {
        return '';
    }

    return date('d/m/Y', strtotime($fecha));
}

/**
 * Da formato dd/mm/aaaa y hora de 12 h con a. m./p. m.
 *
 * Recibe: $fechaHora, texto ISO "aaaa-mm-dd hh:mm:ss", o null.
 * Devuelve: texto como "24/10/2026 5:00 p. m.", o "" si no hay fecha.
 */
function fechaHora(?string $fechaHora): string {
    if ($fechaHora === null || $fechaHora === '') {
        return '';
    }

    $marca = strtotime($fechaHora);
    $periodo = ((int) date('G', $marca) < 12) ? 'a. m.' : 'p. m.';

    return date('d/m/Y g:i', $marca) . ' ' . $periodo;
}

/* ===== Errores de validación en formularios ===== */

/**
 * Devuelve la clase de Bootstrap para marcar un campo con error.
 *
 * Recibe: $errores, errores por campo; $campo, nombre del campo.
 * Devuelve: "is-invalid" si el campo tiene error, o "".
 */
function claseInvalida(array $errores, string $campo): string {
    return isset($errores[$campo]) ? 'is-invalid' : '';
}

/**
 * Devuelve el mensaje de error de un campo, listo para mostrarse.
 *
 * Recibe: $errores, errores por campo; $campo, nombre del campo.
 * Devuelve: HTML del <div class="invalid-feedback">, o "" si no hay error.
 */
function mensajeError(array $errores, string $campo): string {
    if (!isset($errores[$campo])) {
        return '';
    }

    return '<div class="invalid-feedback">' . e($errores[$campo]) . '</div>';
}

/**
 * Devuelve el atributo "selected" para una opción de un <select>.
 *
 * Recibe: $condicion, true si la opción debe quedar elegida.
 * Devuelve: "selected" o "".
 */
function seleccionado(bool $condicion): string {
    return $condicion ? 'selected' : '';
}

/* ===== Etiquetas legibles de valores de la base ===== */

/**
 * Convierte el nombre de un rol de la base en texto legible.
 *
 * Recibe: $rol, ej. "CARPINTERO".
 * Devuelve: texto como "Carpintero".
 */
function etiquetaRol(?string $rol): string {
    return mb_convert_case(mb_strtolower((string) $rol), MB_CASE_TITLE, 'UTF-8');
}

/**
 * Convierte la forma de pago de la base en texto legible.
 *
 * Recibe: $formaPago, "HORA", "DIA", "PROYECTO" o null.
 * Devuelve: texto como "Por día", o "Sin forma de pago" si es null.
 */
function etiquetaFormaPago(?string $formaPago): string {
    $etiquetas = [
        'HORA'     => 'Por hora',
        'DIA'      => 'Por día',
        'PROYECTO' => 'Por proyecto',
    ];

    return $etiquetas[$formaPago] ?? 'Sin forma de pago';
}

/**
 * Convierte el status de pago de la base en texto legible.
 *
 * Recibe: $status, "PENDIENTE", "CON_ANTICIPO" o "PAGADO".
 * Devuelve: texto como "Con anticipo".
 */
function etiquetaStatusPago(?string $status): string {
    $etiquetas = [
        'PENDIENTE'    => 'Pendiente',
        'CON_ANTICIPO' => 'Con anticipo',
        'PAGADO'       => 'Pagado',
    ];

    return $etiquetas[$status] ?? '';
}

/**
 * Convierte el tipo de actividad de la base en texto legible.
 *
 * Recibe: $tipo, "ARMADO", "LIJADO" o "PINTURA".
 * Devuelve: texto como "Lijado".
 */
function etiquetaActividad(?string $tipo): string {
    return mb_convert_case(mb_strtolower((string) $tipo), MB_CASE_TITLE, 'UTF-8');
}

final class View {

    /* ===== Rutas de las vistas ===== */
    private const CARPETA_VISTAS = __DIR__ . '/../views/';

    /* ===== URL base, leída de config.php ===== */
    private static ?string $urlBase = null;

    /* ===== Título de la página: lo fija cada vista y lo usa el layout ===== */
    public static string $titulo = 'Carpin';

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
     * Los parámetros llevan el prefijo "__" para que no choquen con las
     * variables de la vista (por ejemplo, una vista que recibe $datos).
     *
     * Recibe: $__archivo, ruta completa del .php; $__variables, variables
     *         de la vista.
     * Devuelve: el HTML que generó la vista.
     */
    private static function incluir(string $__archivo, array $__variables): string {
        if (!is_file($__archivo)) {
            throw new RuntimeException("No existe la vista: {$__archivo}");
        }

        extract($__variables, EXTR_SKIP);

        ob_start();
        require $__archivo;

        return (string) ob_get_clean();
    }

}
