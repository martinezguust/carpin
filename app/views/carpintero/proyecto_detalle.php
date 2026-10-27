<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/proyecto_detalle.php
 * Detalle del proyecto para el Carpintero (SRS 10.3):
 * fase actual con avance normal y corrección (HU-17), y las
 * pestañas Datos, Actividades (HU-25) e Historial (RF-22).
 * Las pestañas Materiales y Pagos se agregan en la Fase 3.
 * Variables: $proyecto, $saldo, $fases, $siguienteFase,
 * $actividades, $historial, $trabajadoresPorTipo, $rolPorTipo.
 * =====================================================
 */

View::$titulo = $proyecto['folio'];

$idProyecto = (int) $proyecto['id_proyecto'];
$idFaseActual = (int) $proyecto['id_fase'];
$estaEntregado = $idFaseActual === Fase::ENTREGADO;
$avance = (float) $proyecto['porcentaje_avance'];
?>
<a class="enlace-regreso" href="<?= e(View::url('/proyectos')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Proyectos
</a>

<!-- ===== Encabezado ===== -->
<p class="folio-pagina"><?= e($proyecto['folio']) ?></p>
<h1 class="titulo-pagina"><?= e($proyecto['tipo_mueble']) ?></h1>
<p class="subtitulo-pagina">
    <?= e($proyecto['cliente_nombre']) ?> · entrega <?= e(fecha($proyecto['fecha_entrega'])) ?>
</p>

<!-- ===== Fase actual y cambios de fase ===== -->
<?php require __DIR__ . '/_fase_proyecto.php'; ?>

<!-- ===== Pestañas ===== -->
<ul class="nav nav-tabs pestanas-carpin" role="tablist">
    <li class="nav-item" role="presentation">
        <button
            class="nav-link active"
            id="pestanaDatos"
            type="button"
            role="tab"
            data-bs-toggle="tab"
            data-bs-target="#datos"
            aria-controls="datos"
            aria-selected="true"
        >Datos</button>
    </li>
    <li class="nav-item" role="presentation">
        <button
            class="nav-link"
            id="pestanaActividades"
            type="button"
            role="tab"
            data-bs-toggle="tab"
            data-bs-target="#actividades"
            aria-controls="actividades"
            aria-selected="false"
        >Actividades</button>
    </li>
    <li class="nav-item" role="presentation">
        <button
            class="nav-link"
            id="pestanaHistorial"
            type="button"
            role="tab"
            data-bs-toggle="tab"
            data-bs-target="#historial"
            aria-controls="historial"
            aria-selected="false"
        >Historial</button>
    </li>
</ul>

<div class="tab-content tarjeta-carpin tarjeta-carpin--pestanas">
    <div
        class="tab-pane fade show active"
        id="datos"
        role="tabpanel"
        aria-labelledby="pestanaDatos"
    >
        <?php require __DIR__ . '/_datos_proyecto.php'; ?>
    </div>

    <div
        class="tab-pane fade"
        id="actividades"
        role="tabpanel"
        aria-labelledby="pestanaActividades"
    >
        <?php require __DIR__ . '/_actividades_proyecto.php'; ?>
    </div>

    <div
        class="tab-pane fade"
        id="historial"
        role="tabpanel"
        aria-labelledby="pestanaHistorial"
    >
        <?php require __DIR__ . '/_historial_proyecto.php'; ?>
    </div>
</div>
