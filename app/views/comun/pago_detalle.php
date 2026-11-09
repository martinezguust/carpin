<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/pago_detalle.php
 * Pagos de un proyecto o de una reparación (CU-11): importe,
 * pagado, saldo, status y vencimiento; lista de pagos y,
 * para el Contador con saldo pendiente, "Registrar pago".
 * El sistema NO cobra: solo registra pagos en persona.
 * Variables: $tipo, $tipoRuta, $pedido, $importe, $saldo,
 * $vencido, $pagos, $metodos, $datos, $errores, $esContador.
 * =====================================================
 */

View::$titulo = $pedido['folio'];

$esReparacion = $tipo === 'REP';
$idPedido = (int) ($esReparacion ? $pedido['id_reparacion'] : $pedido['id_proyecto']);
$mueble = $esReparacion ? $pedido['descripcion_mueble'] : $pedido['tipo_mueble'];
$hoy = date('Y-m-d');
$mostrarRegistro = true;

// Liga al detalle del pedido para el Carpintero
$urlPedido = View::url(($esReparacion ? '/reparaciones/' : '/proyectos/') . $idPedido);
?>
<a class="enlace-regreso" href="<?= e(View::url('/pagos')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    <?= $esContador ? 'Pagos de clientes' : 'Finanzas' ?>
</a>

<p class="folio-pagina">
    <?= e($pedido['folio']) ?> · <?= $esReparacion ? 'Reparación' : 'Proyecto' ?>
</p>
<h1 class="titulo-pagina"><?= e($mueble) ?></h1>
<p class="subtitulo-pagina">
    <?= e($pedido['cliente_nombre']) ?> · <?= e($pedido['cliente_telefono']) ?>
    <?php if (!$esContador): ?>
        · <a href="<?= e($urlPedido) ?>">Ver el pedido</a>
    <?php endif; ?>
</p>

<?php if ($vencido): ?>
    <div class="alert alert-danger" role="alert">
        Saldo vencido: la fecha límite de pago fue el <?= e(fecha($pedido['fecha_limite_pago'])) ?>.
    </div>
<?php endif; ?>

<!-- ===== Resumen ===== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <section class="indicador">
            <p class="indicador__titulo"><?= $esReparacion ? 'Costo' : 'Precio' ?></p>
            <p class="indicador__valor indicador__valor--dinero"><?= e(dinero($importe)) ?></p>
        </section>
    </div>
    <div class="col-6 col-lg-3">
        <section class="indicador">
            <p class="indicador__titulo">Pagado</p>
            <p class="indicador__valor indicador__valor--dinero">
                <?= e(dinero($pedido['pagado'])) ?>
            </p>
        </section>
    </div>
    <div class="col-6 col-lg-3">
        <section class="indicador <?= $vencido ? 'indicador--alerta' : '' ?>">
            <p class="indicador__titulo">Saldo pendiente</p>
            <p class="indicador__valor indicador__valor--dinero"><?= e(dinero($saldo)) ?></p>
        </section>
    </div>
    <div class="col-6 col-lg-3">
        <section class="indicador">
            <p class="indicador__titulo">Status</p>
            <p class="indicador__valor indicador__valor--texto">
                <?= e(etiquetaStatusPago($pedido['status_pago'])) ?>
            </p>
            <p class="texto-ayuda mb-0">Límite: <?= e(fecha($pedido['fecha_limite_pago'])) ?></p>
        </section>
    </div>
</div>

<div class="row g-4">

    <!-- ===== Pagos ===== -->
    <div class="col-12 <?= ($esContador && $saldo > 0) ? 'col-lg-7' : '' ?>">
        <section class="tarjeta-carpin">
            <h2 class="titulo-seccion">Pagos registrados</h2>
            <?php require __DIR__ . '/../layouts/_lista_pagos.php'; ?>
        </section>
    </div>

    <?php if ($esContador && $saldo > 0): ?>
        <!-- ===== Registrar pago (CU-11) ===== -->
        <div class="col-12 col-lg-5">
            <section class="tarjeta-carpin">
                <h2 class="titulo-seccion">Registrar pago</h2>

                <form
                    method="post"
                    action="<?= e(View::url('/pagos/' . $tipoRuta . '/' . $idPedido)) ?>"
                    novalidate
                >
                    <?= Csrf::campo() ?>

                    <div class="mb-3">
                        <label class="form-label" for="monto">Monto ($ MXN)</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'monto') ?>"
                            type="number"
                            id="monto"
                            name="monto"
                            min="0.01"
                            max="<?= e(number_format($saldo, 2, '.', '')) ?>"
                            step="0.01"
                            inputmode="decimal"
                            value="<?= e($datos['monto']) ?>"
                            required
                        >
                        <?= mensajeError($errores, 'monto') ?>
                        <p class="texto-ayuda mt-1 mb-0">
                            Saldo pendiente: <?= e(dinero($saldo)) ?>
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="metodo">Método</label>
                        <select
                            class="form-select <?= claseInvalida($errores, 'metodo') ?>"
                            id="metodo"
                            name="metodo"
                            required
                        >
                            <option value="">Elige un método</option>
                            <?php foreach ($metodos as $clave => $metodo): ?>
                                <option
                                    value="<?= e($clave) ?>"
                                    <?= seleccionado($datos['metodo'] === $clave) ?>
                                >
                                    <?= e($metodo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= mensajeError($errores, 'metodo') ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="fecha">Fecha del pago</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'fecha') ?>"
                            type="date"
                            id="fecha"
                            name="fecha"
                            max="<?= e($hoy) ?>"
                            value="<?= e($datos['fecha']) ?>"
                            required
                        >
                        <?= mensajeError($errores, 'fecha') ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="referencia">Referencia (opcional)</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'referencia') ?>"
                            type="text"
                            id="referencia"
                            name="referencia"
                            maxlength="60"
                            placeholder="Ej. número de transferencia"
                            value="<?= e($datos['referencia']) ?>"
                        >
                        <?= mensajeError($errores, 'referencia') ?>
                    </div>

                    <p class="texto-ayuda">
                        El sistema no cobra: solo registra el pago hecho en la carpintería.
                    </p>

                    <button class="btn btn-carpin-primario w-100" type="submit">
                        Registrar pago
                    </button>
                </form>
            </section>
        </div>
    <?php endif; ?>

</div>
