<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/material_detalle.php
 * Detalle de un material (RF-37, RF-38): existencia, mínimo
 * y costo; formularios de entrada (compra) y salida
 * (consumo en un proyecto o reparación) e historial de
 * movimientos. El Pintor no cambia costos (RN-08).
 * Variables: $material, $movimientos, $esPintor, $destinos,
 * $destinoElegido.
 * =====================================================
 */

View::$titulo = $material['nombre'];

$idMaterial = (int) $material['id_material'];
$estaActivo = (int) $material['activo'] === 1;
$estaBajo = $estaActivo && Material::estaBajo($material);
$unidad = $material['unidad'];
$urlMovimientos = View::url('/inventario/' . $idMaterial . '/movimientos');
$hayDestinos = $destinos['PRY'] !== [] || $destinos['REP'] !== [];
?>
<a class="enlace-regreso" href="<?= e(View::url('/inventario')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Inventario
</a>

<div class="encabezado-pagina">
    <div>
        <p class="folio-pagina mb-1"><?= e(Material::CATEGORIAS[$material['categoria']]) ?></p>
        <h1 class="titulo-pagina mb-0"><?= e($material['nombre']) ?></h1>
    </div>

    <?php if (!$esPintor): ?>
        <div class="d-flex flex-wrap gap-2">
            <a
                class="btn btn-carpin-secundario"
                href="<?= e(View::url('/inventario/' . $idMaterial . '/editar')) ?>"
            >Editar</a>

            <form
                method="post"
                action="<?= e(View::url('/inventario/' . $idMaterial . '/estado')) ?>"
                <?php if ($estaActivo): ?>
                    data-confirmar="¿Dar de baja este material? Sus movimientos se conservan."
                <?php endif; ?>
            >
                <?= Csrf::campo() ?>
                <input
                    type="hidden"
                    name="accion"
                    value="<?= $estaActivo ? 'baja' : 'reactivar' ?>"
                >
                <button class="btn btn-carpin-secundario" type="submit">
                    <?= $estaActivo ? 'Dar de baja' : 'Reactivar' ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- ===== Existencia ===== -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <section class="indicador <?= $estaBajo ? 'indicador--alerta' : '' ?>">
            <p class="indicador__titulo">Existencia</p>
            <p class="indicador__valor">
                <?= e(Material::cantidadConUnidad($material['existencia'], $unidad)) ?>
            </p>
            <?php if ($estaBajo): ?>
                <p class="texto-peligro mb-0 fw-semibold">Llegó a su mínimo</p>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-6 col-md-4">
        <section class="indicador">
            <p class="indicador__titulo">Mínimo</p>
            <p class="indicador__valor">
                <?= e(Material::cantidadConUnidad($material['minimo'], $unidad)) ?>
            </p>
        </section>
    </div>
    <div class="col-6 col-md-4">
        <section class="indicador">
            <p class="indicador__titulo">Costo unitario</p>
            <p class="indicador__valor"><?= e(dinero($material['costo_unitario'])) ?></p>
        </section>
    </div>
</div>

<?php if (!$estaActivo): ?>
    <div class="alert alert-warning">
        Este material está dado de baja: no se registran movimientos hasta reactivarlo.
    </div>
