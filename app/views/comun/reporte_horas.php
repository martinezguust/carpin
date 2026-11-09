<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/reporte_horas.php
 * Reporte de total de horas por trabajador en un periodo
 * (RF-47), para el Contador y el Carpintero. El PDF se
 * agrega en la Fase 4.
 * Variables: $filas, $desde, $hasta, $error.
 * =====================================================
 */

View::$titulo = 'Horas por trabajador';

$totalHoras = 0;
$totalDias = 0;

foreach ($filas as $fila) {
    $totalHoras += (int) round((float) $fila['horas'] * 100);
    $totalDias += (int) $fila['dias'];
}
?>
<h1 class="titulo-pagina">Reportes: horas por trabajador</h1>

<form class="filtros" method="get" action="<?= e(View::url('/reportes/horas')) ?>">
    <div class="filtros__campo">
        <label class="form-label" for="desde">Desde</label>
        <input class="form-control" type="date" id="desde" name="desde" value="<?= e($desde) ?>">
    </div>

    <div class="filtros__campo">
        <label class="form-label" for="hasta">Hasta</label>
        <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($hasta) ?>">
    </div>

    <div class="filtros__boton">
        <button class="btn btn-carpin-secundario" type="submit">
            <i class="bi bi-funnel" aria-hidden="true"></i>
            Consultar
        </button>
    </div>
</form>

<?php if ($error !== null): ?>
    <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
<?php elseif ($filas === []): ?>
    <p class="estado-vacio">No hay trabajadores para mostrar.</p>
<?php else: ?>
    <p class="subtitulo-pagina">
        Del <?= e(fecha($desde)) ?> al <?= e(fecha($hasta)) ?>.
    </p>

    <table class="table tabla-carpin tabla-tarjetas">
        <thead>
            <tr>
                <th scope="col">Trabajador</th>
                <th scope="col">Rol</th>
                <th scope="col">Forma de pago</th>
                <th scope="col">Días con asistencia</th>
                <th scope="col">Total de horas</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filas as $fila): ?>
                <tr>
                    <td data-etiqueta="Trabajador">
                        <strong><?= e($fila['nombre_completo']) ?></strong>
                    </td>
                    <td data-etiqueta="Rol"><?= e(etiquetaRol($fila['rol'])) ?></td>
                    <td data-etiqueta="Forma de pago">
                        <?= e(etiquetaFormaPago($fila['forma_pago'])) ?>
                    </td>
                    <td data-etiqueta="Días con asistencia"><?= e($fila['dias']) ?></td>
                    <td data-etiqueta="Total de horas">
                        <?= e(number_format((float) $fila['horas'], 2)) ?> h
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="fila-total">
                <td data-etiqueta="Total" colspan="3"><strong>Total</strong></td>
                <td data-etiqueta="Días"><strong><?= e($totalDias) ?></strong></td>
                <td data-etiqueta="Horas">
                    <strong><?= e(number_format($totalHoras / 100, 2)) ?> h</strong>
                </td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>
