<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/admin/producto_formulario.php
 * Formulario de producto del catálogo (RF-12): nuevo o
 * edición, con imagen JPG, PNG o WEBP de máximo 5 MB.
 * Variables: $producto (null si es nuevo), $datos, $errores,
 * $categorias.
 * =====================================================
 */

$esNuevo = $producto === null;

View::$titulo = $esNuevo ? 'Nuevo producto' : 'Editar producto';

if ($esNuevo) {
    $accion = View::url('/admin/catalogo');
} else {
    $accion = View::url('/admin/catalogo/' . $producto['id_producto']);
}
?>
<a class="enlace-regreso" href="<?= e(View::url('/admin/catalogo')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Catálogo
</a>

<h1 class="titulo-pagina"><?= e(View::$titulo) ?></h1>

<section class="tarjeta-carpin formulario-mediano">
    <form method="post" action="<?= e($accion) ?>" enctype="multipart/form-data" novalidate>
        <?= Csrf::campo() ?>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="id_categoria">Categoría</label>
                <select
                    class="form-select <?= claseInvalida($errores, 'id_categoria') ?>"
                    id="id_categoria"
                    name="id_categoria"
                    required
                >
                    <option value="">Elige una categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <?php $idCategoria = (string) $categoria['id_categoria']; ?>
                        <option
                            value="<?= e($idCategoria) ?>"
                            <?= seleccionado($datos['id_categoria'] === $idCategoria) ?>
                        >
                            <?= e($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= mensajeError($errores, 'id_categoria') ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="precio_desde">Precio desde ($ MXN, opcional)</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'precio_desde') ?>"
                    type="number"
                    id="precio_desde"
                    name="precio_desde"
                    min="0.01"
                    step="0.01"
                    inputmode="decimal"
                    value="<?= e($datos['precio_desde']) ?>"
                >
                <?= mensajeError($errores, 'precio_desde') ?>
            </div>

            <div class="col-12">
                <label class="form-label" for="nombre">Nombre</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'nombre') ?>"
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="100"
                    value="<?= e($datos['nombre']) ?>"
                    required
                >
                <?= mensajeError($errores, 'nombre') ?>
            </div>

            <div class="col-12">
                <label class="form-label" for="descripcion">Descripción (opcional)</label>
                <textarea
                    class="form-control <?= claseInvalida($errores, 'descripcion') ?>"
                    id="descripcion"
                    name="descripcion"
                    rows="3"
                    maxlength="2000"
                ><?= e($datos['descripcion']) ?></textarea>
                <?= mensajeError($errores, 'descripcion') ?>
            </div>

            <div class="col-12">
                <label class="form-label" for="imagen">
                    <?= $esNuevo ? 'Imagen (opcional)' : 'Cambiar imagen (opcional)' ?>
                </label>

                <?php if (!$esNuevo && $producto['imagen_ruta'] !== null): ?>
                    <img
                        class="imagen-actual"
                        src="<?= e(View::url($producto['imagen_ruta'])) ?>"
                        alt="Imagen actual de <?= e($producto['nombre']) ?>"
                    >
                <?php endif; ?>

                <input
                    class="form-control <?= claseInvalida($errores, 'imagen') ?>"
                    type="file"
                    id="imagen"
                    name="imagen"
                    accept="image/jpeg,image/png,image/webp"
                >
                <?= mensajeError($errores, 'imagen') ?>
                <p class="texto-ayuda mt-1 mb-0">JPG, PNG o WEBP de máximo 5 MB.</p>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="destacado"
                        name="destacado"
                        value="1"
                        <?= $datos['destacado'] ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="destacado">
                        Mostrar en "Productos destacados" de la página de inicio
                    </label>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <button class="btn btn-carpin-primario" type="submit">Guardar producto</button>
            <a class="btn btn-carpin-secundario" href="<?= e(View::url('/admin/catalogo')) ?>">
                Cancelar
            </a>
        </div>
    </form>
</section>
