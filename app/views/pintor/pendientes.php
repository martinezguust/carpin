<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/pintor/pendientes.php
 * "Mis pendientes" del Pintor (RF-19, mockup 7): tarjetas
 * con folio, mueble, fase actual, madera y fechas. La ficha
 * de color, las capas, el secado y el botón "Marcar como
 * terminada" se agregan en la Fase 2.
 * Variables: $trabajador, $pendientes.
 * =====================================================
 */

View::$titulo = 'Mis pendientes';

$cantidad = count($pendientes);
?>
<h1 class="titulo-pagina mb-1">Mis pendientes</h1>
<p class="subtitulo-pagina">
    <?= e($trabajador['nombre_completo']) ?> ·
    <?= e($cantidad) ?> <?= ($cantidad === 1) ? 'actividad pendiente' : 'actividades pendientes' ?>
</p>

<?php if ($pendientes === []): ?>
    <p class="estado-vacio">
        <i class="bi bi-check2-circle" aria-hidden="true"></i>
        No tienes actividades pendientes.
    </p>
<?php else: ?>
    <div class="lista-pendientes">
        <?php foreach ($pendientes as $pendiente): ?>
            <article class="tarjeta-pendiente">
                <div class="tarjeta-pendiente__encabezado">
                    <span class="tarjeta-pendiente__folio"><?= e($pendiente['folio']) ?></span>
                    <span class="etiqueta"><?= e($pendiente['fase_nombre']) ?></span>
                </div>

                <h2 class="tarjeta-pendiente__mueble"><?= e($pendiente['tipo_mueble']) ?></h2>

                <p class="tarjeta-pendiente__detalle">
                    <?= e(etiquetaActividad($pendiente['tipo'])) ?> ·
                    Madera: <?= e($pendiente['madera']) ?> ·
                    Entrega: <?= e(fecha($pendiente['fecha_entrega'])) ?>
                </p>
                <p class="tarjeta-pendiente__detalle mb-0">
                    Fecha compromiso: <?= e(fecha($pendiente['fecha_compromiso'])) ?>
                </p>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
