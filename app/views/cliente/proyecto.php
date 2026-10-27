<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/cliente/proyecto.php
 * Avance del pedido para el cliente (CU-06, RF-30, RF-31,
 * mockup 8): gráfica de dona con el porcentaje, fase
 * actual, línea de etapas, aviso de "listo para recoger"
 * y resumen de pago. El botón del comprobante PDF se
 * agrega en la Fase 4.
 * Variables: $proyecto, $fases, $saldo, $mensajeListo.
 * =====================================================
 */

View::$titulo = $proyecto['folio'];

$idFaseActual = (int) $proyecto['id_fase'];
$avance = (float) $proyecto['porcentaje_avance'];
$titulo = 'Mi ' . mb_strtolower(mb_substr($proyecto['tipo_mueble'], 0, 1))
    . mb_substr($proyecto['tipo_mueble'], 1);
?>
<a class="enlace-regreso" href="<?= e(View::url('/mis-pedidos')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Mis pedidos
</a>

<p class="folio-pagina"><?= e($proyecto['folio']) ?></p>
<h1 class="titulo-pagina"><?= e($titulo) ?></h1>

<?php if ($idFaseActual === Fase::LISTO_PARA_ENTREGA): ?>
    <!-- CU-06, A1: aviso destacado de mueble listo (RN-04) -->
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
                        <?= e($proyecto['porcentaje_avance']) ?> %
                    </span>
                    <span class="dona-avance__texto">completado</span>
                </div>
            </div>

            <p class="mt-3 mb-1">
                Fase actual: <strong><?= e($proyecto['fase_nombre']) ?></strong>
            </p>

            <?php if ($idFaseActual === Fase::ENTREGADO && $proyecto['entregado_en'] !== null): ?>
                <p class="texto-ayuda mb-0">
                    Entregado el <?= e(fecha($proyecto['entregado_en'])) ?>
                </p>
            <?php else: ?>
                <p class="texto-ayuda mb-0">
                    Entrega estimada: <?= e(fecha($proyecto['fecha_entrega'])) ?>
                </p>
            <?php endif; ?>
        </section>

        <!-- ===== Pago (respuesta 16) ===== -->
        <section class="tarjeta-carpin mb-4">
            <h2 class="titulo-seccion">Pago</h2>
            <dl class="resumen-pago">
                <dt>Precio</dt>
                <dd><strong><?= e(dinero($proyecto['precio'])) ?></strong></dd>
                <dt>Pagado</dt>
                <dd><?= e(dinero($proyecto['pagado'])) ?></dd>
                <dt class="resumen-pago__total">Saldo pendiente</dt>
                <dd class="resumen-pago__total"><strong><?= e(dinero($saldo)) ?></strong></dd>
            </dl>
        </section>

    </div>

    <div class="col-12 col-lg-6">

        <!-- ===== Línea de etapas ===== -->
        <section class="tarjeta-carpin">
            <h2 class="titulo-seccion">Etapas</h2>
            <ol class="etapas">
                <?php foreach ($fases as $idFase => $fase): ?>
                    <?php
                    if ($idFase < $idFaseActual || $idFaseActual === Fase::ENTREGADO) {
                        $claseEtapa = 'etapas__paso--hecho';
                    } elseif ($idFase === $idFaseActual) {
                        $claseEtapa = 'etapas__paso--actual';
                    } else {
                        $claseEtapa = '';
                    }
                    ?>
                    <li
                        class="etapas__paso <?= e($claseEtapa) ?>"
                        <?= ($idFase === $idFaseActual) ? 'aria-current="step"' : '' ?>
                    >
                        <?= e($fase['nombre']) ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>

    </div>
</div>

<script src="<?= e(View::url('assets/vendor/chartjs/chart.umd.js')) ?>"></script>
<script src="<?= e(View::url('assets/js/avance.js')) ?>"></script>
