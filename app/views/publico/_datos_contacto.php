<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/publico/_datos_contacto.php
 * Dirección, horario y teléfono de la carpintería (RF-08).
 * Lo que el Administrador no ha capturado no se muestra.
 * La usan Inicio ("Visítanos") y Contacto.
 * Variable: $negocio, datos de la carpintería.
 * =====================================================
 */

$direccion = (string) ($negocio['direccion'] ?? '');
$horario = (string) ($negocio['horario'] ?? '');
$telefono = (string) ($negocio['telefono'] ?? '');
?>
<dl class="datos-contacto">
    <?php if ($direccion !== ''): ?>
        <dt>Dirección:</dt>
        <dd><?= e($direccion) ?></dd>
    <?php endif; ?>

    <?php if ($horario !== ''): ?>
        <dt>Horario:</dt>
        <dd><?= e($horario) ?></dd>
    <?php endif; ?>

    <?php if ($telefono !== ''): ?>
        <dt>Teléfono:</dt>
        <dd><a href="tel:<?= e($telefono) ?>"><?= e($telefono) ?></a></dd>
    <?php endif; ?>
</dl>
