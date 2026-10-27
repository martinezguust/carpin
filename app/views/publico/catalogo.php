<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/publico/catalogo.php
 * Catálogo público con filtro por categoría (RF-09, mockup
 * 1): todas las categorías, incluidas Estantes y Otros.
 * Variables: $productos, $categorias, $idCategoria.
 * =====================================================
 */

View::$titulo = 'Catálogo';

$claseTodos = ($idCategoria === 0) ? 'filtro-categorias__opcion--activa' : '';
?>
<section class="seccion-publica">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h1 class="titulo-publico mb-0">Catálogo</h1>

            <!-- Filtro por categoría -->
            <nav class="filtro-categorias" aria-label="Filtrar por categoría">
                <a
                    class="filtro-categorias__opcion <?= e($claseTodos) ?>"
                    href="<?= e(View::url('/catalogo')) ?>"
                >Todos</a>
                <?php foreach ($categorias as $categoria): ?>
                    <?php
                    $esActiva = $idCategoria === (int) $categoria['id_categoria'];
                    $claseCategoria = $esActiva ? 'filtro-categorias__opcion--activa' : '';
                    $urlCategoria = View::url('/catalogo?categoria=' . $categoria['id_categoria']);
                    ?>
                    <a
                        class="filtro-categorias__opcion <?= e($claseCategoria) ?>"
                        href="<?= e($urlCategoria) ?>"
                        <?= $esActiva ? 'aria-current="page"' : '' ?>
                    ><?= e($categoria['nombre']) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>

        <?php if ($productos === []): ?>
            <p class="estado-vacio">Por ahora no hay productos en esta categoría.</p>
        <?php else: ?>
            <div class="rejilla-productos">
                <?php foreach ($productos as $producto): ?>
                    <?php require __DIR__ . '/_producto.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="texto-publico mt-4 mb-0">
            ¿No encuentras lo que buscas? Fabricamos a la medida:
            <a
                class="enlace-publico"
                href="<?= e(View::url('/cotizacion')) ?>"
            >solicita una cotización</a>.
        </p>
    </div>
</section>
