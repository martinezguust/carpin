<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/proyecto_nuevo.php
 * Formulario de registro de proyecto con cuenta del cliente
 * (CU-03, RF-15 y RF-16).
 * Variables: $datos, $errores, $clientes, $acabados,
 * $productosFicha, $metodos, $hoy.
 * =====================================================
 */

View::$titulo = 'Nuevo proyecto';
?>
<a class="enlace-regreso" href="<?= e(View::url('/proyectos')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Proyectos
</a>

<h1 class="titulo-pagina">Nuevo proyecto</h1>

<?php if ($errores !== []): ?>
    <div class="alert alert-danger" role="alert">
        Revisa los campos marcados en rojo.
    </div>
<?php endif; ?>

<?php if ($datos['id_solicitud'] !== ''): ?>
    <!-- Proyecto que viene de "Convertir en proyecto" (RF-14) -->
    <div class="alert <?= isset($errores['id_solicitud']) ? 'alert-danger' : 'alert-info' ?>">
        <?= e($errores['id_solicitud'] ?? 'Datos precargados de la solicitud de cotización. '
            . 'Revísalos y completa el resto.') ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(View::url('/proyectos')) ?>" novalidate>
    <?= Csrf::campo() ?>
    <input type="hidden" name="id_solicitud" value="<?= e($datos['id_solicitud']) ?>">

    <!-- ===== 1. Cliente ===== -->
    <?php require __DIR__ . '/_cliente_formulario.php'; ?>

    <!-- ===== 2. Mueble ===== -->
    <section class="tarjeta-carpin mb-4">
        <h2 class="titulo-seccion">Mueble</h2>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="tipo_mueble">Tipo de mueble</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'tipo_mueble') ?>"
                    type="text"
                    id="tipo_mueble"
                    name="tipo_mueble"
                    maxlength="60"
                    placeholder="Mesa, silla, ropero…"
                    value="<?= e($datos['tipo_mueble']) ?>"
                >
                <?= mensajeError($errores, 'tipo_mueble') ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="madera">Tipo de madera</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'madera') ?>"
                    type="text"
                    id="madera"
                    name="madera"
                    maxlength="60"
                    placeholder="Pino, cedro, roble, MDF…"
                    value="<?= e($datos['madera']) ?>"
                >
                <?= mensajeError($errores, 'madera') ?>
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

            <?php
            $medidas = [
                'alto_cm'  => 'Alto',
                'ancho_cm' => 'Ancho',
                'fondo_cm' => 'Fondo',
            ];
            ?>

            <?php foreach ($medidas as $campo => $etiqueta): ?>
                <div class="col-4">
                    <label class="form-label" for="<?= e($campo) ?>">
                        <?= e($etiqueta) ?> (cm)
                    </label>
                    <input
                        class="form-control <?= claseInvalida($errores, $campo) ?>"
                        type="number"
                        id="<?= e($campo) ?>"
                        name="<?= e($campo) ?>"
                        min="0.1"
                        step="0.1"
                        inputmode="decimal"
                        value="<?= e($datos[$campo]) ?>"
                    >
                    <?= mensajeError($errores, $campo) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ===== 3. Acabado y producción ===== -->
    <section class="tarjeta-carpin mb-4">
        <h2 class="titulo-seccion">Acabado y producción</h2>

        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label" for="acabado">Acabado</label>
                <select
                    class="form-select <?= claseInvalida($errores, 'acabado') ?>"
                    id="acabado"
                    name="acabado"
                >
                    <option value="">Sin definir</option>
                    <?php foreach ($acabados as $acabado): ?>
                        <option
                            value="<?= e($acabado) ?>"
                            <?= seleccionado($datos['acabado'] === $acabado) ?>
                        >
                            <?= e($acabado) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= mensajeError($errores, 'acabado') ?>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="color">Color</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'color') ?>"
                    type="text"
                    id="color"
                    name="color"
                    maxlength="60"
                    value="<?= e($datos['color']) ?>"
                >
                <?= mensajeError($errores, 'color') ?>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="granulado_lija">Granulado de lija</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'granulado_lija') ?>"
                    type="text"
                    id="granulado_lija"
                    name="granulado_lija"
                    maxlength="40"
                    placeholder="Ej. 80-120-220"
                    value="<?= e($datos['granulado_lija']) ?>"
                >
                <?= mensajeError($errores, 'granulado_lija') ?>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="ficha_producto">Ficha de color: producto</label>
                <select
                    class="form-select <?= claseInvalida($errores, 'ficha_producto') ?>"
                    id="ficha_producto"
                    name="ficha_producto"
                >
                    <option value="">Sin definir</option>
                    <?php foreach ($productosFicha as $producto): ?>
                        <option
                            value="<?= e($producto) ?>"
                            <?= seleccionado($datos['ficha_producto'] === $producto) ?>
                        >
                            <?= e($producto) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= mensajeError($errores, 'ficha_producto') ?>
            </div>

            <div class="col-12 col-md-8">
                <label class="form-label" for="ficha_notas">Ficha de color: notas</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'ficha_notas') ?>"
                    type="text"
                    id="ficha_notas"
                    name="ficha_notas"
                    maxlength="1000"
                    placeholder="Tono, preparación, indicaciones para el pintor…"
                    value="<?= e($datos['ficha_notas']) ?>"
                >
                <?= mensajeError($errores, 'ficha_notas') ?>
            </div>
        </div>
    </section>

    <!-- ===== 4. Fechas y pago ===== -->
    <section class="tarjeta-carpin mb-4">
        <h2 class="titulo-seccion">Fechas y pago</h2>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="fecha_entrega">Fecha de entrega</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'fecha_entrega') ?>"
                    type="date"
                    id="fecha_entrega"
                    name="fecha_entrega"
                    min="<?= e($hoy) ?>"
                    value="<?= e($datos['fecha_entrega']) ?>"
                >
                <?= mensajeError($errores, 'fecha_entrega') ?>
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
                <label class="form-label" for="precio">Precio ($ MXN)</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'precio') ?>"
                    type="number"
                    id="precio"
                    name="precio"
                    min="0.01"
                    step="0.01"
                    inputmode="decimal"
                    value="<?= e($datos['precio']) ?>"
                >
                <?= mensajeError($errores, 'precio') ?>
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
        <button class="btn btn-carpin-primario" type="submit">Registrar proyecto</button>
        <a class="btn btn-carpin-secundario" href="<?= e(View::url('/proyectos')) ?>">Cancelar</a>
    </div>
</form>
