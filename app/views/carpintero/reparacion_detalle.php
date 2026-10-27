<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/reparacion_detalle.php
 * Detalle de la reparación para el Carpintero (HU-26):
 * estado con su avance y el botón para pasar al siguiente
 * (RN-05), datos, fotos de "antes" y "después" y formulario
 * para subir fotos de "después".
 * Variables: $reparacion, $imagenes, $saldo, $siguienteEstado,
 * $estados, $tamanos, $lugaresLibres.
 * =====================================================
 */

View::$titulo = $reparacion['folio'];

$idReparacion = (int) $reparacion['id_reparacion'];
$avance = (float) $reparacion['porcentaje_avance'];
$entregaConSaldo = $siguienteEstado === 'ENTREGADA' && $saldo > 0;
?>
<a class="enlace-regreso" href="<?= e(View::url('/reparaciones')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Reparaciones
</a>

<p class="folio-pagina"><?= e($reparacion['folio']) ?></p>
<h1 class="titulo-pagina"><?= e($reparacion['descripcion_mueble']) ?></h1>
<p class="subtitulo-pagina">
    <?= e($reparacion['cliente_nombre']) ?> · entrega estimada
    <?= e(fecha($reparacion['fecha_estimada'])) ?>
</p>

<!-- ===== Estado ===== -->
<section class="tarjeta-carpin mb-4">
    <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2 mb-2">
        <h2 class="titulo-seccion mb-0">
            Estado: <?= e($estados[$reparacion['estado']]['nombre']) ?>
        </h2>
        <span class="fw-semibold"><?= e($reparacion['porcentaje_avance']) ?> %</span>
    </div>

    <div
        class="barra-avance barra-avance--grande mb-3"
        role="progressbar"
        aria-label="Avance de la reparación"
        aria-valuenow="<?= e($avance) ?>"
        aria-valuemin="0"
        aria-valuemax="100"
    >
        <span class="barra-avance__relleno" style="width: <?= e($avance) ?>%"></span>
    </div>

    <?php if ($siguienteEstado !== null): ?>
        <form
            method="post"
            action="<?= e(View::url('/reparaciones/' . $idReparacion . '/estado')) ?>"
        >
            <?= Csrf::campo() ?>

            <?php if ($entregaConSaldo): ?>
                <div class="alert alert-warning" role="alert">
                    <p class="mb-2">
                        El cliente tiene un saldo pendiente de
                        <strong><?= e(dinero($saldo)) ?></strong>.
                    </p>
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="confirmarSaldo"
                            name="confirmar_saldo"
                            value="1"
                            required
                        >
                        <label class="form-check-label" for="confirmarSaldo">
                            Confirmo que se entrega con saldo pendiente.
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <button class="btn btn-carpin-accion" type="submit">
                Marcar como: <?= e($estados[$siguienteEstado]['nombre']) ?>
            </button>
        </form>
    <?php elseif ($reparacion['entregada_en'] !== null): ?>
        <p class="texto-ayuda mb-0">
            Entregada el <?= e(fechaHora($reparacion['entregada_en'])) ?>.
        </p>
    <?php endif; ?>
</section>

<div class="row g-4">

    <!-- ===== Datos ===== -->
    <div class="col-12 col-lg-6">
        <section class="tarjeta-carpin">
            <h3 class="subtitulo-seccion">Dueño</h3>
            <dl class="lista-datos">
                <dt>Nombre</dt>
                <dd><?= e($reparacion['cliente_nombre']) ?></dd>
                <dt>Teléfono</dt>
                <dd><?= e($reparacion['cliente_telefono']) ?></dd>
            </dl>

            <h3 class="subtitulo-seccion">Reparación</h3>
            <dl class="lista-datos">
                <dt>Tamaño</dt>
                <dd><?= e($tamanos[$reparacion['tamano']]) ?></dd>
                <dt>Tipo de reparación</dt>
                <dd><?= e($reparacion['tipo_reparacion']) ?></dd>
                <dt>Descripción del daño</dt>
                <dd><?= e($reparacion['descripcion_danio'] ?? '—') ?></dd>
                <dt>Recibida</dt>
                <dd><?= e(fecha($reparacion['fecha_recepcion'])) ?></dd>
                <dt>Límite de pago</dt>
                <dd><?= e(fecha($reparacion['fecha_limite_pago'])) ?></dd>
            </dl>

            <h3 class="subtitulo-seccion">Pago</h3>
            <dl class="lista-datos mb-0">
                <dt>Costo</dt>
                <dd><?= e(dinero($reparacion['costo'])) ?></dd>
                <dt>Pagado</dt>
                <dd><?= e(dinero($reparacion['pagado'])) ?></dd>
                <dt>Saldo pendiente</dt>
                <dd><strong><?= e(dinero($saldo)) ?></strong></dd>
                <dt>Status de pago</dt>
                <dd class="mb-0"><?= e(etiquetaStatusPago($reparacion['status_pago'])) ?></dd>
            </dl>
        </section>
    </div>

    <!-- ===== Fotos ===== -->
    <div class="col-12 col-lg-6">
        <section class="tarjeta-carpin">
            <h2 class="titulo-seccion">Fotos (<?= e(count($imagenes)) ?> de 6)</h2>

            <?php require __DIR__ . '/../layouts/_galeria_reparacion.php'; ?>

            <?php if ($lugaresLibres > 0): ?>
                <form
                    class="mt-3"
                    method="post"
                    action="<?= e(View::url('/reparaciones/' . $idReparacion . '/fotos')) ?>"
                    enctype="multipart/form-data"
                >
                    <?= Csrf::campo() ?>

                    <label class="form-label" for="fotos">
                        Subir fotos de "después" (hasta <?= e($lugaresLibres) ?>)
                    </label>
                    <input
                        class="form-control mb-2"
                        type="file"
                        id="fotos"
                        name="fotos[]"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        required
                    >
                    <button class="btn btn-carpin-secundario" type="submit">Subir fotos</button>
                </form>
            <?php else: ?>
                <p class="texto-ayuda mt-3 mb-0">Ya tiene las 6 fotos permitidas.</p>
            <?php endif; ?>
        </section>
    </div>

</div>
