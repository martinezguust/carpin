<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/layouts/acceso.php
 * Layout de la pantalla de inicio de sesión (mockup 2):
 * fondo café nogal sin menú, solo la marca y la tarjeta.
 * Variable: $contenido, HTML de la vista.
 * =====================================================
 */
?>
<!DOCTYPE html>
<html lang="es-MX">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

<body class="cuerpo-acceso">
    <?= $contenido ?>

    <script
        src="<?= e(View::url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"
    ></script>
</body>

</html>
