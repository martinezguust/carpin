<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/_materiales_proyecto.php
 * Pestaña "Materiales" del detalle del proyecto (respuesta 8
 * de la Fase 3): salidas de inventario del proyecto y botón
 * "Registrar salida", que lleva al inventario con el
 * proyecto ya elegido.
 * Variables (de proyecto_detalle.php): $salidas, $idProyecto,
 * $estaEntregado.
 * =====================================================
 */

$totalMateriales = 0;

foreach ($salidas as $salida) {
    $totalMateriales += PagoService::aCentavos($salida['cantidad'] * $salida['costo_unitario']);
}
?>
<?php if ($salidas === []): ?>
    <p class="estado-vacio">Todavía no se ha dado salida de material a este proyecto.</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas">
        <thead>
            <tr>
                <th scope="col">Fecha</th>
                <th scope="col">Material</th>
                <th scope="col">Cantidad</th>
                <th scope="col">Costo unitario</th>
                <th scope="col">Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($salidas as $salida): ?>
                <?php $importe = (float) $salida['cantidad'] * (float) $salida['costo_unitario']; ?>
                <tr>
                    <td data-etiqueta="Fecha"><?= e(fecha($salida['fecha'])) ?></td>
                    <td data-etiqueta="Material"><?= e($salida['material_nombre']) ?></td>
                    <td data-etiqueta="Cantidad">
                        <?= e(Material::cantidadConUnidad(
                            $salida['cantidad'],
                            $salida['unidad']
                        )) ?>
                    </td>
                    <td data-etiqueta="Costo unitario">
                        <?= e(dinero($salida['costo_unitario'])) ?>
                    </td>
                    <td data-etiqueta="Importe"><?= e(dinero($importe)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="fila-total">
                <td data-etiqueta="Total" colspan="4"><strong>Total de materiales</strong></td>
                <td data-etiqueta="Importe">
                    <strong><?= e(dinero($totalMateriales / 100)) ?></strong>
                </td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<?php if (!$estaEntregado): ?>
    <a
        class="btn btn-carpin-primario mt-3"
        href="<?= e(View::url('/inventario?proyecto=' . $idProyecto)) ?>"
    >
        <i class="bi bi-box-arrow-up" aria-hidden="true"></i>
        Registrar salida
    </a>
    <p class="texto-ayuda mt-2 mb-0">
        Elige el material en el inventario; la salida aparecerá con este proyecto ya elegido.
    </p>
<?php endif; ?>
