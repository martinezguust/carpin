<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/tablero.php
 * Panel del Carpintero (mockup 3): indicadores y tablero de
 * proyectos con las 9 fases previas a "Entregado". En
 * celular las columnas se desplazan de forma horizontal.
 * Variables: $fases, $columnas, $totalActivos, $entregasSemana.
 * =====================================================
 */

View::$titulo = 'Tablero del taller';
?>
<div class="encabezado-pagina">
    <h1 class="titulo-pagina mb-0">Tablero del taller</h1>
    <a class="btn btn-carpin-primario" href="<?= e(View::url('/proyectos/nuevo')) ?>">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
        Nuevo proyecto
    </a>
</div>

<!-- ===== Indicadores ===== -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <section class="indicador">
            <p class="indicador__titulo">Proyectos activos</p>
            <p class="indicador__valor"><?= e($totalActivos) ?></p>
        </section>
    </div>

    <div class="col-12 col-md-6">
        <section class="indicador">
            <p class="indicador__titulo">Entregas esta semana</p>
            <p class="indicador__valor"><?= e($entregasSemana) ?></p>
        </section>
    </div>
</div>

<!-- ===== Tablero por fase ===== -->
<div class="tablero" aria-label="Proyectos por fase">
    <?php foreach ($fases as $idFase => $fase): ?>
        <section class="tablero__columna">
            <h2 class="tablero__titulo">
                <span><?= e($fase['nombre']) ?></span>
                <span class="tablero__cantidad"><?= e(count($columnas[$idFase])) ?></span>
            </h2>

            <?php foreach ($columnas[$idFase] as $proyecto): ?>
                <?php require __DIR__ . '/_tarjeta_proyecto.php'; ?>
            <?php endforeach; ?>

            <?php if ($columnas[$idFase] === []): ?>
                <p class="tablero__vacia">Sin proyectos</p>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
