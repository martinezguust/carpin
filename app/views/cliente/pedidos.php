<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/cliente/pedidos.php
 * "Mis pedidos" del cliente (CU-06, RF-29): folio, fase
 * actual y fecha estimada de entrega de cada proyecto.
 * Las reparaciones se agregan en la Fase 2.
 * Variables: $cliente, $proyectos.
 * =====================================================
 */

View::$titulo = 'Mis pedidos';
?>
<h1 class="titulo-pagina">Mis pedidos</h1>

<?php if ($proyectos === []): ?>
    <p class="estado-vacio">
        <i class="bi bi-box-seam" aria-hidden="true"></i>
        Aún no tienes pedidos registrados.
    </p>
<?php else: ?>
    <div class="lista-pendientes">
        <?php foreach ($proyectos as $proyecto): ?>
            <?php $estaListo = (int) $proyecto['id_fase'] === Fase::LISTO_PARA_ENTREGA; ?>
            <a
                class="tarjeta-pendiente tarjeta-pendiente--enlace"
                href="<?= e(View::url('/mis-pedidos/proyecto/' . $proyecto['id_proyecto'])) ?>"
            >
                <span class="tarjeta-pendiente__encabezado">
                    <span class="tarjeta-pendiente__folio"><?= e($proyecto['folio']) ?></span>
                    <span class="etiqueta <?= $estaListo ? 'etiqueta--exito' : '' ?>">
                        <?= e($proyecto['fase_nombre']) ?>
                    </span>
                </span>

                <span class="tarjeta-pendiente__mueble"><?= e($proyecto['tipo_mueble']) ?></span>

                <span class="tarjeta-pendiente__detalle">
                    Entrega estimada: <?= e(fecha($proyecto['fecha_entrega'])) ?>
                </span>

                <span
                    class="barra-avance mt-2"
                    role="progressbar"
                    aria-label="Avance"
                    aria-valuenow="<?= e($proyecto['porcentaje_avance']) ?>"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <span
                        class="barra-avance__relleno"
                        style="width: <?= e((float) $proyecto['porcentaje_avance']) ?>%"
                    ></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
