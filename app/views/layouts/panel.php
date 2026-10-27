<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/layouts/panel.php
 * Layout de las pantallas con sesión (SRS 10.3): barra
 * superior con logo, campana de notificaciones, nombre del
 * usuario y menú "Mi cuenta"; menú lateral con las opciones
 * del rol (en celular se abre como menú hamburguesa).
 * Variable: $contenido, HTML de la vista.
 * =====================================================
 */

$usuarioSesion = Auth::usuario();
$rolSesion = $usuarioSesion['rol'];
$sinLeer = (new Notificacion())->contarSinLeer((int) $usuarioSesion['id_usuario']);

// Opciones del menú lateral por rol (Fase 1; lo no construido queda oculto)
$menusPorRol = [
    Auth::ADMINISTRADOR => [
        ['/admin', 'Panel', 'bi-speedometer2'],
        ['/usuarios', 'Usuarios', 'bi-people'],
    ],
    Auth::CARPINTERO    => [
        ['/tablero', 'Panel', 'bi-kanban'],
        ['/proyectos', 'Proyectos', 'bi-hammer'],
    ],
    Auth::LIJADOR       => [
        ['/mis-pendientes', 'Mis pendientes', 'bi-list-check'],
    ],
    Auth::PINTOR        => [
        ['/mis-pendientes', 'Mis pendientes', 'bi-list-check'],
    ],
    Auth::CONTADOR      => [
        ['/panel', 'Panel', 'bi-speedometer2'],
    ],
    Auth::CLIENTE       => [
        ['/mis-pedidos', 'Mis pedidos', 'bi-box-seam'],
    ],
];

// Ruta actual sin la carpeta base, para marcar la opción activa del menú
$rutaBase = rtrim((string) parse_url(View::url('/'), PHP_URL_PATH), '/');
$rutaPedida = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$rutaActual = '/' . trim(substr($rutaPedida, strlen($rutaBase)), '/');
?>
<!DOCTYPE html>
<html lang="es-MX">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e(View::$titulo) ?> · Carpin</title>

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

<body
    class="cuerpo-panel"
    data-url-base="<?= e(rtrim(View::url('/'), '/')) ?>"
>
    <!-- ===== Barra superior ===== -->
    <header class="barra-superior no-imprimir">
        <button
            class="btn-barra d-lg-none"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#menuLateral"
            aria-controls="menuLateral"
            aria-label="Abrir menú"
        >
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <a class="barra-superior__logo" href="<?= e(View::url(Auth::rutaInicio())) ?>">
            <?php require __DIR__ . '/_logo.php'; ?>
        </a>

        <div class="barra-superior__acciones">
            <span class="barra-superior__usuario d-none d-md-inline">
                <?= e($usuarioSesion['nombre']) ?> · <?= e(etiquetaRol($rolSesion)) ?>
            </span>

            <!-- Campana de notificaciones (RF-60) -->
            <div class="dropdown">
                <button
                    class="btn-barra campana"
                    type="button"
                    id="botonCampana"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    aria-label="Notificaciones"
                >
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    <span
                        class="campana__contador"
                        id="campanaContador"
                        <?= ($sinLeer === 0) ? 'hidden' : '' ?>
                    ><?= e($sinLeer) ?></span>
                </button>

                <div
                    class="dropdown-menu dropdown-menu-end campana__menu"
                    aria-labelledby="botonCampana"
                >
                    <p class="campana__titulo">Notificaciones</p>

                    <div class="campana__lista" id="campanaLista">
                        <p class="campana__vacia">Cargando…</p>
                    </div>

                    <a class="campana__ver-todas" href="<?= e(View::url('/notificaciones')) ?>">
                        Ver todas
                    </a>
                </div>
            </div>

            <!-- Menú "Mi cuenta" (SRS 10.3) -->
            <div class="dropdown">
                <button
                    class="btn-barra"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Mi cuenta"
                >
                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <h2 class="dropdown-header">
                            Mi cuenta
                            <span class="d-block d-md-none">
                                <?= e($usuarioSesion['nombre']) ?> ·
                                <?= e(etiquetaRol($rolSesion)) ?>
                            </span>
                        </h2>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= e(View::url('/cuenta/contrasena')) ?>">
                            <i class="bi bi-key" aria-hidden="true"></i>
                            Cambiar contraseña
                        </a>
                    </li>
                    <li>
                        <form method="post" action="<?= e(View::url('/logout')) ?>">
                            <?= Csrf::campo() ?>
                            <button class="dropdown-item" type="submit">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                Cerrar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="contenedor-panel">

        <!-- ===== Menú lateral (hamburguesa en celular) ===== -->
        <aside
            class="menu-lateral offcanvas-lg offcanvas-start no-imprimir"
            id="menuLateral"
            tabindex="-1"
            aria-labelledby="tituloMenuLateral"
        >
            <div class="offcanvas-header d-lg-none">
                <h2 class="offcanvas-title h5" id="tituloMenuLateral">Menú</h2>
                <button
                    class="btn-close"
                    type="button"
                    data-bs-dismiss="offcanvas"
                    data-bs-target="#menuLateral"
                    aria-label="Cerrar menú"
                ></button>
            </div>

            <nav class="menu-lateral__opciones">
                <?php foreach ($menusPorRol[$rolSesion] ?? [] as $opcion): ?>
                    <?php
                    [$rutaMenu, $textoMenu, $iconoMenu] = $opcion;
                    $esActiva = $rutaActual === $rutaMenu
                        || str_starts_with($rutaActual, $rutaMenu . '/');
                    $claseActiva = $esActiva ? 'menu-lateral__opcion--activa' : '';
                    ?>
                    <a
                        class="menu-lateral__opcion <?= e($claseActiva) ?>"
                        href="<?= e(View::url($rutaMenu)) ?>"
                        <?= $esActiva ? 'aria-current="page"' : '' ?>
                    >
                        <i class="bi <?= e($iconoMenu) ?>" aria-hidden="true"></i>
                        <?= e($textoMenu) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- ===== Contenido de la pantalla ===== -->
        <main class="contenido-panel">
            <?php require __DIR__ . '/_mensajes.php'; ?>

            <?= $contenido ?>
        </main>

    </div>

    <script
        src="<?= e(View::url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"
    ></script>
    <script src="<?= e(View::url('assets/js/app.js')) ?>"></script>
</body>

</html>
