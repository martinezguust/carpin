<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/layouts/publico.php
 * Layout público mínimo (Fase 1): solo lo usa la pantalla
 * de inicio de sesión. La página pública completa (menú,
 * catálogo y WhatsApp) se construye en la Fase 2.
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

<body class="cuerpo-publico">
    <?= $contenido ?>

    <script
        src="<?= e(View::url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"
    ></script>
</body>

</html>