<?php else: ?>
    <!-- ===== Movimientos (RF-38) ===== -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <section class="tarjeta-carpin h-100">
                <h2 class="titulo-seccion">Registrar entrada (compra)</h2>

                <form method="post" action="<?= e($urlMovimientos) ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="tipo" value="ENTRADA">

                    <label class="form-label" for="cantidadEntrada">
                        Cantidad (<?= e(Material::UNIDADES[$unidad]['nombre']) ?>)
                    </label>
                    <input
                        class="form-control mb-3"
                        type="number"
                        id="cantidadEntrada"
                        name="cantidad"
                        min="0.01"
                        step="0.01"
                        inputmode="decimal"
                        required
                    >

                    <?php if (!$esPintor): ?>
                        <label class="form-label" for="costoEntrada">
                            Costo unitario de la compra
                        </label>
                        <input
                            class="form-control mb-1"
                            type="number"
                            id="costoEntrada"
                            name="costo_unitario"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                            value="<?= e($material['costo_unitario']) ?>"
                            required
                        >
                        <p class="texto-ayuda mb-3">Se guarda como el costo actual del material.</p>
                    <?php else: ?>
                        <p class="texto-ayuda mb-3">
                            Se registra con el costo actual; los costos los cambian el Carpintero o
                            el Contador.
                        </p>
                    <?php endif; ?>

                    <label class="form-label" for="notaEntrada">Nota (opcional)</label>
                    <input
                        class="form-control mb-3"
                        type="text"
                        id="notaEntrada"
                        name="nota"
                        maxlength="255"
                        placeholder="Ej. proveedor o número de compra"
                    >

                    <button class="btn btn-carpin-primario w-100" type="submit">
                        Guardar entrada
                    </button>
                </form>
            </section>
        </div>

        <div class="col-12 col-lg-6">
            <section class="tarjeta-carpin h-100">
                <h2 class="titulo-seccion">Registrar salida (consumo)</h2>

                <?php if (!$hayDestinos): ?>
                    <p class="texto-ayuda mb-0">
                        No hay proyectos ni reparaciones abiertos a los que se pueda dar salida.
                    </p>
                <?php else: ?>
                    <form method="post" action="<?= e($urlMovimientos) ?>">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="tipo" value="SALIDA">

                        <label class="form-label" for="cantidadSalida">
                            Cantidad (<?= e(Material::UNIDADES[$unidad]['nombre']) ?>)
                        </label>
                        <input
                            class="form-control mb-3"
                            type="number"
                            id="cantidadSalida"
                            name="cantidad"
                            min="0.01"
                            step="0.01"
                            inputmode="decimal"
                            required
                        >

                        <label class="form-label" for="destino">¿Dónde se usó?</label>
                        <select class="form-select mb-3" id="destino" name="destino" required>
                            <option value="">Elige un proyecto o reparación</option>
                            <?php if ($destinos['PRY'] !== []): ?>
                                <optgroup label="Proyectos">
                                    <?php foreach ($destinos['PRY'] as $idDestino => $texto): ?>
                                        <?php $valor = 'PRY-' . $idDestino; ?>
                                        <option
                                            value="<?= e($valor) ?>"
                                            <?= seleccionado($destinoElegido === $valor) ?>
                                        >
                                            <?= e($texto) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <?php if ($destinos['REP'] !== []): ?>
                                <optgroup label="Reparaciones">
                                    <?php foreach ($destinos['REP'] as $idDestino => $texto): ?>
                                        <option value="<?= e('REP-' . $idDestino) ?>">
                                            <?= e($texto) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>

                        <label class="form-label" for="notaSalida">Nota (opcional)</label>
                        <input
                            class="form-control mb-3"
                            type="text"
                            id="notaSalida"
                            name="nota"
                            maxlength="255"
                        >

                        <button class="btn btn-carpin-accion w-100" type="submit">
                            Guardar salida
                        </button>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </div>
<?php endif; ?>

<!-- ===== Historial de movimientos ===== -->
<h2 class="titulo-seccion">Movimientos</h2>

<?php if ($movimientos === []): ?>
    <p class="estado-vacio">Todavía no hay movimientos.</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas">
        <thead>
            <tr>
                <th scope="col">Fecha</th>
                <th scope="col">Tipo</th>
                <th scope="col">Cantidad</th>
                <th scope="col">Costo unitario</th>
                <th scope="col">Destino</th>
                <th scope="col">Registró</th>
                <th scope="col">Nota</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimientos as $movimiento): ?>
                <?php
                $esEntrada = $movimiento['tipo'] === 'ENTRADA';
                $destinoMovimiento = $movimiento['folio_proyecto']
                    ?? $movimiento['folio_reparacion']
                    ?? '—';
                $claseTipo = $esEntrada ? 'etiqueta--exito' : 'etiqueta--advertencia';
                $cantidadMovida = Material::cantidadConUnidad($movimiento['cantidad'], $unidad);
                ?>
                <tr>
                    <td data-etiqueta="Fecha"><?= e(fechaHora($movimiento['fecha'])) ?></td>
                    <td data-etiqueta="Tipo">
                        <span class="etiqueta <?= e($claseTipo) ?>">
                            <?= $esEntrada ? 'Entrada' : 'Salida' ?>
                        </span>
                    </td>
                    <td data-etiqueta="Cantidad">
                        <?= $esEntrada ? '+' : '−' ?><?= e($cantidadMovida) ?>
                    </td>
                    <td data-etiqueta="Costo unitario">
                        <?= e(dinero($movimiento['costo_unitario'])) ?>
                    </td>
                    <td data-etiqueta="Destino"><?= e($destinoMovimiento) ?></td>
                    <td data-etiqueta="Registró"><?= e($movimiento['usuario_nombre']) ?></td>
                    <td data-etiqueta="Nota"><?= e($movimiento['nota'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
