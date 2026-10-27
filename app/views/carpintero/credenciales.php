<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/credenciales.php
 * Hoja imprimible con las credenciales del cliente nuevo
 * (RF-16, respuesta 8). La contraseña se muestra una sola
 * vez: al recargar la página ya no aparece.
 * Variables: $proyecto, $credenciales (o null), $negocio.
 * =====================================================
 */

View::$titulo = 'Credenciales del cliente';

$urlProyecto = View::url('/proyectos/' . $proyecto['id_proyecto']);
?>
<div class="alert alert-success no-imprimir" role="alert">
    Proyecto <?= e($proyecto['folio']) ?> registrado y cuenta del cliente creada.
</div>

<?php if ($credenciales === null): ?>
    <section class="tarjeta-carpin formulario-angosto">
        <h1 class="titulo-seccion">Credenciales no disponibles</h1>
        <p>
            Por seguridad, la contraseña solo se muestra una vez, justo después de registrar
            el proyecto. Si el cliente la necesita de nuevo, pide al Administrador que la
            restablezca.
        </p>
        <a class="btn btn-carpin-primario" href="<?= e($urlProyecto) ?>">
            Ir al proyecto
        </a>
    </section>
<?php else: ?>
    <!-- ===== Hoja para imprimir ===== -->
    <section class="hoja-credenciales">
        <div class="hoja-credenciales__encabezado">
            <?php require __DIR__ . '/../layouts/_logo.php'; ?>
            <p class="mb-0"><?= e($negocio['nombre_negocio'] ?? 'Carpintería Carpin') ?></p>
        </div>

        <h1 class="hoja-credenciales__titulo">Acceso para consultar su pedido</h1>

        <dl class="hoja-credenciales__datos">
            <dt>Cliente</dt>
            <dd><?= e($proyecto['cliente_nombre']) ?></dd>

            <dt>Folio</dt>
            <dd><?= e($proyecto['folio']) ?> · <?= e($proyecto['tipo_mueble']) ?></dd>

            <dt>Usuario</dt>
            <dd class="hoja-credenciales__dato-clave"><?= e($credenciales['username']) ?></dd>

            <dt>Contraseña</dt>
            <dd class="hoja-credenciales__dato-clave"><?= e($credenciales['password']) ?></dd>

            <dt>Dirección de acceso</dt>
            <dd><?= e(View::url('/login')) ?></dd>
        </dl>

        <p class="hoja-credenciales__nota">
            Con estos datos puede consultar el avance de su mueble.
        </p>
    </section>

    <div class="d-flex gap-2 flex-wrap mt-4 no-imprimir">
        <button class="btn btn-carpin-primario" type="button" data-imprimir>
            <i class="bi bi-printer" aria-hidden="true"></i>
            Imprimir
        </button>
        <a class="btn btn-carpin-secundario" href="<?= e($urlProyecto) ?>">
            Ir al proyecto
        </a>
    </div>

    <p class="texto-ayuda mt-3 no-imprimir">
        Esta contraseña no se volverá a mostrar. Imprime o anota la hoja antes de salir.
    </p>
<?php endif; ?>
