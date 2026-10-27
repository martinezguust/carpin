<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/reparacion_nueva.php
 * Formulario de registro de reparación (CU-08, RF-33, RF-34):
 * dueño, datos del mueble y del daño, costo, fechas,
 * anticipo y de 1 a 6 fotos de "antes".
 * Variables: $datos, $errores, $clientes, $tamanos, $metodos, $hoy.
 * =====================================================
 */

View::$titulo = 'Nueva reparación';
?>
<a class="enlace-regreso" href="<?= e(View::url('/reparaciones')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Reparaciones
</a>

<h1 class="titulo-pagina">Nueva reparación</h1>

<?php if ($errores !== []): ?>
    <div class="alert alert-danger" role="alert">
        Revisa los campos marcados en rojo. Por seguridad, las fotos se deben elegir de nuevo.
    </div>
<?php endif; ?>

<form
    method="post"
    action="<?= e(View::url('/reparaciones')) ?>"
    enctype="multipart/form-data"
    novalidate
>
    <?= Csrf::campo() ?>

    <!-- ===== 1. Dueño del mueble ===== -->
    <?php require __DIR__ . '/_cliente_formulario.php'; ?>

    <!-- ===== 2. Mueble y reparación ===== -->
    <section class="tarjeta-carpin mb-4">
        <h2 class="titulo-seccion">Mueble y reparación</h2>

        <div class="row g-3">
            <div class="col-12 col-md-8">
                <label class="form-label" for="descripcion_mueble">Descripción del mueble</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'descripcion_mueble') ?>"
                    type="text"
                    id="descripcion_mueble"
                    name="descripcion_mueble"
                    maxlength="150"
                    placeholder="Ej. Silla de comedor de cedro"
                    value="<?= e($datos['descripcion_mueble']) ?>"
                >
                <?= mensajeError($errores, 'descripcion_mueble') ?>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="tamano">Tamaño</label>
                <select
                    class="form-select <?= claseInvalida($errores, 'tamano') ?>"
                    id="tamano"
                    name="tamano"
                >
                    <option value="">Elige el tamaño</option>
                    <?php foreach ($tamanos as $clave => $tamano): ?>
                        <option
                            value="<?= e($clave) ?>"
                            <?= seleccionado($datos['tamano'] === $clave) ?>
                        >
                            <?= e($tamano) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= mensajeError($errores, 'tamano') ?>
            </div>

            <div class="col-12">
                <label class="form-label" for="tipo_reparacion">Tipo de reparación</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'tipo_reparacion') ?>"
                    type="text"
                    id="tipo_reparacion"
                    name="tipo_reparacion"
                    maxlength="80"
                    placeholder="Ej. cambio de patas, retapizado, barnizado"
                    value="<?= e($datos['tipo_reparacion']) ?>"
                >
                <?= mensajeError($errores, 'tipo_reparacion') ?>
            </div>

            <div class="col-12">
                <label class="form-label" for="descripcion_danio">
                    Descripción del daño (opcional)
                </label>
                <textarea
                    class="form-control <?= claseInvalida($errores, 'descripcion_danio') ?>"
                    id="descripcion_danio"
                    name="descripcion_danio"
                    rows="3"
                    maxlength="2000"
                ><?= e($datos['descripcion_danio']) ?></textarea>
                <?= mensajeError($errores, 'descripcion_danio') ?>
            </div>

            <div class="col-12">
                <label class="form-label" for="fotos">Fotos de "antes" (de 1 a 6)</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'fotos') ?>"
                    type="file"
                    id="fotos"
                    name="fotos[]"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                >
                <?= mensajeError($errores, 'fotos') ?>
                <p class="texto-ayuda mt-1 mb-0">
                    JPG, PNG o WEBP de máximo 5 MB cada una. Las fotos de "después" se suben
                    desde el detalle de la reparación (máximo 6 fotos en total).
                </p>
            </div>
        </div>
    </section>

    <!-- ===== 3. Fechas y pago ===== -->
    <section class="tarjeta-carpin mb-4">
        <h2 class="titulo-seccion">Fechas y pago</h2>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="fecha_estimada">Fecha estimada de entrega</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'fecha_estimada') ?>"
                    type="date"
                    id="fecha_estimada"
                    name="fecha_estimada"
                    min="<?= e($hoy) ?>"
                    value="<?= e($datos['fecha_estimada']) ?>"
                >
                <?= mensajeError($errores, 'fecha_estimada') ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="fecha_limite_pago">Fecha límite de pago</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'fecha_limite_pago') ?>"
                    type="date"
                    id="fecha_limite_pago"
                    name="fecha_limite_pago"
                    min="<?= e($hoy) ?>"
                    value="<?= e($datos['fecha_limite_pago']) ?>"
                >
                <?= mensajeError($errores, 'fecha_limite_pago') ?>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="costo">Costo ($ MXN)</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'costo') ?>"
                    type="number"
                    id="costo"
                    name="costo"
                    min="0.01"
                    step="0.01"
                    inputmode="decimal"
                    value="<?= e($datos['costo']) ?>"
                >
                <?= mensajeError($errores, 'costo') ?>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="anticipo">Anticipo ($ MXN)</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'anticipo') ?>"
                    type="number"
                    id="anticipo"
                    name="anticipo"
                    min="0"
                    step="0.01"
                    inputmode="decimal"
                    placeholder="0.00"
                    value="<?= e($datos['anticipo']) ?>"
                >
                <?= mensajeError($errores, 'anticipo') ?>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="metodo_anticipo">Método del anticipo</label>
                <select
                    class="form-select <?= claseInvalida($errores, 'metodo_anticipo') ?>"
                    id="metodo_anticipo"
                    name="metodo_anticipo"
                >
                    <option value="">Elige un método</option>
                    <?php foreach ($metodos as $clave => $metodo): ?>
                        <option
                            value="<?= e($clave) ?>"
                            <?= seleccionado($datos['metodo_anticipo'] === $clave) ?>
                        >
                            <?= e($metodo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= mensajeError($errores, 'metodo_anticipo') ?>
            </div>
        </div>

        <p class="texto-ayuda mt-2 mb-0">
            El método del anticipo solo es obligatorio si el anticipo es mayor a $0.00.
            El sistema no cobra: solo registra el pago hecho en la carpintería.
        </p>
    </section>

    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-carpin-primario" type="submit">Registrar reparación</button>
        <a class="btn btn-carpin-secundario" href="<?= e(View::url('/reparaciones')) ?>">
            Cancelar
        </a>
    </div>
</form>
