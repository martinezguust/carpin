<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/proyectos.php
 * Lista de proyectos con filtros por fase, cliente, status
 * de pago y rango de fechas de entrega (RF-23).
 * Variables: $proyectos, $filtros, $fases, $clientes.
 * =====================================================
 */

View::$titulo = 'Proyectos';

$statusPago = ['PENDIENTE', 'CON_ANTICIPO', 'PAGADO'];
?>
<div class="encabezado-pagina">
    <h1 class="titulo-pagina mb-0">Proyectos</h1>
    <a class="btn btn-carpin-primario" href="<?= e(View::url('/proyectos/nuevo')) ?>">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
        Nuevo proyecto
    </a>
</div>

<!-- ===== Filtros ===== -->
<form class="filtros" method="get" action="<?= e(View::url('/proyectos')) ?>">
    <div class="filtros__campo">
        <label class="form-label" for="fase">Fase</label>
        <select class="form-select" id="fase" name="fase">
            <option value="">Todas</option>
            <?php foreach ($fases as $idFase => $fase): ?>
                <option
                    value="<?= e($idFase) ?>"
                    <?= seleccionado($filtros['id_fase'] === $idFase) ?>
                >
                    <?= e($fase['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filtros__campo">
        <label class="form-label" for="cliente">Cliente</label>
        <select class="form-select" id="cliente" name="cliente">
            <option value="">Todos</option>
            <?php foreach ($clientes as $cliente): ?>
                <option
                    value="<?= e($cliente['id_cliente']) ?>"
                    <?= seleccionado($filtros['id_cliente'] === (int) $cliente['id_cliente']) ?>
                >
                    <?= e($cliente['nombre_completo']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filtros__campo">
        <label class="form-label" for="status">Status de pago</label>
        <select class="form-select" id="status" name="status">
            <option value="">Todos</option>
            <?php foreach ($statusPago as $status): ?>
                <option
                    value="<?= e($status) ?>"
                    <?= seleccionado($filtros['status_pago'] === $status) ?>
                >
                    <?= e(etiquetaStatusPago($status)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filtros__campo">
        <label class="form-label" for="desde">Entrega desde</label>
        <input
            class="form-control"
            type="date"
            id="desde"
            name="desde"
            value="<?= e($filtros['desde']) ?>"
        >
    </div>

    <div class="filtros__campo">
        <label class="form-label" for="hasta">Entrega hasta</label>
        <input
            class="form-control"
            type="date"
            id="hasta"
            name="hasta"
            value="<?= e($filtros['hasta']) ?>"
        >
    </div>

    <div class="filtros__boton">
        <button class="btn btn-carpin-secundario" type="submit">
            <i class="bi bi-funnel" aria-hidden="true"></i>
            Filtrar
        </button>
        <a class="btn btn-link" href="<?= e(View::url('/proyectos')) ?>">Limpiar</a>
    </div>
</form>

<!-- ===== Lista ===== -->
<?php if ($proyectos === []): ?>
    <p class="estado-vacio">No hay proyectos que coincidan con los filtros.</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas">
        <thead>
            <tr>
                <th scope="col">Folio</th>
                <th scope="col">Mueble</th>
                <th scope="col">Cliente</th>
                <th scope="col">Fase</th>
                <th scope="col">Entrega</th>
                <th scope="col">Status de pago</th>
                <th scope="col">Precio</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($proyectos as $proyecto): ?>
                <tr>
                    <td data-etiqueta="Folio">
                        <a href="<?= e(View::url('/proyectos/' . $proyecto['id_proyecto'])) ?>">
                            <strong><?= e($proyecto['folio']) ?></strong>
                        </a>
                    </td>
                    <td data-etiqueta="Mueble"><?= e($proyecto['tipo_mueble']) ?></td>
                    <td data-etiqueta="Cliente"><?= e($proyecto['cliente_nombre']) ?></td>
                    <td data-etiqueta="Fase">
                        <?= e($proyecto['fase_nombre']) ?>
                        <span class="texto-ayuda">
                            (<?= e($proyecto['porcentaje_avance']) ?> %)
                        </span>
                    </td>
                    <td data-etiqueta="Entrega"><?= e(fecha($proyecto['fecha_entrega'])) ?></td>
                    <td data-etiqueta="Status de pago">
                        <?= e(etiquetaStatusPago($proyecto['status_pago'])) ?>
                    </td>
                    <td data-etiqueta="Precio"><?= e(dinero($proyecto['precio'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
