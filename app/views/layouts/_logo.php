<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/layouts/_logo.php
 * Logo de la carpintería (SRS 10.1). Si el Administrador
 * todavía no sube uno, se muestra el logo provisional:
 * ícono de árbol con el texto "Carpin".
 * Variable opcional: $claseLogo, clase CSS extra.
 * =====================================================
 */

$configuracionLogo = (new Configuracion())->obtener();
$rutaLogo = $configuracionLogo['logo_ruta'] ?? null;
?>
<?php if ($rutaLogo !== null && $rutaLogo !== ''): ?>
    <img
        class="logo-imagen"
        src="<?= e(View::url($rutaLogo)) ?>"
        alt="<?= e($configuracionLogo['nombre_negocio'] ?? 'Carpin') ?>"
    >
<?php else: ?>
    <span class="logo-provisional">
        <i class="bi bi-tree-fill" aria-hidden="true"></i>
        <span>Carpin</span>
    </span>
<?php endif; ?>
