<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/contrasena.php
 * Cambio de la propia contraseña (RF-07), para todos los
 * roles. Variable: $errores, errores por campo.
 * =====================================================
 */

View::$titulo = 'Cambiar contraseña';
?>
<h1 class="titulo-pagina">Cambiar contraseña</h1>

<section class="tarjeta-carpin formulario-angosto">
    <form method="post" action="<?= e(View::url('/cuenta/contrasena')) ?>" novalidate>
        <?= Csrf::campo() ?>

        <?php
        $camposContrasena = [
            'actual'       => ['Contraseña actual', 'current-password'],
            'nueva'        => ['Nueva contraseña', 'new-password'],
            'confirmacion' => ['Confirma la nueva contraseña', 'new-password'],
        ];
        ?>

        <?php foreach ($camposContrasena as $campo => [$etiqueta, $autocompletar]): ?>
            <div class="mb-3">
                <label class="form-label" for="<?= e($campo) ?>"><?= e($etiqueta) ?></label>
                <input
                    class="form-control <?= isset($errores[$campo]) ? 'is-invalid' : '' ?>"
                    type="password"
                    id="<?= e($campo) ?>"
                    name="<?= e($campo) ?>"
                    autocomplete="<?= e($autocompletar) ?>"
                    required
                >
                <?php if (isset($errores[$campo])): ?>
                    <div class="invalid-feedback"><?= e($errores[$campo]) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <p class="texto-ayuda">
            Mínimo 8 caracteres, con al menos una letra y un número.
        </p>

        <button class="btn btn-carpin-primario" type="submit">Guardar contraseña</button>
    </form>
</section>
