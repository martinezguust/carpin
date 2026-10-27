<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/layouts/_avance_tarjeta.php
 * Parte final de una tarjeta de "Mis pendientes": botón
 * "Marcar como terminada → {siguiente fase}" cuando le toca
 * al trabajador (RF-20, RN-02), o el mensaje de espera
 * (respuesta 3 de la Fase 2).
 * Variables: $tarjeta, datos de la tarjeta; $actividad,
 * actividad con su proyecto.
 * =====================================================
 */

$urlAvanzar = View::url('/proyectos/' . $actividad['id_proyecto'] . '/avanzar');
$idConfirmar = 'confirmarSecado' . $actividad['id_actividad'];
$faltaSecado = $tarjeta['faltaSecado'] ?? false;
$secadoSinTerminar = $tarjeta['secadoSinTerminar'] ?? false;
?>
<?php if ($tarjeta['siguiente'] !== null): ?>
    <form class="mt-3" method="post" action="<?= e($urlAvanzar) ?>">
        <?= Csrf::campo() ?>

        <?php if ($faltaSecado): ?>
            <!-- CU-05, A2: antes de avanzar a "Secado" se programa el secado -->
            <p class="texto-ayuda mb-2">Programa el tiempo de secado para poder avanzar.</p>
        <?php endif; ?>

        <?php if ($secadoSinTerminar): ?>
            <!-- Respuesta 1: el secado aún no termina, se pide confirmar -->
            <div class="form-check mb-2">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="<?= e($idConfirmar) ?>"
                    name="confirmar_secado"
                    value="1"
                    required
                >
                <label class="form-check-label" for="<?= e($idConfirmar) ?>">
                    El secado aún no termina; confirmo que el mueble ya está listo.
                </label>
            </div>
        <?php endif; ?>

        <button
            class="btn btn-carpin-accion boton-avance"
            type="submit"
            <?= $faltaSecado ? 'disabled' : '' ?>
        >
            Marcar como terminada → <?= e($tarjeta['siguiente']['nombre']) ?>
        </button>
    </form>
<?php else: ?>
    <p class="tarjeta-pendiente__espera"><?= e($tarjeta['espera']) ?></p>
<?php endif; ?>
