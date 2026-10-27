<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/admin/catalogo.php
 * Lista de productos del catálogo para el Administrador
 * (RF-12): editar, ocultar / mostrar y eliminar.
 * Variable: $productos, todos los productos.
 * =====================================================
 */

View::$titulo = 'Catálogo';
?>
<div class="encabezado-pagina">
    <h1 class="titulo-pagina mb-0">Catálogo</h1>
    <a class="btn btn-carpin-primario" href="<?= e(View::url('/admin/catalogo/nuevo')) ?>">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
        Nuevo producto
    </a>
</div>

<?php if ($productos === []): ?>
    <p class="estado-vacio">Todavía no hay productos en el catálogo.</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas">
        <thead>
            <tr>
                <th scope="col">Producto</th>
                <th scope="col">Categoría</th>
                <th scope="col">Precio desde</th>
                <th scope="col">Estado</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $producto): ?>
                <?php
                $idProducto = (int) $producto['id_producto'];
                $esVisible = (int) $producto['activo'] === 1;
                $urlProducto = '/admin/catalogo/' . $idProducto;
                $urlVisibilidad = View::url($urlProducto . '/visibilidad');
                $textoPrecio = ($producto['precio_desde'] !== null)
                    ? dinero($producto['precio_desde'])
                    : '—';
                $confirmarEliminar = '¿Seguro que quieres eliminar "' . $producto['nombre']
                    . '"? Esta acción no se puede deshacer.';
                ?>
                <tr>
                    <td data-etiqueta="Producto">
                        <span class="d-flex align-items-center gap-2">
                            <?php if ($producto['imagen_ruta'] !== null): ?>
                                <img
                                    class="miniatura"
                                    src="<?= e(View::url($producto['imagen_ruta'])) ?>"
                                    alt=""
                                >
                            <?php endif; ?>
                            <span>
                                <strong><?= e($producto['nombre']) ?></strong>
                                <?php if ((int) $producto['destacado'] === 1): ?>
                                    <span class="etiqueta etiqueta--acento">Destacado</span>
                                <?php endif; ?>
                            </span>
                        </span>
                    </td>
                    <td data-etiqueta="Categoría"><?= e($producto['categoria_nombre']) ?></td>
                    <td data-etiqueta="Precio desde">
                        <?= e($textoPrecio) ?>
                    </td>
                    <td data-etiqueta="Estado">
                        <?php if ($esVisible): ?>
                            <span class="etiqueta etiqueta--exito">Visible</span>
                        <?php else: ?>
                            <span class="etiqueta">Oculto</span>
                        <?php endif; ?>
                    </td>
                    <td data-etiqueta="Acciones">
                        <div class="acciones-tabla">
                            <a href="<?= e(View::url($urlProducto . '/editar')) ?>">Editar</a>

                            <form method="post" action="<?= e($urlVisibilidad) ?>">
                                <?= Csrf::campo() ?>
                                <input
                                    type="hidden"
                                    name="accion"
                                    value="<?= $esVisible ? 'ocultar' : 'mostrar' ?>"
                                >
                                <button class="btn btn-link p-0" type="submit">
                                    <?= $esVisible ? 'Ocultar' : 'Mostrar' ?>
                                </button>
                            </form>

                            <form
                                method="post"
                                action="<?= e(View::url($urlProducto . '/eliminar')) ?>"
                                data-confirmar="<?= e($confirmarEliminar) ?>"
                            >
                                <?= Csrf::campo() ?>
                                <button class="btn btn-link p-0 texto-peligro" type="submit">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
