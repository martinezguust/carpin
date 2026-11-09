<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/_lista_danios.php
 * Tarjetas de reportes de daño (RF-40) con foto, estado y,
 * para el Carpintero y el Contador, el botón para pasar al
 * siguiente estado (solo avanza).
 * Variables: $reportes; $puedeCambiarEstado, true para
 * mostrar el botón de estado.
 * =====================================================
 */

$clasesEstado = [
    'REPORTADO'   => 'etiqueta--error',
    'EN_ATENCION' => 'etiqueta--advertencia',
    'RESUELTO'    => 'etiqueta--exito',
];
?>
<div class="lista-pendientes">
    <?php foreach ($reportes as $reporte): ?>
        <?php
        $idReporte = (int) $reporte['id_reporte'];
        $siguiente = ReporteDanio::siguienteEstado($reporte['estado']);
        ?>
        <article class="tarjeta-pendiente" id="reporte<?= e($idReporte) ?>">
            <div class="tarjeta-pendiente__encabezado">
                <span class="tarjeta-pendiente__folio">
                    <?= e(ReporteDanio::TIPOS[$reporte['tipo']]) ?> ·
                    <?= e(fecha($reporte['fecha'])) ?>
                </span>
                <span class="etiqueta <?= e($clasesEstado[$reporte['estado']]) ?>">
                    <?= e(ReporteDanio::ESTADOS[$reporte['estado']]) ?>
                </span>
            </div>

            <h2 class="tarjeta-pendiente__mueble"><?= e($reporte['elemento']) ?></h2>

            <?php if ($reporte['foto_ruta'] !== null): ?>
                <a href="<?= e(View::url($reporte['foto_ruta'])) ?>" target="_blank" rel="noopener">
                    <img
                        class="foto-danio"
                        src="<?= e(View::url($reporte['foto_ruta'])) ?>"
                        alt="Foto del daño de <?= e($reporte['elemento']) ?>"
                        loading="lazy"
                    >
                </a>
            <?php endif; ?>

            <p class="mb-2"><?= nl2br(e($reporte['descripcion'])) ?></p>
            <p class="tarjeta-pendiente__detalle">
                Acción sugerida:
                <strong><?= e(ReporteDanio::ACCIONES[$reporte['accion']]) ?></strong>
            </p>
            <p class="tarjeta-pendiente__detalle mb-0">
                Reportó: <?= e($reporte['reporta_nombre']) ?>
                <?php if ($reporte['resuelto_en'] !== null): ?>
                    · Resuelto el <?= e(fechaHora($reporte['resuelto_en'])) ?>
                <?php endif; ?>
            </p>

            <?php if ($puedeCambiarEstado && $siguiente !== null): ?>
                <form
                    class="mt-3"
                    method="post"
                    action="<?= e(View::url('/danios/' . $idReporte . '/estado')) ?>"
                >
                    <?= Csrf::campo() ?>
                    <button class="btn btn-carpin-accion w-100" type="submit">
                        Marcar como: <?= e(ReporteDanio::ESTADOS[$siguiente]) ?>
                    </button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
