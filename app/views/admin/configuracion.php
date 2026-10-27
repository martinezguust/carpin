<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/admin/configuracion.php
 * Datos de la carpintería (RF-13): nombre, "Quiénes somos",
 * dirección, horario, teléfono, WhatsApp, mapa y logo.
 * Variables: $datos, $errores, $negocio.
 * =====================================================
 */

View::$titulo = 'Datos de la carpintería';

$tieneLogo = ($negocio['logo_ruta'] ?? null) !== null;
?>
<h1 class="titulo-pagina">Datos de la carpintería</h1>

<div class="row g-4">

    <!-- ===== Datos generales ===== -->
    <div class="col-12 col-xl-8">
        <section class="tarjeta-carpin">
            <h2 class="titulo-seccion">Datos generales</h2>

            <form method="post" action="<?= e(View::url('/admin/configuracion')) ?>" novalidate>
                <?= Csrf::campo() ?>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="nombre_negocio">
                            Nombre de la carpintería
                        </label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'nombre_negocio') ?>"
                            type="text"
                            id="nombre_negocio"
                            name="nombre_negocio"
                            maxlength="100"
                            value="<?= e($datos['nombre_negocio']) ?>"
                            required
                        >
                        <?= mensajeError($errores, 'nombre_negocio') ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="descripcion">Quiénes somos</label>
                        <textarea
                            class="form-control <?= claseInvalida($errores, 'descripcion') ?>"
                            id="descripcion"
                            name="descripcion"
                            rows="4"
                            maxlength="2000"
                        ><?= e($datos['descripcion']) ?></textarea>
                        <?= mensajeError($errores, 'descripcion') ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="direccion">Dirección</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'direccion') ?>"
                            type="text"
                            id="direccion"
                            name="direccion"
                            maxlength="255"
                            value="<?= e($datos['direccion']) ?>"
                        >
                        <?= mensajeError($errores, 'direccion') ?>
                    </div>

                    <div class="col-12 col-md-8">
                        <label class="form-label" for="horario">Horario</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'horario') ?>"
                            type="text"
                            id="horario"
                            name="horario"
                            maxlength="150"
                            value="<?= e($datos['horario']) ?>"
                        >
                        <?= mensajeError($errores, 'horario') ?>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label" for="telefono">Teléfono (10 dígitos)</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'telefono') ?>"
                            type="tel"
                            id="telefono"
                            name="telefono"
                            inputmode="numeric"
                            maxlength="10"
                            value="<?= e($datos['telefono']) ?>"
                        >
                        <?= mensajeError($errores, 'telefono') ?>
                    </div>

                    <div class="col-12 col-md-5">
                        <label class="form-label" for="whatsapp">
                            WhatsApp (formato internacional)
                        </label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'whatsapp') ?>"
                            type="tel"
                            id="whatsapp"
                            name="whatsapp"
                            inputmode="numeric"
                            maxlength="15"
                            placeholder="Ej. 5212281234567"
                            value="<?= e($datos['whatsapp']) ?>"
                        >
                        <?= mensajeError($errores, 'whatsapp') ?>
                    </div>

                    <div class="col-12 col-md-7">
                        <label class="form-label" for="mensaje_whatsapp">
                            Mensaje predefinido de WhatsApp
                        </label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'mensaje_whatsapp') ?>"
                            type="text"
                            id="mensaje_whatsapp"
                            name="mensaje_whatsapp"
                            maxlength="255"
                            value="<?= e($datos['mensaje_whatsapp']) ?>"
                        >
                        <?= mensajeError($errores, 'mensaje_whatsapp') ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="mapa_url">
                            URL del mapa (Google Maps → Compartir → Insertar un mapa)
                        </label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'mapa_url') ?>"
                            type="url"
                            id="mapa_url"
                            name="mapa_url"
                            placeholder="https://www.google.com/maps/embed?pb=…"
                            value="<?= e($datos['mapa_url']) ?>"
                        >
                        <?= mensajeError($errores, 'mapa_url') ?>
                    </div>
                </div>

                <p class="texto-ayuda mt-3">
                    Los datos que dejes vacíos no se muestran en la página pública.
                </p>

                <button class="btn btn-carpin-primario" type="submit">Guardar datos</button>
            </form>
        </section>
    </div>

    <!-- ===== Logo ===== -->
    <div class="col-12 col-xl-4">
        <section class="tarjeta-carpin">
            <h2 class="titulo-seccion">Logo</h2>

            <div class="vista-logo mb-3">
                <?php require __DIR__ . '/../layouts/_logo.php'; ?>
            </div>

            <form
                method="post"
                action="<?= e(View::url('/admin/configuracion/logo')) ?>"
                enctype="multipart/form-data"
            >
                <?= Csrf::campo() ?>

                <label class="form-label" for="logo">Nuevo logo</label>
                <input
                    class="form-control mb-1"
                    type="file"
                    id="logo"
                    name="logo"
                    accept="image/jpeg,image/png,image/webp"
                    required
                >
                <p class="texto-ayuda">
                    JPG, PNG o WEBP de máximo 5 MB; de preferencia cuadrado (512 × 512 px).
                </p>

                <button class="btn btn-carpin-primario w-100" type="submit">Cambiar logo</button>
            </form>

            <?php if ($tieneLogo): ?>
                <form
                    class="mt-2"
                    method="post"
                    action="<?= e(View::url('/admin/configuracion/logo/quitar')) ?>"
                    data-confirmar="¿Quitar el logo y volver al logo provisional?"
                >
                    <?= Csrf::campo() ?>
                    <button class="btn btn-carpin-secundario w-100" type="submit">
                        Quitar logo
                    </button>
                </form>
            <?php endif; ?>
        </section>
    </div>

</div>
