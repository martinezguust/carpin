<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/inventario.php
 * Lista de materiales (RF-37, RF-39): los que llegaron a su
 * mínimo se marcan en rojo. El Pintor solo ve Pintura,
 * Solvente y Barniz ("Inventario de pinturas y solventes").
 * Si llega ?proyecto=id (desde la pestaña "Materiales" del
 * proyecto), la liga de cada material lleva ese proyecto ya
 * elegido para la salida.
 * Variables: $materiales, $categorias, $categoria,
 * $incluirBajas, $esPintor, $proyectoDestino.
 * =====================================================
 */

View::$titulo = $esPintor ? 'Inventario de pinturas y solventes' : 'Inventario';

$esContador = Auth::rol() === Auth::CONTADOR;
$sufijoDestino = ($proyectoDestino > 0) ? '?proyecto=' . $proyectoDestino : '';
?>
<div class="encabezado-pagina">
    <h1 class="titulo-pagina mb-0"><?= e(View::$titulo) ?></h1>

    <?php if (!$esPintor): ?>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($esContador): ?>
                <a
                    class="btn btn-carpin-secundario"
                    href="<?= e(View::url('/inventario/faltantes')) ?>"
                >
                    Faltantes / por reemplazar
                </a>
                <a class="btn btn-carpin-secundario" href="<?= e(View::url('/danios')) ?>">
                    Reportes de daño
                </a>
            <?php endif; ?>
            <a class="btn btn-carpin-primario" href="<?= e(View::url('/inventario/nuevo')) ?>">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Nuevo material
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if ($proyectoDestino > 0): ?>
    <div class="alert alert-info" role="status">
        Elige el material para registrar la salida; el proyecto ya quedará elegido.
    </div>
<?php endif; ?>

<!-- ===== Filtros ===== -->
<form class="filtros" method="get" action="<?= e(View::url('/inventario')) ?>">
    <?php if ($proyectoDestino > 0): ?>
        <input type="hidden" name="proyecto" value="<?= e($proyectoDestino) ?>">
    <?php endif; ?>
    <div class="filtros__campo">
        <label class="form-label" for="categoria">Categoría</label>
        <select class="form-select" id="categoria" name="categoria">
            <option value="">Todas</option>
            <?php foreach ($categorias as $clave => $nombre): ?>
                <option value="<?= e($clave) ?>" <?= seleccionado($categoria === $clave) ?>>
                    <?= e($nombre) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (!$esPintor): ?>
        <div class="filtros__campo d-flex align-items-end">
            <div class="form-check mb-2">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="bajas"
                    name="bajas"
                    value="1"
                    <?= $incluirBajas ? 'checked' : '' ?>
                >
                <label class="form-check-label" for="bajas">Mostrar dados de baja</label>
            </div>
        </div>
    <?php endif; ?>

    <div class="filtros__boton">
        <button class="btn btn-carpin-secundario" type="submit">
            <i class="bi bi-funnel" aria-hidden="true"></i>
            Filtrar
        </button>
    </div>
</form>

<!-- ===== Lista ===== -->
<?php if ($materiales === []): ?>
    <p class="estado-vacio">No hay materiales que coincidan con el filtro.</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas">
        <thead>
            <tr>
                <th scope="col">Material</th>
                <th scope="col">Categoría</th>
                <th scope="col">Existencia</th>
                <th scope="col">Mínimo</th>
                <th scope="col">Costo unitario</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materiales as $material): ?>
                <?php
                $estaBajo = (int) $material['activo'] === 1 && Material::estaBajo($material);
                $claseFila = $estaBajo ? 'fila-alerta' : '';
                $rutaMaterial = '/inventario/' . $material['id_material'] . $sufijoDestino;
                ?>
                <tr class="<?= e($claseFila) ?>">
                    <td data-etiqueta="Material">
                        <span>
                            <strong><?= e($material['nombre']) ?></strong>
                            <?php if ($estaBajo): ?>
                                <span class="etiqueta etiqueta--error">Existencia baja</span>
                            <?php endif; ?>
                            <?php if ((int) $material['activo'] !== 1): ?>
                                <span class="etiqueta">Dado de baja</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td data-etiqueta="Categoría">
                        <?= e(Material::CATEGORIAS[$material['categoria']]) ?>
                    </td>
                    <td data-etiqueta="Existencia">
                        <?= e(Material::cantidadConUnidad(
                            $material['existencia'],
                            $material['unidad']
                        )) ?>
                    </td>
                    <td data-etiqueta="Mínimo">
                        <?= e(Material::cantidadConUnidad(
                            $material['minimo'],
                            $material['unidad']
                        )) ?>
                    </td>
                    <td data-etiqueta="Costo unitario">
                        <?= e(dinero($material['costo_unitario'])) ?>
                    </td>
                    <td data-etiqueta="Acciones">
                        <a href="<?= e(View::url($rutaMaterial)) ?>">
                            Ver y mover
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
