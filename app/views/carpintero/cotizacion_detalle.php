<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/cotizacion_detalle.php
 * Detalle de una solicitud de cotización (RF-14) con las
 * acciones: marcar como contactado, convertir en proyecto
 * y descartar.
 * Variables: $solicitud, $estados, $abierta.
 * =====================================================
 */

View::$titulo = 'Solicitud de cotización';

$idSolicitud = (int) $solicitud['id_solicitud'];
$urlEstado = View::url('/cotizaciones/' . $idSolicitud . '/estado');
$urlConvertir = View::url('/proyectos/nuevo?cotizacion=' . $idSolicitud);
?>
<a class="enlace-regreso" href="<?= e(View::url('/cotizaciones')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Cotizaciones
</a>

<h1 class="titulo-pagina"><?= e($solicitud['nombre']) ?></h1>
<p class="subtitulo-pagina">
    Recibida el <?= e(fechaHora($solicitud['creado_en'])) ?> ·
    <strong><?= e($estados[$solicitud['estado']]) ?></strong>
</p>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <section class="tarjeta-carpin">
            <h2 class="titulo-seccion">Datos de la solicitud</h2>
            <dl class="lista-datos mb-0">
                <dt>Teléfono</dt>
                <dd>
                    <a href="tel:<?= e($solicitud['telefono']) ?>">
                        <?= e($solicitud['telefono']) ?>
                    </a>
                </dd>
                <dt>Correo</dt>
                <dd><?= e($solicitud['correo'] ?? '—') ?></dd>
                <dt>Producto del catálogo</dt>
                <dd><?= e($solicitud['producto_nombre'] ?? 'Ninguno en particular') ?></dd>
                <dt>Medidas</dt>
                <dd><?= e($solicitud['medidas'] ?? '—') ?></dd>
                <dt>Descripción</dt>
                <dd class="mb-0"><?= nl2br(e($solicitud['descripcion'])) ?></dd>
            </dl>
        </section>
    </div>

    <div class="col-12 col-lg-5">
        <section class="tarjeta-carpin">
            <h2 class="titulo-seccion">Acciones</h2>

            <?php if (!$abierta): ?>
                <p class="texto-ayuda mb-0">Esta solicitud ya está cerrada.</p>
            <?php else: ?>
                <div class="d-grid gap-2">
                    <?php if ($solicitud['estado'] === 'NUEVA'): ?>
                        <form method="post" action="<?= e($urlEstado) ?>">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="accion" value="contactado">
                            <button class="btn btn-carpin-secundario w-100" type="submit">
                                Marcar como contactado
                            </button>
                        </form>
                    <?php endif; ?>

                    <a class="btn btn-carpin-primario" href="<?= e($urlConvertir) ?>">
                        Convertir en proyecto
                    </a>

                    <form
                        method="post"
                        action="<?= e($urlEstado) ?>"
                        data-confirmar="¿Descartar esta solicitud? Ya no se podrá reabrir."
                    >
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="accion" value="descartar">
                        <button class="btn btn-carpin-peligro w-100" type="submit">
                            Descartar
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
