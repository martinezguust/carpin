<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/cotizaciones.php
 * Bandeja de solicitudes de cotización (RF-14) con filtro
 * por estado.
 * Variables: $solicitudes, $estado, $estados.
 * =====================================================
 */

View::$titulo = 'Cotizaciones';

// Clase de la etiqueta de cada estado
$clasesEstado = [
    'NUEVA'      => 'etiqueta--acento',
    'CONTACTADO' => 'etiqueta--advertencia',
    'CONVERTIDA' => 'etiqueta--exito',
    'DESCARTADA' => '',
];
?>
<h1 class="titulo-pagina">Cotizaciones</h1>

<form class="filtros" method="get" action="<?= e(View::url('/cotizaciones')) ?>">
    <div class="filtros__campo">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select" id="estado" name="estado">
            <option value="">Todos</option>
            <?php foreach ($estados as $clave => $nombre): ?>
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

<?php if ($solicitudes === []): ?>
    <p class="estado-vacio">No hay solicitudes de cotización.</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas">
        <thead>
            <tr>
                <th scope="col">Fecha</th>
                <th scope="col">Nombre</th>
                <th scope="col">Teléfono</th>
                <th scope="col">Producto</th>
                <th scope="col">Estado</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($solicitudes as $solicitud): ?>
                <tr>
                    <td data-etiqueta="Fecha"><?= e(fechaHora($solicitud['creado_en'])) ?></td>
                    <td data-etiqueta="Nombre"><strong><?= e($solicitud['nombre']) ?></strong></td>
                    <td data-etiqueta="Teléfono"><?= e($solicitud['telefono']) ?></td>
                    <td data-etiqueta="Producto">
                        <?= e($solicitud['producto_nombre'] ?? '—') ?>
                    </td>
                    <td data-etiqueta="Estado">
                        <span class="etiqueta <?= e($clasesEstado[$solicitud['estado']]) ?>">
                            <?= e($estados[$solicitud['estado']]) ?>
                        </span>
                    </td>
                    <td data-etiqueta="Acciones">
                        <?php
                        $urlSolicitud = View::url('/cotizaciones/' . $solicitud['id_solicitud']);
                        ?>
                        <a href="<?= e($urlSolicitud) ?>">
                            Ver
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
