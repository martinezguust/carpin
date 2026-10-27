<?php
/*
 * =====================================================
 * Carpintería Carpin – public/index.php
 * Front controller: toda petición del sistema entra por
 * aquí (ver public/.htaccess). Prepara la zona horaria,
 * la carga automática de clases, la sesión y el manejo de
 * errores, y entrega la petición al Router.
 * =====================================================
 */

declare(strict_types=1);

/* ===== Rutas y configuración ===== */

// Carpeta raíz del proyecto (carpin/)
define('RAIZ', dirname(__DIR__));

$configuracion = require RAIZ . '/config/config.php';

// Zona horaria del sistema (RNF-06)
date_default_timezone_set($configuracion['app']['timezone']);

/* ===== Carga automática de clases ===== */

// Dependencias de Composer (Dompdf)
require RAIZ . '/vendor/autoload.php';

// Clases propias: se buscan por nombre en estas carpetas
spl_autoload_register(function (string $clase): void {
    $carpetas = [
        'app/core',
        'app/controllers',
        'app/models',
        'app/services',
    ];

    foreach ($carpetas as $carpeta) {
        $archivo = RAIZ . '/' . $carpeta . '/' . $clase . '.php';

        if (is_file($archivo)) {
            require_once $archivo;
            return;
        }
    }
});

// View.php define la función global e(), que usan todas las vistas
require_once RAIZ . '/app/core/View.php';

/* ===== Manejo de errores no controlados ===== */

// Cualquier excepción no atrapada se registra en el log de Apache
// y el usuario ve la página de error 500 en español.
set_exception_handler(function (Throwable $error): void {
    $detalle = $error->getMessage() . ' en ' . $error->getFile() . ':' . $error->getLine();
    error_log('[Carpin] ' . $detalle);

    if (!headers_sent()) {
        http_response_code(500);
    }

    echo View::render('errores/500');
});

/* ===== Sesión y atención de la petición ===== */

Auth::iniciarSesion();

$router = new Router($configuracion['app']['base_url']);

require RAIZ . '/config/rutas.php';

$router->despachar();
