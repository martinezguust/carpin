<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/layouts/_lista_pagos.php
 * Lista de pagos de un proyecto o de una reparación
 * (RF-31, RF-48): fecha, método, monto y referencia. La
 * usan "Pagos de clientes", los detalles del Carpintero y
 * el portal del cliente.
 * Variables: $pagos; $mostrarRegistro, true para mostrar
 * quién lo registró y la referencia (personal del taller).
 * =====================================================
 */
?>
<?php if ($pagos === []): ?>
    <p class="texto-ayuda mb-0">Todavía no hay pagos registrados.</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas mb-0">
        <thead>
            <tr>
                <th scope="col">Fecha</th>
                <th scope="col">Método</th>
                <th scope="col">Monto</th>
                <?php if ($mostrarRegistro): ?>
                    <th scope="col">Referencia</th>
                    <th scope="col">Registró</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagos as $pago): ?>
                <tr>
                    <td data-etiqueta="Fecha"><?= e(fecha($pago['fecha'])) ?></td>
                    <td data-etiqueta="Método"><?= e(PagoCliente::METODOS[$pago['metodo']]) ?></td>
                    <td data-etiqueta="Monto"><strong><?= e(dinero($pago['monto'])) ?></strong></td>
                    <?php if ($mostrarRegistro): ?>
                        <td data-etiqueta="Referencia"><?= e($pago['referencia'] ?? '—') ?></td>
                        <td data-etiqueta="Registró"><?= e($pago['registro_nombre']) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
