<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/cliente/reparacion.php
 * Estado de una reparación para su dueño (RF-36, RN-05):
 * gráfica de dona con el avance, estados, aviso de "listo
 * para recoger", fotos y resumen de pago.
 * Variables: $reparacion, $estados, $imagenes, $saldo,
 * $mensajeListo.
 * =====================================================
 */

View::$titulo = $reparacion['folio'];

$estadoActual = $reparacion['estado'];
$avance = (float) $reparacion['porcentaje_avance'];
$llavesEstados = array_keys($estados);
$posicionActual = array_search($estadoActual, $llavesEstados, true);
?>
<a class="enlace-regreso" href="<?= e(View::url('/mis-pedidos')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Mis pedidos
</a>

<p class="folio-pagina"><?= e($reparacion['folio']) ?> · Reparación</p>
<h1 class="titulo-pagina"><?= e($reparacion['descripcion_mueble']) ?></h1>

<?php if ($estadoActual === 'LISTA'): ?>
    <!-- RF-35: aviso de que puede pasar a recogerla -->
    <div class="aviso-listo" role="status">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <span><?= e($mensajeListo) ?></span>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-6">

        <!-- ===== Gráfica de avance ===== -->
        <section class="tarjeta-carpin text-center mb-4">
            <div class="dona-avance">
                <canvas
                    id="graficaAvance"
                    data-avance="<?= e($avance) ?>"
                    aria-label="Avance de <?= e($avance) ?> por ciento"
                    role="img"
                ></canvas>
                <div class="dona-avance__centro">
                    <span class="dona-avance__porcentaje">
                        <?= e($reparacion['porcentaje_avance']) ?> %
                    </span>
                    <span class="dona-avance__texto">completado</span>
                </div>
            </div>

            <p class="mt-3 mb-1">
                Estado actual: <strong><?= e($estados[$estadoActual]['nombre']) ?></strong>
            </p>

            <?php if ($estadoActual === 'ENTREGADA' && $reparacion['entregada_en'] !== null): ?>
                <p class="texto-ayuda mb-0">
                    Entregada el <?= e(fecha($reparacion['entregada_en'])) ?>
                </p>
            <?php else: ?>
                <p class="texto-ayuda mb-0">
                    Entrega estimada: <?= e(fecha($reparacion['fecha_estimada'])) ?>
                </p>
            <?php endif; ?>
        </section>

        <!-- ===== Pago ===== -->
        <section class="tarjeta-carpin mb-4">
            <h2 class="titulo-seccion">Pago</h2>
            <dl class="resumen-pago">
                <dt>Costo</dt>
                <dd><strong><?= e(dinero($reparacion['costo'])) ?></strong></dd>
                <dt>Pagado</dt>
                <dd><?= e(dinero($reparacion['pagado'])) ?></dd>
                <dt class="resumen-pago__total">Saldo pendiente</dt>
                <dd class="resumen-pago__total"><strong><?= e(dinero($saldo)) ?></strong></dd>
            </dl>
        </section>

    </div>

    <div class="col-12 col-lg-6">

        <!-- ===== Estados (RN-05) ===== -->
        <section class="tarjeta-carpin mb-4">
            <h2 class="titulo-seccion">Etapas</h2>
            <ol class="etapas">
                <?php foreach ($llavesEstados as $posicion => $llave): ?>
                    <?php
                    if ($posicion < $posicionActual || $estadoActual === 'ENTREGADA') {
                        $claseEtapa = 'etapas__paso--hecho';
                    } elseif ($posicion === $posicionActual) {
                        $claseEtapa = 'etapas__paso--actual';
                    } else {
                        $claseEtapa = '';
                    }
                    ?>
                    <li
                        class="etapas__paso <?= e($claseEtapa) ?>"
                        <?= ($posicion === $posicionActual) ? 'aria-current="step"' : '' ?>
                    >
                        <?= e($estados[$llave]['nombre']) ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <!-- ===== Fotos ===== -->
        <?php if ($imagenes !== []): ?>
            <section class="tarjeta-carpin">
                <h2 class="titulo-seccion">Fotos</h2>
                <?php require __DIR__ . '/../layouts/_galeria_reparacion.php'; ?>
            </section>
        <?php endif; ?>

    </div>
</div>

<script src="<?= e(View::url('assets/vendor/chartjs/chart.umd.js')) ?>"></script>
<script src="<?= e(View::url('assets/js/avance.js')) ?>"></script>
