<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/_datos_proyecto.php
 * Pestaña "Datos" del detalle del proyecto: cliente, fechas,
 * pago (solo consulta, respuesta 10), datos del mueble y
 * "Producción" con las capas y los secados registrados por
 * el Pintor (solo consulta, respuesta 7 de la Fase 2).
 * Variables (de proyecto_detalle.php): $proyecto, $saldo,
 * $capas, $secados.
 * =====================================================
 */

// Medidas en formato "alto × ancho × fondo cm", sin ".0" de sobra
$medidas = array_filter([$proyecto['alto_cm'], $proyecto['ancho_cm'], $proyecto['fondo_cm']]);
$medidas = array_map(fn ($medida) => rtrim(rtrim((string) $medida, '0'), '.'), $medidas);
$textoMedidas = ($medidas === []) ? 'Sin medidas' : implode(' × ', $medidas) . ' cm';
?>
<div class="row g-4">
    <div class="col-12 col-lg-6">
        <h3 class="subtitulo-seccion">Cliente</h3>
        <dl class="lista-datos">
            <dt>Nombre</dt>
            <dd><?= e($proyecto['cliente_nombre']) ?></dd>
            <dt>Teléfono</dt>
            <dd><?= e($proyecto['cliente_telefono']) ?></dd>
            <dt>Correo</dt>
            <dd><?= e($proyecto['cliente_correo'] ?? '—') ?></dd>
            <dt>Dirección</dt>
            <dd><?= e($proyecto['cliente_direccion'] ?? '—') ?></dd>
        </dl>

        <h3 class="subtitulo-seccion">Fechas</h3>
        <dl class="lista-datos">
            <dt>Registro</dt>
            <dd><?= e(fecha($proyecto['fecha_registro'])) ?></dd>
            <dt>Entrega estimada</dt>
            <dd><?= e(fecha($proyecto['fecha_entrega'])) ?></dd>
            <dt>Límite de pago</dt>
            <dd><?= e(fecha($proyecto['fecha_limite_pago'])) ?></dd>
            <?php if ($proyecto['entregado_en'] !== null): ?>
                <dt>Entregado</dt>
                <dd><?= e(fechaHora($proyecto['entregado_en'])) ?></dd>
            <?php endif; ?>
        </dl>

        <h3 class="subtitulo-seccion">Pago</h3>
        <dl class="lista-datos">
            <dt>Precio</dt>
            <dd><?= e(dinero($proyecto['precio'])) ?></dd>
            <dt>Pagado</dt>
            <dd><?= e(dinero($proyecto['pagado'])) ?></dd>
            <dt>Saldo pendiente</dt>
            <dd><strong><?= e(dinero($saldo)) ?></strong></dd>
            <dt>Status de pago</dt>
            <dd><?= e(etiquetaStatusPago($proyecto['status_pago'])) ?></dd>
        </dl>
    </div>

    <div class="col-12 col-lg-6">
        <h3 class="subtitulo-seccion">Mueble</h3>
        <dl class="lista-datos">
            <dt>Tipo</dt>
            <dd><?= e($proyecto['tipo_mueble']) ?></dd>
            <dt>Descripción</dt>
            <dd><?= e($proyecto['descripcion'] ?? '—') ?></dd>
            <dt>Medidas (alto × ancho × fondo)</dt>
            <dd><?= e($textoMedidas) ?></dd>
            <dt>Madera</dt>
            <dd><?= e($proyecto['madera']) ?></dd>
            <dt>Acabado</dt>
            <dd><?= e($proyecto['acabado'] ?? '—') ?></dd>
            <dt>Color</dt>
            <dd><?= e($proyecto['color'] ?? '—') ?></dd>
            <dt>Granulado de lija</dt>
            <dd><?= e($proyecto['granulado_lija'] ?? '—') ?></dd>
            <dt>Ficha de color</dt>
            <dd><?= e($proyecto['ficha_color'] ?? '—') ?></dd>
        </dl>

        <!-- ===== Producción (capas y secado) ===== -->
        <h3 class="subtitulo-seccion">Producción</h3>

        <?php if ($capas === [] && $secados === []): ?>
            <p class="texto-ayuda">El Pintor todavía no registra capas ni secados.</p>
        <?php endif; ?>

        <?php if ($capas !== []): ?>
            <p class="fw-semibold mb-1">Capas aplicadas: <?= e(count($capas)) ?></p>
            <ul class="lista-produccion">
                <?php foreach ($capas as $capa): ?>
                    <li>
                        Capa <?= e($capa['numero_capa']) ?> · <?= e($capa['producto']) ?>
                        <span class="texto-ayuda d-block">
                            <?= e(fechaHora($capa['fecha'])) ?> · <?= e($capa['pintor_nombre']) ?>
                            <?php if ($capa['observaciones'] !== null): ?>
                                · <?= e($capa['observaciones']) ?>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($secados !== []): ?>
            <p class="fw-semibold mb-1">Secados programados</p>
            <ul class="lista-produccion">
                <?php foreach ($secados as $secado): ?>
                    <li>
                        <?= e(fechaHora($secado['inicio'])) ?> · <?= e($secado['horas']) ?> h
                        <span class="texto-ayuda d-block">
                            Termina: <?= e(fechaHora($secado['fin_estimado'])) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
