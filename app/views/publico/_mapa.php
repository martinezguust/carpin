<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/publico/_mapa.php
 * Mapa de Google con la ubicación de la carpintería (RF-08).
 * Solo se muestra si el Administrador capturó la URL.
 * La usan Inicio ("Visítanos") y Contacto.
 * Variable: $negocio, datos de la carpintería.
 * =====================================================
 */

$urlMapa = (string) ($negocio['mapa_url'] ?? '');
?>
<?php if ($urlMapa !== ''): ?>
    <iframe
        class="mapa-contacto"
        src="<?= e($urlMapa) ?>"
        title="Mapa de la ubicación de la carpintería"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        allowfullscreen
    ></iframe>
<?php endif; ?>
