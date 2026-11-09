<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/pagos.php
 * Cuentas por cobrar de proyectos y reparaciones (HU-27,
 * HU-15): "Pagos de clientes" del Contador y "Finanzas"
 * (solo consulta) del Carpintero. Los saldos vencidos se
 * marcan en rojo (RN-16).
 * Variables: $cuentas, $filtros, $situaciones, $esContador.
 * =====================================================
 */

View::$titulo = $esContador ? 'Pagos de clientes' : 'Finanzas';

$tiposPedido = [
    'PRY' => 'Proyectos',
    'REP' => 'Reparaciones',
];
?>
<h1 class="titulo-pagina"><?= e(View::$titulo) ?></h1>

<?php if (!$esContador): ?>
    <p class="subtitulo-pagina">Solo consulta: los pagos los registra el Contador.</p>
<?php endif; ?>

<!-- ===== Filtros ===== -->
<form class="filtros" method="get" action="<?= e(View::url('/pagos')) ?>">
    <div class="filtros__campo">
        <label class="form-label" for="situacion">Situación</label>
        <select class="form-select" id="situacion" name="situacion">
            <?php foreach ($situaciones as $clave => $nombre): ?>
                <option
                    value="<?= e($clave) ?>"
                    <?= seleccionado($filtros['situacion'] === $clave) ?>
                >
                    <?= e($nombre) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filtros__campo">
        <label class="form-label" for="tipo">Tipo</label>
        <select class="form-select" id="tipo" name="tipo">
            <option value="">Proyectos y reparaciones</option>
            <?php foreach ($tiposPedido as $clave => $nombre): ?>
                <option value="<?= e($clave) ?>" <?= seleccionado($filtros['tipo'] === $clave) ?>>
                    <?= e($nombre) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filtros__campo filtros__campo--ancho">
        <label class="form-label" for="q">Buscar</label>
        <input
            class="form-control"
            type="search"
            id="q"
            name="q"
            placeholder="Folio o cliente"
            value="<?= e($filtros['busqueda']) ?>"
        >
    </div>

    <div class="filtros__boton">
        <button class="btn btn-carpin-secundario" type="submit">
            <i class="bi bi-funnel" aria-hidden="true"></i>
            Filtrar
        </button>
    </div>
</form>

<!-- ===== Lista ===== -->
<?php if ($cuentas === []): ?>
    <p class="estado-vacio">No hay pedidos que coincidan con el filtro.</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas">
        <thead>
            <tr>
                <th scope="col">Folio</th>
                <th scope="col">Cliente</th>
                <th scope="col">Importe</th>
                <th scope="col">Pagado</th>
                <th scope="col">Saldo</th>
                <th scope="col">Límite de pago</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cuentas as $cuenta): ?>
                <?php
                $tipoRuta = ($cuenta['tipo'] === 'REP') ? 'reparacion' : 'proyecto';
                $urlCuenta = View::url('/pagos/' . $tipoRuta . '/' . $cuenta['id']);
                $estaVencido = (int) $cuenta['vencido'] === 1;
                ?>
                <tr class="<?= $estaVencido ? 'fila-alerta' : '' ?>">
                    <td data-etiqueta="Folio">
                        <span>
                            <a href="<?= e($urlCuenta) ?>">
                                <strong><?= e($cuenta['folio']) ?></strong>
                            </a>
                            <span class="d-block texto-ayuda"><?= e($cuenta['mueble']) ?></span>
                        </span>
                    </td>
                    <td data-etiqueta="Cliente"><?= e($cuenta['cliente_nombre']) ?></td>
                    <td data-etiqueta="Importe"><?= e(dinero($cuenta['importe'])) ?></td>
                    <td data-etiqueta="Pagado"><?= e(dinero($cuenta['pagado'])) ?></td>
                    <td data-etiqueta="Saldo">
                        <strong><?= e(dinero(max(0, (float) $cuenta['saldo']))) ?></strong>
                    </td>
                    <td data-etiqueta="Límite de pago">
                        <span>
                            <?= e(fecha($cuenta['fecha_limite_pago'])) ?>
                            <?php if ($estaVencido): ?>
                                <span class="etiqueta etiqueta--error">Vencido</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td data-etiqueta="Status">
                        <?= e(etiquetaStatusPago($cuenta['status_pago'])) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
