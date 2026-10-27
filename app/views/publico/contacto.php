<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/publico/contacto.php
 * "Contacto" en su propia página (cambio pedido por el
 * Product Owner el 27/10/2026): dirección, horario,
 * teléfono, mapa, WhatsApp y el botón "Solicitar
 * cotización". Lo que no se ha capturado no se muestra.
 * Variables: $negocio, $enlaceWhatsapp (o null).
 * =====================================================
 */

View::$titulo = 'Contacto';
?>
<section class="seccion-publica">
    <div class="container">
        <div class="tarjeta-contacto">
            <h1 class="titulo-publico">Contacto</h1>

            <?php require __DIR__ . '/_datos_contacto.php'; ?>
            <?php require __DIR__ . '/_mapa.php'; ?>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <?php if ($enlaceWhatsapp !== null): ?>
                    <a
                        class="btn btn-carpin-whatsapp"
                        href="<?= e($enlaceWhatsapp) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <i class="bi bi-whatsapp" aria-hidden="true"></i>
                        Escríbenos por WhatsApp
                    </a>
                <?php endif; ?>

                <a class="btn btn-carpin-accion" href="<?= e(View::url('/cotizacion')) ?>">
                    Solicitar cotización
                </a>
            </div>
        </div>
    </div>
</section>
