<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/danio_reportar.php
 * "Reportar daño" (HU-03, RF-40) para el Carpintero, el
 * Lijador y el Pintor: formulario con foto opcional y lista
 * de los reportes propios con su estado (respuesta 10).
 * Variables: $datos, $errores, $propios, $puedeVerTodos.
 * =====================================================
 */

View::$titulo = 'Reportar daño';

$reportes = $propios;
$puedeCambiarEstado = false;
?>
<?php if ($puedeVerTodos): ?>
    <a class="enlace-regreso" href="<?= e(View::url('/danios')) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        Reportes de daño
    </a>
<?php endif; ?>

<h1 class="titulo-pagina">Reportar material o herramienta dañada</h1>

<section class="tarjeta-carpin formulario-mediano mb-5">
    <form
        method="post"
        action="<?= e(View::url('/danios')) ?>"
        enctype="multipart/form-data"
        novalidate
    >
        <?= Csrf::campo() ?>

        <div class="row g-3">
            <div class="col-12 col-md-7">
                <label class="form-label" for="elemento">¿Qué se dañó?</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'elemento') ?>"
                    type="text"
                    id="elemento"
                    name="elemento"
                    maxlength="100"
                    placeholder="Ej. lijadora orbital"
                    value="<?= e($datos['elemento']) ?>"
                    required
                >
                <?= mensajeError($errores, 'elemento') ?>
            </div>

            <div class="col-12 col-md-5">
                <label class="form-label" for="tipo">Tipo</label>
                <select
                    class="form-select <?= claseInvalida($errores, 'tipo') ?>"
                    id="tipo"
                    name="tipo"
                    required
                >
                    <option value="">Elige el tipo</option>
                    <?php foreach (ReporteDanio::TIPOS as $clave => $nombre): ?>
                        <option
                            value="<?= e($clave) ?>"
                            <?= seleccionado($datos['tipo'] === $clave) ?>
                        >
                            <?= e($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= mensajeError($errores, 'tipo') ?>
            </div>

            <div class="col-12">
                <label class="form-label" for="descripcion">Descripción del daño</label>
                <textarea
                    class="form-control <?= claseInvalida($errores, 'descripcion') ?>"
                    id="descripcion"
                    name="descripcion"
                    rows="3"
                    maxlength="2000"
                    required
                ><?= e($datos['descripcion']) ?></textarea>
                <?= mensajeError($errores, 'descripcion') ?>
            </div>

            <div class="col-12 col-md-5">
                <label class="form-label" for="accion">Acción sugerida</label>
                <select
                    class="form-select <?= claseInvalida($errores, 'accion') ?>"
                    id="accion"
                    name="accion"
                    required
                >
                    <option value="">Elige la acción</option>
                    <?php foreach (ReporteDanio::ACCIONES as $clave => $nombre): ?>
                        <option
                            value="<?= e($clave) ?>"
                            <?= seleccionado($datos['accion'] === $clave) ?>
                        >
                            <?= e($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= mensajeError($errores, 'accion') ?>
            </div>

            <div class="col-12 col-md-7">
                <label class="form-label" for="foto">Foto (opcional)</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'foto') ?>"
                    type="file"
                    id="foto"
                    name="foto"
                    accept="image/jpeg,image/png,image/webp"
                >
                <?= mensajeError($errores, 'foto') ?>
                <p class="texto-ayuda mt-1 mb-0">JPG, PNG o WEBP de máximo 5 MB.</p>
            </div>
        </div>

        <button class="btn btn-carpin-peligro mt-4" type="submit">Enviar reporte</button>
    </form>
</section>

<!-- ===== Mis reportes ===== -->
<h2 class="titulo-seccion">Mis reportes</h2>

<?php if ($reportes === []): ?>
    <p class="estado-vacio">Todavía no has enviado reportes.</p>
<?php else: ?>
    <?php require __DIR__ . '/_lista_danios.php'; ?>
<?php endif; ?>
