<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/pintor/pendientes.php
 * "Mis pendientes" del Pintor (HU-06, HU-08, HU-09, HU-10,
 * mockup 7): ficha de color, capas aplicadas, secado con
 * cuenta regresiva, formularios de capa y secado (solo en
 * "Pintado") y el botón "Marcar como terminada".
 * Variables: $trabajador, $tarjetas.
 * =====================================================
 */

View::$titulo = 'Mis pendientes';

$cantidad = count($tarjetas);
$ahora = date('Y-m-d\TH:i');
?>
<h1 class="titulo-pagina mb-1">Mis pendientes</h1>
<p class="subtitulo-pagina">
    <?= e($trabajador['nombre_completo']) ?> ·
    <?= e($cantidad) ?> <?= ($cantidad === 1) ? 'actividad de pintura' : 'actividades de pintura' ?>
</p>

<?php if ($tarjetas === []): ?>
    <p class="estado-vacio">
        <i class="bi bi-check2-circle" aria-hidden="true"></i>
        No tienes actividades pendientes.
    </p>
<?php else: ?>
    <div class="lista-pendientes">
        <?php foreach ($tarjetas as $tarjeta): ?>
            <?php
            $actividad = $tarjeta['actividad'];
            $idProyecto = (int) $actividad['id_proyecto'];
            $teToca = $tarjeta['siguiente'] !== null;
            $ficha = $tarjeta['ficha'];
            $secado = $tarjeta['secado'];

            // Color y acabado en la primera línea de la ficha (mockup 7)
            $colorAcabado = implode(' · ', array_filter([
                $actividad['color'],
                $actividad['acabado'],
            ]));
            $productoNotas = implode(' · ', array_filter([
                $ficha['producto'],
                $ficha['notas'],
            ]));

            // Fin del secado en formato ISO para la cuenta regresiva
            $finSecadoIso = ($secado !== null) ? date('c', strtotime($secado['fin_estimado'])) : '';
            ?>
            <article class="tarjeta-pendiente <?= $teToca ? 'tarjeta-pendiente--te-toca' : '' ?>">
                <div class="tarjeta-pendiente__encabezado">
                    <span class="tarjeta-pendiente__folio"><?= e($actividad['folio']) ?></span>
                    <?php if ($teToca): ?>
                        <span class="etiqueta etiqueta--acento">Te toca</span>
                    <?php else: ?>
                        <span class="etiqueta"><?= e($actividad['fase_nombre']) ?></span>
                    <?php endif; ?>
                </div>

                <h2 class="tarjeta-pendiente__mueble"><?= e($actividad['tipo_mueble']) ?></h2>

                <!-- Ficha de color (RF-25) -->
                <div class="dato-destacado">
                    <p class="dato-destacado__titulo">Ficha de color</p>
                    <p class="ficha-color__principal">
                        <?= e(($colorAcabado !== '') ? $colorAcabado : 'Sin color indicado') ?>
                    </p>
                    <?php if ($productoNotas !== ''): ?>
                        <p class="ficha-color__notas"><?= e($productoNotas) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Capas y secado (RF-26, RF-27) -->
                <div class="cuadros-produccion">
                    <div class="cuadro-produccion">
                        <p class="cuadro-produccion__titulo">Capas</p>
                        <p class="cuadro-produccion__valor"><?= e($tarjeta['capas']) ?></p>
                    </div>
                    <div class="cuadro-produccion">
                        <p class="cuadro-produccion__titulo">Secado</p>
                        <?php if ($secado !== null): ?>
                            <p
                                class="cuadro-produccion__valor"
                                data-fin-secado="<?= e($finSecadoIso) ?>"
                                title="Termina el <?= e(fechaHora($secado['fin_estimado'])) ?>"
                            ><?= e(fechaHora($secado['fin_estimado'])) ?></p>
                        <?php else: ?>
                            <p class="cuadro-produccion__valor cuadro-produccion__valor--vacio">
                                Sin programar
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="tarjeta-pendiente__detalle">
                    Madera: <?= e($actividad['madera']) ?> ·
                    Entrega: <?= e(fecha($actividad['fecha_entrega'])) ?>
                </p>
                <p class="tarjeta-pendiente__detalle mb-0">
                    Fase actual: <?= e($actividad['fase_nombre']) ?> ·
                    Compromiso: <?= e(fecha($actividad['fecha_compromiso'])) ?>
                </p>

                <?php if ($tarjeta['enPintado']): ?>
                    <!-- Registrar capa y programar secado: solo en "Pintado" -->
                    <div class="botones-produccion mt-3">
                        <button
                            class="btn btn-carpin-secundario"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#capa<?= e($idProyecto) ?>"
                            aria-expanded="false"
                            aria-controls="capa<?= e($idProyecto) ?>"
                        >Registrar capa</button>
                        <button
                            class="btn btn-carpin-secundario"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#secado<?= e($idProyecto) ?>"
                            aria-expanded="false"
                            aria-controls="secado<?= e($idProyecto) ?>"
                        >Programar secado</button>
                    </div>

                    <form
                        class="collapse formulario-produccion"
                        id="capa<?= e($idProyecto) ?>"
                        method="post"
                        action="<?= e(View::url('/proyectos/' . $idProyecto . '/capas')) ?>"
                    >
                        <?= Csrf::campo() ?>
                        <label class="form-label" for="producto<?= e($idProyecto) ?>">
                            Producto usado (capa <?= e($tarjeta['capas'] + 1) ?>)
                        </label>
                        <input
                            class="form-control mb-2"
                            type="text"
                            id="producto<?= e($idProyecto) ?>"
                            name="producto"
                            maxlength="80"
                            value="<?= e($ficha['producto']) ?>"
                            required
                        >
                        <label class="form-label" for="observaciones<?= e($idProyecto) ?>">
                            Observaciones (opcional)
                        </label>
                        <input
                            class="form-control mb-2"
                            type="text"
                            id="observaciones<?= e($idProyecto) ?>"
                            name="observaciones"
                            maxlength="255"
                        >
                        <button class="btn btn-carpin-primario w-100" type="submit">
                            Guardar capa
                        </button>
                    </form>

                    <form
                        class="collapse formulario-produccion"
                        id="secado<?= e($idProyecto) ?>"
                        method="post"
                        action="<?= e(View::url('/proyectos/' . $idProyecto . '/secado')) ?>"
                    >
                        <?= Csrf::campo() ?>
                        <label class="form-label" for="inicio<?= e($idProyecto) ?>">
                            Inicio del secado
                        </label>
                        <input
                            class="form-control mb-2"
                            type="datetime-local"
                            id="inicio<?= e($idProyecto) ?>"
                            name="inicio"
                            value="<?= e($ahora) ?>"
                            required
                        >
                        <label class="form-label" for="horas<?= e($idProyecto) ?>">
                            Horas de secado
                        </label>
                        <input
                            class="form-control mb-2"
                            type="number"
                            id="horas<?= e($idProyecto) ?>"
                            name="horas"
                            min="0.01"
                            max="999.99"
                            step="0.01"
                            inputmode="decimal"
                            required
                        >
                        <button class="btn btn-carpin-primario w-100" type="submit">
                            Guardar secado
                        </button>
                    </form>
                <?php endif; ?>

                <?php require __DIR__ . '/../layouts/_avance_tarjeta.php'; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
