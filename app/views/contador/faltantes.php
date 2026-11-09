<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/contador/faltantes.php
 * Materiales faltantes / por reemplazar (RF-41): existencias
 * bajas y reportes de daño no resueltos con acción
 * "Reemplazar" (respuesta 7 de la Fase 3).
 * Variables: $bajos, $reemplazos.
 * =====================================================
 */

View::$titulo = 'Faltantes y por reemplazar';
?>
<a class="enlace-regreso" href="<?= e(View::url('/inventario')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Inventario
</a>

<h1 class="titulo-pagina">Faltantes y por reemplazar</h1>

<!-- ===== Existencias bajas ===== -->
<h2 class="titulo-seccion">Existencia en su mínimo o debajo</h2>

<?php if ($bajos === []): ?>
    <p class="estado-vacio">Ningún material está en su mínimo.</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas mb-5">
        <thead>
            <tr>
                <th scope="col">Material</th>
                <th scope="col">Existencia</th>
                <th scope="col">Mínimo</th>
                <th scope="col">Costo unitario</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bajos as $material): ?>
                <tr class="fila-alerta">
                    <td data-etiqueta="Material">
                        <a href="<?= e(View::url('/inventario/' . $material['id_material'])) ?>">
                            <strong><?= e($material['nombre']) ?></strong>
                        </a>
                    </td>
                    <td data-etiqueta="Existencia">
                        <?= e(Material::cantidadConUnidad(
                            $material['existencia'],
                            $material['unidad']
                        )) ?>
                    </td>
                    <td data-etiqueta="Mínimo">
                        <?= e(Material::cantidadConUnidad(
                            $material['minimo'],
                            $material['unidad']
                        )) ?>
                    </td>
                    <td data-etiqueta="Costo unitario">
                        <?= e(dinero($material['costo_unitario'])) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- ===== Por reemplazar (reportes de daño) ===== -->
<h2 class="titulo-seccion">Por reemplazar (reportes de daño)</h2>

<?php if ($reemplazos === []): ?>
    <p class="estado-vacio">No hay reportes pendientes con acción "Reemplazar".</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas">
        <thead>
            <tr>
                <th scope="col">Elemento</th>
                <th scope="col">Tipo</th>
                <th scope="col">Estado</th>
                <th scope="col">Reportó</th>
                <th scope="col">Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reemplazos as $reporte): ?>
                <tr>
                    <td data-etiqueta="Elemento">
                        <strong><?= e($reporte['elemento']) ?></strong>
                    </td>
                    <td data-etiqueta="Tipo"><?= e(ReporteDanio::TIPOS[$reporte['tipo']]) ?></td>
                    <td data-etiqueta="Estado">
                        <?= e(ReporteDanio::ESTADOS[$reporte['estado']]) ?>
                    </td>
                    <td data-etiqueta="Reportó"><?= e($reporte['reporta_nombre']) ?></td>
                    <td data-etiqueta="Fecha"><?= e(fecha($reporte['fecha'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
