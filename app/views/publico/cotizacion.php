<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/publico/cotizacion.php
 * Formulario "Solicitar cotización" (RF-11, CU-07): nombre,
 * teléfono, correo opcional, producto opcional, medidas y
 * descripción.
 * Variables: $datos, $errores, $productos.
 * =====================================================
 */

View::$titulo = 'Solicitar cotización';
?>
<section class="seccion-publica">
    <div class="container">
        <div class="tarjeta-formulario-publico">
            <h1 class="titulo-publico">Solicitar cotización</h1>
            <p class="texto-publico">
                Cuéntanos qué mueble necesitas y nos comunicaremos contigo.
            </p>

            <?php require __DIR__ . '/../layouts/_mensajes.php'; ?>

            <form method="post" action="<?= e(View::url('/cotizacion')) ?>" novalidate>
                <?= Csrf::campo() ?>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'nombre') ?>"
                            type="text"
                            id="nombre"
                            name="nombre"
                            maxlength="120"
                            autocomplete="name"
                            value="<?= e($datos['nombre']) ?>"
                            required
                        >
                        <?= mensajeError($errores, 'nombre') ?>
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
                            autocomplete="tel-national"
                            value="<?= e($datos['telefono']) ?>"
                            required
                        >
                        <?= mensajeError($errores, 'telefono') ?>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label" for="correo">Correo (opcional)</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'correo') ?>"
                            type="email"
                            id="correo"
                            name="correo"
                            maxlength="120"
                            autocomplete="email"
                            value="<?= e($datos['correo']) ?>"
                        >
                        <?= mensajeError($errores, 'correo') ?>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="id_producto">Producto (opcional)</label>
                        <select
                            class="form-select <?= claseInvalida($errores, 'id_producto') ?>"
                            id="id_producto"
                            name="id_producto"
                        >
                            <option value="">Ninguno en particular</option>
                            <?php foreach ($productos as $producto): ?>
                                <?php $idProducto = (string) $producto['id_producto']; ?>
                                <option
                                    value="<?= e($idProducto) ?>"
                                    <?= seleccionado($datos['id_producto'] === $idProducto) ?>
                                >
                                    <?= e($producto['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= mensajeError($errores, 'id_producto') ?>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="medidas">Medidas (cm)</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'medidas') ?>"
                            type="text"
                            id="medidas"
                            name="medidas"
                            maxlength="100"
                            placeholder="Ej. 190 × 120 × 55"
                            value="<?= e($datos['medidas']) ?>"
                        >
                        <?= mensajeError($errores, 'medidas') ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="descripcion">Descripción</label>
                        <textarea
                            class="form-control <?= claseInvalida($errores, 'descripcion') ?>"
                            id="descripcion"
                            name="descripcion"
                            rows="4"
                            maxlength="2000"
                            required
                        ><?= e($datos['descripcion']) ?></textarea>
                        <?= mensajeError($errores, 'descripcion') ?>
                    </div>
                </div>

                <button class="btn btn-carpin-accion mt-4" type="submit">Enviar solicitud</button>
            </form>
        </div>
    </div>
</section>
