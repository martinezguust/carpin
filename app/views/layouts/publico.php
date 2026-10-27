<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/layouts/publico.php
 * Layout de la página pública (SRS 10.2, mockup 1): menú
 * superior fijo (logo · Inicio · Catálogo · Nosotros ·
 * Contacto · Solicitar cotización · Iniciar sesión), pie de
 * página y botón flotante de WhatsApp (RF-10).
 * Variable: $contenido, HTML de la vista.
 * =====================================================
 */

$negocioPublico = (new Configuracion())->obtener();
$nombreNegocio = $negocioPublico['nombre_negocio'] ?? 'Carpintería Carpin';

// Enlace de WhatsApp con el mensaje predefinido (RF-10); null si no hay número
$enlaceWhatsapp = Configuracion::enlaceWhatsapp($negocioPublico);

// Opciones del menú: ruta, texto
$menuPublico = [
    ['/', 'Inicio'],
    ['/catalogo', 'Catálogo'],
    ['/nosotros', 'Nosotros'],
    ['/contacto', 'Contacto'],
    ['/cotizacion', 'Solicitar cotización'],
];
?>
<!DOCTYPE html>
<html lang="es-MX">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(View::$titulo) ?> · <?= e($nombreNegocio) ?></title>

    <link
        rel="stylesheet"
        href="<?= e(View::url('assets/vendor/bootstrap/css/bootstrap.min.css')) ?>"
    >
    <link
        rel="stylesheet"
        href="<?= e(View::url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>"
    >
    <link rel="stylesheet" href="<?= e(View::url('assets/css/carpin.css')) ?>">
</head>

<body class="cuerpo-publico">

    <!-- ===== Menú superior fijo ===== -->
    <header class="menu-publico">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand menu-publico__logo" href="<?= e(View::url('/')) ?>">
                    <?php require __DIR__ . '/_logo.php'; ?>
                </a>

                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#menuPublico"
                    aria-controls="menuPublico"
                    aria-expanded="false"
                    aria-label="Abrir menú"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="menuPublico">
                    <ul class="navbar-nav me-auto">
                        <?php foreach ($menuPublico as [$rutaMenu, $textoMenu]): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= e(View::url($rutaMenu)) ?>">
                                    <?= e($textoMenu) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (Auth::estaAutenticado()): ?>
                        <!-- Con sesión: regreso al panel (respuesta 8) -->
                        <a
                            class="btn btn-carpin-claro"
                            href="<?= e(View::url(Auth::rutaInicio())) ?>"
                        >Mi panel</a>
                    <?php else: ?>
                        <a class="btn btn-carpin-claro" href="<?= e(View::url('/login')) ?>">
                            Iniciar sesión
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- ===== Contenido de la página ===== -->
    <main>
        <?= $contenido ?>
    </main>

    <!-- ===== Pie de página ===== -->
    <footer class="pie-publico">
        <div class="container">
            <?= e($nombreNegocio) ?> · Muebles a la medida y reparaciones
        </div>
    </footer>

    <?php if ($enlaceWhatsapp !== null): ?>
        <!-- ===== Botón flotante de WhatsApp (RF-10) ===== -->
        <a
            class="boton-whatsapp"
            href="<?= e($enlaceWhatsapp) ?>"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Escríbenos por WhatsApp"
        >
            <i class="bi bi-whatsapp" aria-hidden="true"></i>
        </a>
    <?php endif; ?>

    <script
        src="<?= e(View::url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"
    ></script>
</body>

</html>
