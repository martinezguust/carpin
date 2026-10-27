<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/contador/panel.php
 * Panel del Contador. Fase 1: bienvenida sin indicadores
 * (respuesta 2). Los módulos de asistencia, nómina, pagos,
 * proveedores y reportes se agregan en las Fases 2 a 4.
 * =====================================================
 */

View::$titulo = 'Panel';

$usuarioPanel = Auth::usuario();
?>
<h1 class="titulo-pagina">Panel</h1>

<section class="tarjeta-carpin">
    <h2 class="titulo-seccion">Hola, <?= e($usuarioPanel['nombre']) ?></h2>
    <p class="mb-0">
        Revisa tus avisos en la campana de notificaciones. Aquí aparecerán tus
        módulos conforme se vayan habilitando.
    </p>
</section>
