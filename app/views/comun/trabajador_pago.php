<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/trabajador_pago.php
 * Forma de pago y tarifa de un trabajador (HU-30, RF-43):
 * por hora con tarifa, por día con salario o por proyecto
 * (solo Lijador y Pintor).
 * Variables: $trabajador, $datos, $errores, $permitidas,
 * $rutaLista.
 * =====================================================
 */

View::$titulo = 'Forma de pago';
?>
<a class="enlace-regreso" href="<?= e(View::url($rutaLista)) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Regresar
</a>

<h1 class="titulo-pagina"><?= e($trabajador['nombre_completo']) ?></h1>
<p class="subtitulo-pagina"><?= e(etiquetaRol($trabajador['rol'])) ?></p>

<section class="tarjeta-carpin formulario-angosto">
    <form
        method="post"
        action="<?= e(View::url('/trabajadores/' . $trabajador['id_trabajador'])) ?>"
        novalidate
    >
        <?= Csrf::campo() ?>

        <div class="mb-3">
            <label class="form-label" for="forma_pago">Forma de pago</label>
            <select
                class="form-select <?= claseInvalida($errores, 'forma_pago') ?>"
                id="forma_pago"
                name="forma_pago"
                required
            >
                <option value="">Elige una forma de pago</option>
                <?php foreach ($permitidas as $forma): ?>
                    <option
                        value="<?= e($forma) ?>"
                        <?= seleccionado($datos['forma_pago'] === $forma) ?>
                    >
                        <?= e(etiquetaFormaPago($forma)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= mensajeError($errores, 'forma_pago') ?>
        </div>

        <div class="mb-3" data-forma-pago="HORA">
            <label class="form-label" for="tarifa_hora">Tarifa por hora ($ MXN)</label>
            <input
                class="form-control <?= claseInvalida($errores, 'tarifa_hora') ?>"
                type="number"
                id="tarifa_hora"
                name="tarifa_hora"
                min="0.01"
                step="0.01"
                inputmode="decimal"
                value="<?= e($datos['tarifa_hora']) ?>"
            >
            <?= mensajeError($errores, 'tarifa_hora') ?>
        </div>

        <div class="mb-3" data-forma-pago="DIA">
            <label class="form-label" for="salario_dia">Salario diario ($ MXN)</label>
            <input
                class="form-control <?= claseInvalida($errores, 'salario_dia') ?>"
                type="number"
                id="salario_dia"
                name="salario_dia"
                min="0.01"
                step="0.01"
                inputmode="decimal"
                value="<?= e($datos['salario_dia']) ?>"
            >
            <?= mensajeError($errores, 'salario_dia') ?>
        </div>

        <p class="texto-ayuda" data-forma-pago="PROYECTO">
            Por proyecto no lleva tarifa: recibe su parte de la mano de obra de cada proyecto
            entregado en el que tuvo actividad, según el porcentaje de su rol.
        </p>

        <button class="btn btn-carpin-primario" type="submit">Guardar forma de pago</button>
    </form>
</section>
