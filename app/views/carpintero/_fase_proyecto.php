<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/_fase_proyecto.php
 * Tarjeta de la fase actual del proyecto: barra de avance,
 * botón de avance normal del Carpintero (1→2→3→4 y 9→10)
 * con advertencia de saldo (RN-04) y formulario de
 * corrección con motivo obligatorio (RN-03).
 * Variables (de proyecto_detalle.php): $proyecto, $saldo,
 * $fases, $siguienteFase, $idProyecto, $idFaseActual, $avance.
 * =====================================================
 */

$urlAvanzar = View::url('/proyectos/' . $idProyecto . '/avanzar');
$urlCorregir = View::url('/proyectos/' . $idProyecto . '/corregir');
$entregaConSaldo = $siguienteFase !== null
    && (int) $siguienteFase['id_fase'] === Fase::ENTREGADO
    && $saldo > 0;
?>
<section class="tarjeta-carpin mb-4">
    <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2 mb-2">
        <h2 class="titulo-seccion mb-0">Fase: <?= e($proyecto['fase_nombre']) ?></h2>
        <span class="fw-semibold"><?= e($proyecto['porcentaje_avance']) ?> %</span>
    </div>

    <div
        class="barra-avance barra-avance--grande mb-3"
        role="progressbar"
        aria-label="Avance del proyecto"
        aria-valuenow="<?= e($avance) ?>"
        aria-valuemin="0"
        aria-valuemax="100"
    >
        <span class="barra-avance__relleno" style="width: <?= e($avance) ?>%"></span>
    </div>

    <?php if ($siguienteFase !== null): ?>
        <!-- Avance normal del Carpintero -->
        <form method="post" action="<?= e($urlAvanzar) ?>">
            <?= Csrf::campo() ?>

            <?php if ($entregaConSaldo): ?>
                <!-- RN-04: advertencia de saldo pendiente -->
                <div class="alert alert-warning" role="alert">
                    <p class="mb-2">
                        El cliente tiene un saldo pendiente de
                        <strong><?= e(dinero($saldo)) ?></strong>.
                    </p>
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="confirmarSaldoAvance"
                            name="confirmar_saldo"
                            value="1"
                            required
                        >
                        <label class="form-check-label" for="confirmarSaldoAvance">
                            Confirmo que se entrega con saldo pendiente.
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <button class="btn btn-carpin-accion" type="submit">
                Marcar como terminada → <?= e($siguienteFase['nombre']) ?>
            </button>
        </form>
    <?php endif; ?>

    <!-- Corrección de fase con motivo obligatorio (RN-03) -->
    <details class="correccion-fase mt-3">
        <summary>Corregir fase</summary>

        <form
            class="mt-3"
            method="post"
            action="<?= e($urlCorregir) ?>"
            data-confirmar="¿Seguro que quieres cambiar la fase del proyecto?"
        >
            <?= Csrf::campo() ?>

            <div class="row g-3">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="id_fase">Nueva fase</label>
                    <select class="form-select" id="id_fase" name="id_fase" required>
                        <option value="">Elige una fase</option>
                        <?php foreach ($fases as $idFase => $fase): ?>
                            <?php if ($idFase !== $idFaseActual): ?>
                                <option value="<?= e($idFase) ?>">
                                    <?= e($fase['orden']) ?>. <?= e($fase['nombre']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-7">
                    <label class="form-label" for="motivo">Motivo (obligatorio)</label>
                    <input
                        class="form-control"
                        type="text"
                        id="motivo"
                        name="motivo"
                        maxlength="255"
                        required
                    >
                </div>
            </div>

            <?php if ($saldo > 0): ?>
                <div class="form-check mt-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="confirmarSaldoCorreccion"
                        name="confirmar_saldo"
                        value="1"
                    >
                    <label class="form-check-label" for="confirmarSaldoCorreccion">
                        Si elijo "Entregado", confirmo que se entrega con saldo pendiente de
                        <?= e(dinero($saldo)) ?>.
                    </label>
                </div>
            <?php endif; ?>

            <button class="btn btn-carpin-secundario mt-3" type="submit">
                Guardar corrección
            </button>
        </form>
    </details>
</section>
