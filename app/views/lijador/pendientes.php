<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/lijador/pendientes.php
 * "Mis pendientes" del Lijador (HU-01, HU-02, HU-05,
 * mockup 6): tarjetas con folio, fase actual, mueble,
 * granulado de lija, madera, fechas y el botón "Marcar como
 * terminada" cuando le toca.
 * Variables: $trabajador, $tarjetas.
 * =====================================================
 */

View::$titulo = 'Mis pendientes';

$cantidad = count($tarjetas);
?>
<h1 class="titulo-pagina mb-1">Mis pendientes</h1>
<p class="subtitulo-pagina">
    <?= e($trabajador['nombre_completo']) ?> ·
    <?= e($cantidad) ?> <?= ($cantidad === 1) ? 'actividad de lijado' : 'actividades de lijado' ?>
</p>

<?php if ($tarjetas === []): ?>
    <p class="estado-vacio">
        <i class="bi bi-check2-circle" aria-hidden="true"></i>
        No tienes actividades pendientes.
    </p>
<?php else: ?>
    <div class="lista-pendientes">
        <?php foreach ($tarjetas as $tarjeta): ?>
            <?php
            $actividad = $tarjeta['actividad'];
            $teToca = $tarjeta['siguiente'] !== null;
            ?>
            <article class="tarjeta-pendiente <?= $teToca ? 'tarjeta-pendiente--te-toca' : '' ?>">
                <div class="tarjeta-pendiente__encabezado">
                    <span class="tarjeta-pendiente__folio"><?= e($actividad['folio']) ?></span>
                    <?php if ($teToca): ?>
                        <span class="etiqueta etiqueta--acento">Te toca</span>
                    <?php else: ?>
                        <span class="etiqueta"><?= e($actividad['fase_nombre']) ?></span>
                    <?php endif; ?>
                </div>

                <h2 class="tarjeta-pendiente__mueble"><?= e($actividad['tipo_mueble']) ?></h2>

                <!-- Granulado de lija (RF-24) -->
                <div class="dato-destacado">
                    <p class="dato-destacado__titulo">Granulado de lija</p>
                    <p class="dato-destacado__valor">
                        <?= e($actividad['granulado_lija'] ?? 'Sin indicar') ?>
                    </p>
                </div>

                <p class="tarjeta-pendiente__detalle">
                    Madera: <?= e($actividad['madera']) ?> ·
                    Entrega: <?= e(fecha($actividad['fecha_entrega'])) ?>
                </p>
                <p class="tarjeta-pendiente__detalle mb-0">
                    Fase actual: <?= e($actividad['fase_nombre']) ?> ·
                    Compromiso: <?= e(fecha($actividad['fecha_compromiso'])) ?>
                </p>

                <?php require __DIR__ . '/../layouts/_avance_tarjeta.php'; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
