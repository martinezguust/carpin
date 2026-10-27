<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/notificaciones.php
 * Lista completa de notificaciones del usuario (RF-60).
 * Variable: $notificaciones, lista de la más reciente a la
 * más antigua.
 * =====================================================
 */

View::$titulo = 'Notificaciones';
?>
<h1 class="titulo-pagina">Notificaciones</h1>

<?php if ($notificaciones === []): ?>
    <p class="estado-vacio">
        <i class="bi bi-bell-slash" aria-hidden="true"></i>
        No tienes notificaciones.
    </p>
<?php else: ?>
    <ul class="lista-notificaciones">
        <?php foreach ($notificaciones as $notificacion): ?>
            <?php
            $esNueva = (int) $notificacion['leida'] === 0;
            $claseNueva = $esNueva ? 'lista-notificaciones__elemento--nueva' : '';
            $urlLeer = View::url('/notificaciones/' . $notificacion['id_notificacion'] . '/leer');
            ?>
            <li class="lista-notificaciones__elemento <?= e($claseNueva) ?>">
                <div class="lista-notificaciones__texto">
                    <p class="mb-1"><?= e($notificacion['mensaje']) ?></p>
                    <p class="texto-ayuda mb-0">
                        <?= e(fechaHora($notificacion['creado_en'])) ?>
                        <?php if ($esNueva): ?>
                            · <span class="etiqueta etiqueta--acento">Sin leer</span>
                        <?php endif; ?>
                    </p>
                </div>

                <form method="post" action="<?= e($urlLeer) ?>">
                    <?= Csrf::campo() ?>
                    <button class="btn btn-carpin-secundario btn-sm" type="submit">
                        <?= ($notificacion['enlace'] !== null) ? 'Abrir' : 'Marcar como leída' ?>
                    </button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
