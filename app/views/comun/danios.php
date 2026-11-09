<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/danios.php
 * Reportes de daño para el Carpintero y el Contador (RF-40):
 * filtro por estado y cambio de estado Reportado → En
 * atención → Resuelto.
 * Variables: $reportes, $estado.
 * =====================================================
 */

View::$titulo = 'Reportes de daño';

$puedeCambiarEstado = true;
?>
<div class="encabezado-pagina">
    <h1 class="titulo-pagina mb-0">Reportes de daño</h1>

    <?php if (Auth::rol() === Auth::CARPINTERO): ?>
        <a class="btn btn-carpin-primario" href="<?= e(View::url('/danios/reportar')) ?>">
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            Reportar daño
        </a>
    <?php endif; ?>
</div>

<form class="filtros" method="get" action="<?= e(View::url('/danios')) ?>">
    <div class="filtros__campo">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select" id="estado" name="estado">
            <option value="">Todos</option>
            <?php foreach (ReporteDanio::ESTADOS as $clave => $nombre): ?>
                <option value="<?= e($clave) ?>" <?= seleccionado($estado === $clave) ?>>
                    <?= e($nombre) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filtros__boton">
        <button class="btn btn-carpin-secundario" type="submit">
            <i class="bi bi-funnel" aria-hidden="true"></i>
            Filtrar
        </button>
    </div>
</form>

<?php if ($reportes === []): ?>
    <p class="estado-vacio">No hay reportes de daño.</p>
<?php else: ?>
    <?php require __DIR__ . '/_lista_danios.php'; ?>
<?php endif; ?>
