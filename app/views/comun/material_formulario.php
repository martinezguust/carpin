<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/material_formulario.php
 * Alta y edición de material (RF-37). En el alta se pide la
 * existencia inicial, que se guarda como entrada "Existencia
 * inicial"; después la existencia solo cambia con
 * movimientos (respuesta 2 de la Fase 3).
 * Variables: $material (null si es nuevo), $datos, $errores,
 * $categorias, $unidades.
 * =====================================================
 */

$esNuevo = $material === null;

View::$titulo = $esNuevo ? 'Nuevo material' : 'Editar material';

if ($esNuevo) {
    $accion = View::url('/inventario');
    $urlRegreso = View::url('/inventario');
} else {
    $accion = View::url('/inventario/' . $material['id_material']);
    $urlRegreso = $accion;
}
?>
<a class="enlace-regreso" href="<?= e($urlRegreso) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    <?= $esNuevo ? 'Inventario' : e($material['nombre']) ?>
</a>

<h1 class="titulo-pagina"><?= e(View::$titulo) ?></h1>

<section class="tarjeta-carpin formulario-mediano">
    <form method="post" action="<?= e($accion) ?>" novalidate>
        <?= Csrf::campo() ?>

        <div class="row g-3">
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

            <div class="col-12 col-md-6">
                <label class="form-label" for="categoria">Categoría</label>
                <select
                    class="form-select <?= claseInvalida($errores, 'categoria') ?>"
                    id="categoria"
                    name="categoria"
                    required
                >
                    <option value="">Elige una categoría</option>
                    <?php foreach ($categorias as $clave => $nombre): ?>
                        <option
                            value="<?= e($clave) ?>"
                            <?= seleccionado($datos['categoria'] === $clave) ?>
                        >
                            <?= e($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= mensajeError($errores, 'categoria') ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="unidad">Unidad</label>
                <select
                    class="form-select <?= claseInvalida($errores, 'unidad') ?>"
                    id="unidad"
                    name="unidad"
                    required
                >
                    <option value="">Elige una unidad</option>
                    <?php foreach ($unidades as $clave => $unidad): ?>
                        <option
                            value="<?= e($clave) ?>"
                            <?= seleccionado($datos['unidad'] === $clave) ?>
                        >
                            <?= e($unidad['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= mensajeError($errores, 'unidad') ?>
            </div>

            <?php if ($esNuevo): ?>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="existencia">Existencia inicial</label>
                    <input
                        class="form-control <?= claseInvalida($errores, 'existencia') ?>"
                        type="number"
                        id="existencia"
                        name="existencia"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="0"
                        value="<?= e($datos['existencia']) ?>"
                    >
                    <?= mensajeError($errores, 'existencia') ?>
                </div>
            <?php endif; ?>

            <div class="col-12 col-md-4">
                <label class="form-label" for="minimo">Mínimo</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'minimo') ?>"
                    type="number"
                    id="minimo"
                    name="minimo"
                    min="0"
                    step="0.01"
                    inputmode="decimal"
                    value="<?= e($datos['minimo']) ?>"
                    required
                >
                <?= mensajeError($errores, 'minimo') ?>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="costo_unitario">Costo unitario ($ MXN)</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'costo_unitario') ?>"
                    type="number"
                    id="costo_unitario"
                    name="costo_unitario"
                    min="0"
                    step="0.01"
                    inputmode="decimal"
                    value="<?= e($datos['costo_unitario']) ?>"
                    required
                >
                <?= mensajeError($errores, 'costo_unitario') ?>
            </div>
        </div>

        <p class="texto-ayuda mt-3">
            <?php if ($esNuevo): ?>
                La existencia inicial se registra como una entrada. Después, la existencia
                solo cambia con entradas y salidas.
            <?php else: ?>
                La existencia no se edita aquí: cambia con entradas y salidas.
            <?php endif; ?>
            Cuando la existencia llegue al mínimo se avisará al Carpintero y al Contador.
        </p>

        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-carpin-primario" type="submit">Guardar material</button>
            <a class="btn btn-carpin-secundario" href="<?= e($urlRegreso) ?>">Cancelar</a>
        </div>
    </form>
</section>
