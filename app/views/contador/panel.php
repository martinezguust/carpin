<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/contador/panel.php
 * Panel del Contador (SRS 10.3, respuesta 19 de la Fase 3):
 * total por cobrar, saldos vencidos y existencias bajas, con
 * listas cortas. "Comprobantes por vencer" llega en la Fase 4.
 * Variables: $totalPorCobrar, $cuentasConSaldo, $vencidos,
 * $totalVencidos, $bajos, $totalBajos.
 * =====================================================
 */

View::$titulo = 'Panel';

// Los indicadores se marcan en rojo cuando hay algo pendiente
$claseVencidos = ($totalVencidos > 0) ? 'indicador--alerta' : '';
$claseBajos = ($totalBajos > 0) ? 'indicador--alerta' : '';
?>
<h1 class="titulo-pagina">Panel</h1>

<!-- ===== Indicadores ===== -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <a class="indicador indicador--enlace" href="<?= e(View::url('/pagos')) ?>">
            <p class="indicador__titulo">Por cobrar</p>
            <p class="indicador__valor"><?= e(dinero($totalPorCobrar)) ?></p>
            <p class="texto-ayuda mb-0"><?= e($cuentasConSaldo) ?> pedido(s) con saldo</p>
        </a>
    </div>

    <div class="col-12 col-md-4">
        <a
            class="indicador indicador--enlace <?= e($claseVencidos) ?>"
            href="<?= e(View::url('/pagos?situacion=VENCIDOS')) ?>"
        >
            <p class="indicador__titulo">Saldos vencidos</p>
            <p class="indicador__valor"><?= e($totalVencidos) ?></p>
        </a>
    </div>

    <div class="col-12 col-md-4">
        <a
            class="indicador indicador--enlace <?= e($claseBajos) ?>"
            href="<?= e(View::url('/inventario/faltantes')) ?>"
        >
            <p class="indicador__titulo">Existencias bajas</p>
            <p class="indicador__valor"><?= e($totalBajos) ?></p>
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- ===== Saldos vencidos ===== -->
    <div class="col-12 col-lg-6">
        <section class="tarjeta-carpin h-100">
            <h2 class="titulo-seccion">Saldos vencidos</h2>

            <?php if ($vencidos === []): ?>
                <p class="texto-ayuda mb-0">No hay saldos vencidos.</p>
            <?php else: ?>
                <ul class="lista-corta">
                    <?php foreach ($vencidos as $cuenta): ?>
                        <?php
                        $tipoRuta = ($cuenta['tipo'] === 'REP') ? 'reparacion' : 'proyecto';
                        $urlCuenta = View::url('/pagos/' . $tipoRuta . '/' . $cuenta['id']);
                        ?>
                        <li>
                            <a href="<?= e($urlCuenta) ?>">
                                <strong><?= e($cuenta['folio']) ?></strong>
                            </a>
                            · <?= e($cuenta['cliente_nombre']) ?>
                            <span class="d-block texto-ayuda">
                                Debe <?= e(dinero($cuenta['saldo'])) ?> desde
                                <?= e(fecha($cuenta['fecha_limite_pago'])) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($totalVencidos > count($vencidos)): ?>
                    <a
                        class="enlace-publico"
                        href="<?= e(View::url('/pagos?situacion=VENCIDOS')) ?>"
                    >
                        Ver los <?= e($totalVencidos) ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>

    <!-- ===== Existencias bajas ===== -->
    <div class="col-12 col-lg-6">
        <section class="tarjeta-carpin h-100">
            <h2 class="titulo-seccion">Existencias bajas</h2>

            <?php if ($bajos === []): ?>
                <p class="texto-ayuda mb-0">Ningún material está en su mínimo.</p>
            <?php else: ?>
                <ul class="lista-corta">
                    <?php foreach ($bajos as $material): ?>
                        <?php
                        $urlMaterial = View::url('/inventario/' . $material['id_material']);
                        ?>
                        <li>
                            <a href="<?= e($urlMaterial) ?>">
                                <strong><?= e($material['nombre']) ?></strong>
                            </a>
                            <span class="d-block texto-ayuda">
                                Quedan
                                <?= e(Material::cantidadConUnidad(
                                    $material['existencia'],
                                    $material['unidad']
                                )) ?>
                                (mínimo
                                <?= e(Material::cantidadConUnidad(
                                    $material['minimo'],
                                    $material['unidad']
                                )) ?>)
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="enlace-publico" href="<?= e(View::url('/inventario/faltantes')) ?>">
                    Ver faltantes y por reemplazar
                </a>
            <?php endif; ?>
        </section>
    </div>

</div>
