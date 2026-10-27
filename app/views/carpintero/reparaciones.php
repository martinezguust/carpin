<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/reparaciones.php
 * Lista de reparaciones con filtro por estado (HU-26).
 * Variables: $reparaciones, $estado, $estados.
 * =====================================================
 */

View::$titulo = 'Reparaciones';
?>
<div class="encabezado-pagina">
    <h1 class="titulo-pagina mb-0">Reparaciones</h1>
    <a class="btn btn-carpin-primario" href="<?= e(View::url('/reparaciones/nueva')) ?>">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
        Nueva reparación
    </a>
</div>

<form class="filtros" method="get" action="<?= e(View::url('/reparaciones')) ?>">
    <div class="filtros__campo">
        <label class="form-label" for="estado">Estado</label>
        <select class="form-select" id="estado" name="estado">
            <option value="">Todos</option>
            <?php foreach ($estados as $clave => $datosEstado): ?>
                <option value="<?= e($clave) ?>" <?= seleccionado($estado === $clave) ?>>
                    <?= e($datosEstado['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filtros__boton">
        <button class="btn btn-carpin-secundario" type="submit">
            <i class="bi bi-funnel" aria-hidden="true"></i>
            Filtrar
        </button>
    </div>
</form>

<?php if ($reparaciones === []): ?>
    <p class="estado-vacio">No hay reparaciones registradas.</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas">
        <thead>
            <tr>
                <th scope="col">Folio</th>
                <th scope="col">Mueble</th>
                <th scope="col">Dueño</th>
                <th scope="col">Estado</th>
                <th scope="col">Fecha estimada</th>
                <th scope="col">Costo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reparaciones as $reparacion): ?>
                <tr>
                    <td data-etiqueta="Folio">
                        <?php
                        $urlReparacion = View::url('/reparaciones/' . $reparacion['id_reparacion']);
                        ?>
                        <a href="<?= e($urlReparacion) ?>">
                            <strong><?= e($reparacion['folio']) ?></strong>
                        </a>
                    </td>
                    <td data-etiqueta="Mueble"><?= e($reparacion['descripcion_mueble']) ?></td>
                    <td data-etiqueta="Dueño"><?= e($reparacion['cliente_nombre']) ?></td>
                    <td data-etiqueta="Estado">
                        <?= e($estados[$reparacion['estado']]['nombre']) ?>
                    </td>
                    <td data-etiqueta="Fecha estimada">
                        <?= e(fecha($reparacion['fecha_estimada'])) ?>
                    </td>
                    <td data-etiqueta="Costo"><?= e(dinero($reparacion['costo'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
