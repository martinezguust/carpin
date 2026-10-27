<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/errores/plantilla.php
 * Diseño común de las páginas de error. La incluyen
 * 403.php, 404.php y 500.php después de definir:
 *     $codigo  – código HTTP (texto)
 *     $titulo  – título del error
 *     $mensaje – explicación para el usuario
 * Es una página independiente: no usa layout.
 * =====================================================
 */
?>
<!DOCTYPE html>
<html lang="es-MX">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> · Carpin</title>

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

<body>
    <main class="pagina-error">
        <section class="pagina-error__tarjeta shadow">

            <!-- ===== Logo provisional ===== -->
            <p class="pagina-error__logo mb-4">
                <i class="bi bi-tree-fill" aria-hidden="true"></i>
                Carpin
            </p>

            <!-- ===== Detalle del error ===== -->
            <p class="pagina-error__codigo mb-1"><?= e($codigo) ?></p>
            <h1 class="h4 mb-3"><?= e($titulo) ?></h1>
            <p class="mb-4"><?= e($mensaje) ?></p>

            <!-- ===== Regreso ===== -->
            <a class="pagina-error__boton" href="<?= e(View::url('/')) ?>">
                Volver al inicio
            </a>

        </section>
    </main>
</body>

</html>
