<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/cliente/pedidos.php
 * "Mis pedidos" del cliente (CU-06, RF-29): sus proyectos y
 * reparaciones con folio, fase o estado y fecha estimada.
 * Variables: $cliente, $proyectos, $reparaciones, $estados.
 * =====================================================
 */

View::$titulo = 'Mis pedidos';
?>
<h1 class="titulo-pagina">Mis pedidos</h1>

<?php if ($proyectos === [] && $reparaciones === []): ?>
    <p class="estado-vacio">
        <i class="bi bi-box-seam" aria-hidden="true"></i>
        Aún no tienes pedidos registrados.
    </p>
<?php else: ?>
    <div class="lista-pendientes">

        <!-- ===== Proyectos (muebles nuevos) ===== -->
        <?php foreach ($proyectos as $proyecto): ?>
            <?php
            $estaListo = (int) $proyecto['id_fase'] === Fase::LISTO_PARA_ENTREGA;
            $avanceProyecto = (float) $proyecto['porcentaje_avance'];
            ?>
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
                    aria-valuenow="<?= e($avanceProyecto) ?>"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <span
                        class="barra-avance__relleno"
                        style="width: <?= e($avanceProyecto) ?>%"
                    ></span>
                </span>
            </a>
        <?php endforeach; ?>

        <!-- ===== Reparaciones (RF-36) ===== -->
        <?php foreach ($reparaciones as $reparacion): ?>
            <?php
            $estaLista = $reparacion['estado'] === 'LISTA';
            $urlReparacion = View::url('/mis-pedidos/reparacion/' . $reparacion['id_reparacion']);
            $avanceReparacion = (float) $reparacion['porcentaje_avance'];
            ?>
            <a
                class="tarjeta-pendiente tarjeta-pendiente--enlace"
                href="<?= e($urlReparacion) ?>"
            >
                <span class="tarjeta-pendiente__encabezado">
                    <span class="tarjeta-pendiente__folio"><?= e($reparacion['folio']) ?></span>
                    <span class="etiqueta <?= $estaLista ? 'etiqueta--exito' : '' ?>">
                        <?= e($estados[$reparacion['estado']]['nombre']) ?>
                    </span>
                </span>

                <span class="tarjeta-pendiente__mueble">
                    <?= e($reparacion['descripcion_mueble']) ?>
                </span>

                <span class="tarjeta-pendiente__detalle">
                    Reparación · Entrega estimada: <?= e(fecha($reparacion['fecha_estimada'])) ?>
                </span>

                <span
                    class="barra-avance mt-2"
                    role="progressbar"
                    aria-label="Avance"
                    aria-valuenow="<?= e($avanceReparacion) ?>"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <span
                        class="barra-avance__relleno"
                        style="width: <?= e($avanceReparacion) ?>%"
                    ></span>
                </span>
            </a>
        <?php endforeach; ?>

    </div>
<?php endif; ?>
