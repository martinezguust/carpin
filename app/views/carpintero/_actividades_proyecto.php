<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/_actividades_proyecto.php
 * Pestaña "Actividades" del detalle del proyecto (CU-04):
 * lista de actividades con reasignación de las pendientes
 * y un formulario de asignación por tipo (Armado, Lijado,
 * Pintura), cada uno con los trabajadores activos del rol.
 * Variables (de proyecto_detalle.php): $actividades,
 * $trabajadoresPorTipo, $rolPorTipo, $idProyecto, $estaEntregado.
 * =====================================================
 */

$urlAsignar = View::url('/proyectos/' . $idProyecto . '/actividades');
$hoy = date('Y-m-d');
?>
<!-- ===== Actividades asignadas ===== -->
<?php if ($actividades === []): ?>
    <p class="estado-vacio">Todavía no hay actividades asignadas.</p>
<?php else: ?>
    <table class="table tabla-carpin tabla-tarjetas">
        <thead>
            <tr>
                <th scope="col">Actividad</th>
                <th scope="col">Trabajador</th>
                <th scope="col">Fecha compromiso</th>
                <th scope="col">Estado</th>
                <th scope="col">Reasignar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($actividades as $actividad): ?>
                <?php
                $idActividad = (int) $actividad['id_actividad'];
                $urlReasignar = View::url('/actividades/' . $idActividad . '/reasignar');
                $idSelector = 'reasignar' . $idActividad;

                // Trabajadores del mismo rol a quienes se puede reasignar
                $otros = array_filter(
                    $trabajadoresPorTipo[$actividad['tipo']],
                    fn ($trabajador) => (int) $trabajador['id_trabajador']
                        !== (int) $actividad['id_trabajador']
                );
                $puedeReasignar = $actividad['estado'] === 'PENDIENTE'
                    && !$estaEntregado
                    && $otros !== [];
                ?>
                <tr>
                    <td data-etiqueta="Actividad">
                        <?= e(etiquetaActividad($actividad['tipo'])) ?>
                    </td>
                    <td data-etiqueta="Trabajador">
                        <?= e($actividad['trabajador_nombre']) ?>
                    </td>
                    <td data-etiqueta="Fecha compromiso">
                        <?= e(fecha($actividad['fecha_compromiso'])) ?>
                    </td>
                    <td data-etiqueta="Estado">
                        <?php if ($actividad['estado'] === 'TERMINADA'): ?>
                            <span class="etiqueta etiqueta--exito">Terminada</span>
                        <?php else: ?>
                            <span class="etiqueta etiqueta--advertencia">Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td data-etiqueta="Reasignar">
                        <?php if ($puedeReasignar): ?>
                            <form
                                class="formulario-en-linea"
                                method="post"
                                action="<?= e($urlReasignar) ?>"
                            >
                                <?= Csrf::campo() ?>
                                <label class="visually-hidden" for="<?= e($idSelector) ?>">
                                    Nuevo trabajador
                                </label>
                                <select
                                    class="form-select form-select-sm"
                                    id="<?= e($idSelector) ?>"
                                    name="id_trabajador"
                                    required
                                >
                                    <option value="">Elegir…</option>
                                    <?php foreach ($otros as $trabajador): ?>
                                        <option value="<?= e($trabajador['id_trabajador']) ?>">
                                            <?= e($trabajador['nombre_completo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-carpin-secundario btn-sm" type="submit">
                                    Reasignar
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="texto-ayuda">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- ===== Asignar actividad ===== -->
<?php if ($estaEntregado): ?>
    <p class="texto-ayuda mt-3 mb-0">
        El proyecto ya fue entregado: no se asignan más actividades.
    </p>
<?php else: ?>
    <h3 class="subtitulo-seccion mt-4">Asignar actividad</h3>

    <div class="row g-3">
        <?php foreach ($rolPorTipo as $tipo => $rol): ?>
            <div class="col-12 col-lg-4">
                <form class="asignar-actividad" method="post" action="<?= e($urlAsignar) ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

                    <h4 class="asignar-actividad__titulo">
                        <?= e(etiquetaActividad($tipo)) ?>
                        <span class="texto-ayuda">(<?= e(etiquetaRol($rol)) ?>)</span>
                    </h4>

                    <?php if ($trabajadoresPorTipo[$tipo] === []): ?>
                        <!-- CU-04, A1 -->
                        <p class="texto-ayuda mb-0">
                            No hay trabajadores activos con el rol <?= e(etiquetaRol($rol)) ?>;
                            pide al Administrador que los dé de alta.
                        </p>
                    <?php else: ?>
                        <fieldset class="mb-2">
                            <legend class="form-label fs-6">Trabajador(es)</legend>
                            <?php foreach ($trabajadoresPorTipo[$tipo] as $trabajador): ?>
                                <?php
                                $idCasilla = 'trabajador' . $tipo . $trabajador['id_trabajador'];
                                ?>
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="<?= e($idCasilla) ?>"
                                        name="trabajadores[]"
                                        value="<?= e($trabajador['id_trabajador']) ?>"
                                    >
                                    <label class="form-check-label" for="<?= e($idCasilla) ?>">
                                        <?= e($trabajador['nombre_completo']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </fieldset>

                        <label class="form-label" for="fecha<?= e($tipo) ?>">
                            Fecha compromiso
                        </label>
                        <input
                            class="form-control mb-3"
                            type="date"
                            id="fecha<?= e($tipo) ?>"
                            name="fecha_compromiso"
                            min="<?= e($hoy) ?>"
                            required
                        >

                        <button class="btn btn-carpin-primario w-100" type="submit">
                            Asignar <?= e(mb_strtolower(etiquetaActividad($tipo))) ?>
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
