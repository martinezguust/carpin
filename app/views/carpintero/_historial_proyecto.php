<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/_historial_proyecto.php
 * Pestaña "Historial" del detalle del proyecto (RF-22):
 * fase anterior, fase nueva, quién hizo el cambio, fecha y
 * motivo de cada cambio de fase.
 * Variable (de proyecto_detalle.php): $historial.
 * =====================================================
 */
?>
<table class="table tabla-carpin tabla-tarjetas mb-0">
    <thead>
        <tr>
            <th scope="col">Fecha</th>
            <th scope="col">Fase anterior</th>
            <th scope="col">Fase nueva</th>
            <th scope="col">Quién</th>
            <th scope="col">Motivo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($historial as $cambio): ?>
            <tr>
                <td data-etiqueta="Fecha"><?= e(fechaHora($cambio['fecha'])) ?></td>
                <td data-etiqueta="Fase anterior">
                    <?= e($cambio['fase_anterior'] ?? 'Registro') ?>
                </td>
                <td data-etiqueta="Fase nueva"><?= e($cambio['fase_nueva']) ?></td>
                <td data-etiqueta="Quién"><?= e($cambio['usuario_nombre']) ?></td>
                <td data-etiqueta="Motivo"><?= e($cambio['motivo'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
