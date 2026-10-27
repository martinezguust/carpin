<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/layouts/_mensajes.php
 * Muestra los mensajes de una sola vez (Flash): éxito,
 * error o aviso, con la clase de alerta de Bootstrap.
 * =====================================================
 */

$clasesAlerta = [
    'exito' => 'alert-success',
    'error' => 'alert-danger',
    'aviso' => 'alert-warning',
];

$iconosAlerta = [
    'exito' => 'bi-check-circle-fill',
    'error' => 'bi-exclamation-octagon-fill',
    'aviso' => 'bi-exclamation-triangle-fill',
];
?>
<?php foreach (Flash::obtener() as $mensajeFlash): ?>
    <div class="alert <?= e($clasesAlerta[$mensajeFlash['tipo']]) ?> d-flex gap-2" role="alert">
        <i class="bi <?= e($iconosAlerta[$mensajeFlash['tipo']]) ?>" aria-hidden="true"></i>
        <div><?= e($mensajeFlash['mensaje']) ?></div>
    </div>
<?php endforeach; ?>
