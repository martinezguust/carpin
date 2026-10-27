<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/_tarjeta_proyecto.php
 * Tarjeta de un proyecto en el tablero (mockup 3): folio,
 * mueble, cliente, fecha de entrega y barra de avance.
 * Variable: $proyecto, datos del proyecto.
 * =====================================================
 */

$avanceTarjeta = (float) $proyecto['porcentaje_avance'];
?>
<a
    class="tarjeta-proyecto"
    href="<?= e(View::url('/proyectos/' . $proyecto['id_proyecto'])) ?>"
>
    <span class="tarjeta-proyecto__folio"><?= e($proyecto['folio']) ?></span>
    <span class="tarjeta-proyecto__mueble"><?= e($proyecto['tipo_mueble']) ?></span>
    <span class="tarjeta-proyecto__detalle">
        <?= e($proyecto['cliente_nombre']) ?> · entrega <?= e(fecha($proyecto['fecha_entrega'])) ?>
    </span>
    <span
        class="barra-avance"
        role="progressbar"
        aria-label="Avance"
        aria-valuenow="<?= e($avanceTarjeta) ?>"
        aria-valuemin="0"
        aria-valuemax="100"
    >
        <span class="barra-avance__relleno" style="width: <?= e($avanceTarjeta) ?>%"></span>
    </span>
</a>
