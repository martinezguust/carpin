<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/publico/_producto.php
 * Tarjeta de un producto del catálogo (RF-09, mockup 1):
 * foto (o imagen provisional), categoría, nombre,
 * descripción, "Desde $X" y botón "Cotizar".
 * Variable: $producto, datos del producto con su categoría.
 * =====================================================
 */

$urlCotizar = View::url('/cotizacion?producto=' . $producto['id_producto']);
?>
<article class="tarjeta-producto">
    <?php if ($producto['imagen_ruta'] !== null): ?>
        <img
            class="tarjeta-producto__foto"
            src="<?= e(View::url($producto['imagen_ruta'])) ?>"
            alt="<?= e($producto['nombre']) ?>"
            loading="lazy"
        >
    <?php else: ?>
        <!-- Sin foto todavía: imagen provisional con la paleta -->
        <div class="tarjeta-producto__foto tarjeta-producto__foto--provisional">
            <i class="bi bi-tree" aria-hidden="true"></i>
        </div>
    <?php endif; ?>

    <div class="tarjeta-producto__cuerpo">
        <p class="tarjeta-producto__categoria"><?= e($producto['categoria_nombre']) ?></p>
        <h3 class="tarjeta-producto__nombre"><?= e($producto['nombre']) ?></h3>

        <?php if ($producto['descripcion'] !== null): ?>
            <p class="tarjeta-producto__descripcion"><?= e($producto['descripcion']) ?></p>
        <?php endif; ?>

        <?php if ($producto['precio_desde'] !== null): ?>
            <p class="tarjeta-producto__precio">
                Desde <strong><?= e(dinero($producto['precio_desde'])) ?></strong>
            </p>
        <?php endif; ?>

        <a class="btn btn-carpin-secundario w-100 mt-auto" href="<?= e($urlCotizar) ?>">
            Cotizar
        </a>
    </div>
</article>
