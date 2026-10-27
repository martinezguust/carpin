<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/admin/panel.php
 * Panel del Administrador (SRS 10.3): totales de usuarios,
 * productos y proyectos activos.
 * Variables: $totalUsuarios, $totalProductos, $totalProyectos.
 * =====================================================
 */

View::$titulo = 'Panel';
?>
<h1 class="titulo-pagina">Panel</h1>

<div class="row g-3">
    <div class="col-12 col-md-4">
        <section class="indicador">
            <p class="indicador__titulo">Usuarios activos</p>
            <p class="indicador__valor"><?= e($totalUsuarios) ?></p>
        </section>
    </div>

    <div class="col-12 col-md-4">
        <section class="indicador">
            <p class="indicador__titulo">Productos del catálogo</p>
            <p class="indicador__valor"><?= e($totalProductos) ?></p>
        </section>
    </div>

    <div class="col-12 col-md-4">
        <section class="indicador">
            <p class="indicador__titulo">Proyectos activos</p>
            <p class="indicador__valor"><?= e($totalProyectos) ?></p>
        </section>
    </div>
</div>
