<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/porcentajes.php
 * Porcentajes (HU-30): mano de obra por rol en el pago por
 * proyecto (RF-44, deben sumar 100 %) y mano de obra y
 * ganancia de los presupuestos (RF-55). Para el Carpintero
 * ("Precios y porcentajes") también muestra las tarifas de
 * los trabajadores (respuesta 16).
 * Variables: $porcentajesRol, $datosRol, $datosPresupuesto,
 * $errores, $esCarpintero, $trabajadores.
 * =====================================================
 */

View::$titulo = $esCarpintero ? 'Precios y porcentajes' : 'Porcentajes';
?>
<h1 class="titulo-pagina"><?= e(View::$titulo) ?></h1>

<div class="row g-4">

    <!-- ===== Mano de obra por rol (RF-44) ===== -->
    <div class="col-12 col-lg-6">
        <section class="tarjeta-carpin h-100">
            <h2 class="titulo-seccion">Mano de obra por rol</h2>
            <p class="texto-ayuda">
                Parte de la mano de obra de cada proyecto que le toca a cada rol cuando se paga
                por proyecto. Entre todos deben sumar 100 %. El Carpintero y el Administrador no
                participan en este reparto.
            </p>

            <?php if (isset($errores['suma_roles'])): ?>
                <div class="alert alert-danger" role="alert"><?= e($errores['suma_roles']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(View::url('/porcentajes/roles')) ?>" novalidate>
                <?= Csrf::campo() ?>

                <?php foreach ($porcentajesRol as $fila): ?>
                    <?php
                    $idRol = (int) $fila['id_rol'];
                    $campoError = 'rol_' . $idRol;
                    ?>
                    <div class="mb-3">
                        <label class="form-label" for="porcentaje<?= e($idRol) ?>">
                            <?= e(etiquetaRol($fila['rol'])) ?> (%)
                        </label>
                        <input
                            class="form-control <?= claseInvalida($errores, $campoError) ?>"
                            type="number"
                            id="porcentaje<?= e($idRol) ?>"
                            name="porcentaje[<?= e($idRol) ?>]"
                            min="0"
                            max="100"
                            step="0.01"
                            inputmode="decimal"
                            value="<?= e($datosRol[$idRol] ?? '') ?>"
                            required
                        >
                        <?= mensajeError($errores, $campoError) ?>
                    </div>
                <?php endforeach; ?>

                <button class="btn btn-carpin-primario" type="submit">
                    Guardar porcentajes por rol
                </button>
            </form>
        </section>
    </div>

    <!-- ===== Presupuestos (RF-55) ===== -->
    <div class="col-12 col-lg-6">
        <section class="tarjeta-carpin h-100">
            <h2 class="titulo-seccion">Presupuestos</h2>
            <p class="texto-ayuda">
                Se usan al calcular un presupuesto: mano de obra = materiales × % mano de obra;
                ganancia = (materiales + mano de obra) × % ganancia.
            </p>

            <form
                method="post"
                action="<?= e(View::url('/porcentajes/presupuestos')) ?>"
                novalidate
            >
                <?= Csrf::campo() ?>

                <div class="mb-3">
                    <label class="form-label" for="pct_mano_obra">Mano de obra (%)</label>
                    <input
                        class="form-control <?= claseInvalida($errores, 'pct_mano_obra') ?>"
                        type="number"
                        id="pct_mano_obra"
                        name="pct_mano_obra"
                        min="0"
                        max="100"
                        step="0.01"
                        inputmode="decimal"
                        value="<?= e($datosPresupuesto['pct_mano_obra']) ?>"
                        required
                    >
                    <?= mensajeError($errores, 'pct_mano_obra') ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="pct_ganancia">Ganancia (%)</label>
                    <input
                        class="form-control <?= claseInvalida($errores, 'pct_ganancia') ?>"
                        type="number"
                        id="pct_ganancia"
                        name="pct_ganancia"
                        min="0"
                        max="100"
                        step="0.01"
                        inputmode="decimal"
                        value="<?= e($datosPresupuesto['pct_ganancia']) ?>"
                        required
                    >
                    <?= mensajeError($errores, 'pct_ganancia') ?>
                </div>

                <button class="btn btn-carpin-primario" type="submit">
                    Guardar porcentajes de presupuestos
                </button>
            </form>
        </section>
    </div>

</div>

<?php if ($esCarpintero): ?>
    <!-- ===== Tarifas de los trabajadores (respuesta 16) ===== -->
    <h2 class="titulo-seccion mt-5">Tarifas de los trabajadores</h2>
    <?php require __DIR__ . '/_tabla_trabajadores.php'; ?>
<?php endif; ?>
