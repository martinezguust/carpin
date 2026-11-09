<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/contador/asistencia.php
 * Captura de tarjetas de asistencia de una fecha (CU-09,
 * RF-42): un renglón por trabajador activo con su hora de
 * entrada y de salida. Los renglones ya guardados aparecen
 * precargados (A2) y tienen el botón "Eliminar"
 * (respuesta 12 de la Fase 3).
 * Variables: $fecha, $trabajadores, $existentes, $errores,
 * $capturados.
 * =====================================================
 */

View::$titulo = 'Asistencia';

$hoy = date('Y-m-d');
?>
<h1 class="titulo-pagina">Asistencia</h1>

<!-- ===== Fecha ===== -->
<form class="filtros" method="get" action="<?= e(View::url('/asistencia')) ?>">
    <div class="filtros__campo">
        <label class="form-label" for="fechaConsulta">Fecha de las tarjetas</label>
        <input
            class="form-control"
            type="date"
            id="fechaConsulta"
            name="fecha"
            max="<?= e($hoy) ?>"
            value="<?= e($fecha) ?>"
        >
    </div>

    <div class="filtros__boton">
        <button class="btn btn-carpin-secundario" type="submit">Ver fecha</button>
    </div>
</form>

<p class="subtitulo-pagina">
    Tarjetas del <strong><?= e(fecha($fecha)) ?></strong>. Deja vacío el renglón de quien no
    asistió. Las horas se calculan solas: salida − entrada.
</p>

<?php if ($trabajadores === []): ?>
    <p class="estado-vacio">No hay trabajadores activos.</p>
<?php else: ?>
    <!-- ===== Captura ===== -->
    <form method="post" action="<?= e(View::url('/asistencia')) ?>" novalidate>
        <?= Csrf::campo() ?>
        <input type="hidden" name="fecha" value="<?= e($fecha) ?>">

        <table class="table tabla-carpin tabla-tarjetas">
            <thead>
                <tr>
                    <th scope="col">Trabajador</th>
                    <th scope="col">Entrada</th>
                    <th scope="col">Salida</th>
                    <th scope="col">Horas</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trabajadores as $trabajador): ?>
                    <?php
                    $idTrabajador = (int) $trabajador['id_trabajador'];
                    $tarjeta = $existentes[$idTrabajador] ?? null;
                    $error = $errores[$idTrabajador] ?? null;

                    // Valores: lo recién capturado (si hubo error) o lo ya guardado
                    if (isset($capturados[$idTrabajador])) {
                        $entrada = $capturados[$idTrabajador]['entrada'];
                        $salida = $capturados[$idTrabajador]['salida'];
                    } else {
                        $entrada = '';
                        $salida = '';

                        if ($tarjeta !== null) {
                            $entrada = substr($tarjeta['hora_entrada'], 0, 5);
                            $salida = substr($tarjeta['hora_salida'], 0, 5);
                        }
                    }

                    $claseError = ($error !== null) ? 'is-invalid' : '';
                    ?>
                    <tr class="<?= ($error !== null) ? 'fila-alerta' : '' ?>">
                        <td data-etiqueta="Trabajador">
                            <span>
                                <strong><?= e($trabajador['nombre_completo']) ?></strong>
                                <span class="d-block texto-ayuda">
                                    <?= e(etiquetaRol($trabajador['rol'])) ?>
                                </span>
                            </span>
                        </td>
                        <td data-etiqueta="Entrada">
                            <label class="visually-hidden" for="entrada<?= e($idTrabajador) ?>">
                                Entrada de <?= e($trabajador['nombre_completo']) ?>
                            </label>
                            <input
                                class="form-control campo-hora <?= e($claseError) ?>"
                                type="time"
                                id="entrada<?= e($idTrabajador) ?>"
                                name="entrada[<?= e($idTrabajador) ?>]"
                                value="<?= e($entrada) ?>"
                            >
                        </td>
                        <td data-etiqueta="Salida">
                            <label class="visually-hidden" for="salida<?= e($idTrabajador) ?>">
                                Salida de <?= e($trabajador['nombre_completo']) ?>
                            </label>
                            <input
                                class="form-control campo-hora <?= e($claseError) ?>"
                                type="time"
                                id="salida<?= e($idTrabajador) ?>"
                                name="salida[<?= e($idTrabajador) ?>]"
                                value="<?= e($salida) ?>"
                            >
                        </td>
                        <td data-etiqueta="Horas">
                            <span>
                                <?= ($tarjeta !== null) ? e($tarjeta['horas']) . ' h' : '—' ?>
                                <?php if ($error !== null): ?>
                                    <span class="d-block texto-peligro fw-semibold">
                                        <?= e($error) ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td data-etiqueta="Acciones">
                            <?php if ($tarjeta !== null): ?>
                                <!-- Envía el formulario "eliminar…" de fuera de la tabla -->
                                <button
                                    class="btn btn-link texto-peligro p-0"
                                    type="submit"
                                    form="eliminar<?= e($tarjeta['id_asistencia']) ?>"
                                >Eliminar</button>
                            <?php else: ?>
                                <span class="texto-ayuda">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button class="btn btn-carpin-primario" type="submit">Guardar tarjetas</button>
    </form>

    <!-- ===== Formularios de "Eliminar" (respuesta 12) ===== -->
    <?php foreach ($existentes as $tarjeta): ?>
        <form
            id="eliminar<?= e($tarjeta['id_asistencia']) ?>"
            method="post"
            action="<?= e(View::url('/asistencia/' . $tarjeta['id_asistencia'] . '/eliminar')) ?>"
            data-confirmar="¿Eliminar esta tarjeta de asistencia? No se puede deshacer."
            hidden
        >
            <?= Csrf::campo() ?>
        </form>
    <?php endforeach; ?>
<?php endif; ?>
