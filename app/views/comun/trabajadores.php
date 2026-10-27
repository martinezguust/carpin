<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/trabajadores.php
 * "Trabajadores y formas de pago" (HU-30, RF-43): lista de
 * trabajadores con su forma de pago; quien todavía no
 * tiene tarifa aparece como "Sin configurar".
 * Variable: $trabajadores.
 * =====================================================
 */

View::$titulo = 'Trabajadores y formas de pago';
?>
<h1 class="titulo-pagina">Trabajadores y formas de pago</h1>

<p class="subtitulo-pagina">
    El Administrador elige el tipo de pago al dar de alta; aquí se captura la tarifa por hora
    o el salario diario. Quien cobra por proyecto participa en el reparto de mano de obra.
</p>

<?php if ($trabajadores === []): ?>
    <p class="estado-vacio">No hay trabajadores registrados.</p>
<?php else: ?>
    <?php require __DIR__ . '/_tabla_trabajadores.php'; ?>
<?php endif; ?>
