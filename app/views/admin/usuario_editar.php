<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/admin/usuario_editar.php
 * Edición de una cuenta por el Administrador (CU-02, A2 y
 * A3): datos del trabajador o del cliente, restablecer la
 * contraseña y activar / desactivar.
 * Variables: $cuenta, $datos, $errores, $rolesTrabajador,
 * $formasPorRol, $esPropia.
 * =====================================================
 */

View::$titulo = 'Editar usuario';

$idUsuario = (int) $cuenta['id_usuario'];
$esCliente = $cuenta['rol'] === Auth::CLIENTE;
$estaActiva = (int) $cuenta['activo'] === 1;
$formasPago = ['HORA', 'DIA', 'PROYECTO'];

// Direcciones de los tres formularios de la pantalla
$urlDatos = View::url('/usuarios/' . $idUsuario);
$urlContrasena = View::url('/usuarios/' . $idUsuario . '/contrasena');
$urlEstado = View::url('/usuarios/' . $idUsuario . '/estado');
$confirmarDesactivar = '¿Seguro que quieres desactivar la cuenta de ' . $cuenta['nombre'] . '?';
?>
<a class="enlace-regreso" href="<?= e(View::url('/usuarios')) ?>">
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    Usuarios
</a>

<h1 class="titulo-pagina"><?= e($cuenta['nombre']) ?></h1>
<p class="subtitulo-pagina">
    <?= e($cuenta['username']) ?> · <?= e(etiquetaRol($cuenta['rol'])) ?> ·
    <?php if ($estaActiva): ?>
        <span class="etiqueta etiqueta--exito">Activo</span>
    <?php else: ?>
        <span class="etiqueta etiqueta--error">Inactivo</span>
    <?php endif; ?>
    <?php if ($cuenta['ultimo_acceso'] !== null): ?>
        <span class="d-block texto-ayuda">
            Último acceso: <?= e(fechaHora($cuenta['ultimo_acceso'])) ?>
        </span>
    <?php endif; ?>
</p>

<div class="row g-4">

    <!-- ===== Datos ===== -->
    <div class="col-12 col-lg-7">
        <section class="tarjeta-carpin">
            <h2 class="titulo-seccion">Datos</h2>

            <form method="post" action="<?= e($urlDatos) ?>" novalidate>
                <?= Csrf::campo() ?>

                <div class="mb-3">
                    <label class="form-label" for="nombre">Nombre completo</label>
                    <input
                        class="form-control <?= claseInvalida($errores, 'nombre') ?>"
                        type="text"
                        id="nombre"
                        name="nombre"
                        maxlength="120"
                        value="<?= e($datos['nombre']) ?>"
                        required
                    >
                    <?= mensajeError($errores, 'nombre') ?>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="telefono">Teléfono</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'telefono') ?>"
                            type="tel"
                            id="telefono"
                            name="telefono"
                            inputmode="numeric"
                            maxlength="10"
                            value="<?= e($datos['telefono']) ?>"
                            required
                        >
                        <?= mensajeError($errores, 'telefono') ?>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="username">Usuario</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'username') ?>"
                            type="text"
                            id="username"
                            name="username"
                            maxlength="30"
                            autocapitalize="none"
                            autocomplete="off"
                            value="<?= e($datos['username']) ?>"
                            required
                        >
                        <?= mensajeError($errores, 'username') ?>
                    </div>
                </div>

                <?php if ($esCliente): ?>
                    <!-- Datos propios del cliente -->
                    <div class="mb-3">
                        <label class="form-label" for="correo">Correo (opcional)</label>
                        <input
                            class="form-control <?= claseInvalida($errores, 'correo') ?>"
                            type="email"
                            id="correo"
                            name="correo"
                            maxlength="120"
                            value="<?= e($datos['correo']) ?>"
                        >
                        <?= mensajeError($errores, 'correo') ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="direccion">Dirección (opcional)</label>
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
                <?php else: ?>
                    <!-- Rol y forma de pago del trabajador -->
                    <div class="row g-3 mb-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="rol">Rol</label>
                            <select
                                class="form-select <?= claseInvalida($errores, 'rol') ?>"
                                id="rol"
                                name="rol"
                                data-formas-por-rol="<?= e(json_encode($formasPorRol)) ?>"
                                data-selector-forma="#forma_pago"
                                <?= $esPropia ? 'disabled' : '' ?>
                            >
                                <?php foreach ($rolesTrabajador as $rol): ?>
                                    <option
                                        value="<?= e($rol) ?>"
                                        <?= seleccionado($datos['rol'] === $rol) ?>
                                    >
                                        <?= e(etiquetaRol($rol)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($esPropia): ?>
                                <!-- El propio rol no se puede cambiar -->
                                <input type="hidden" name="rol" value="<?= e($datos['rol']) ?>">
                            <?php endif; ?>
                            <?= mensajeError($errores, 'rol') ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="forma_pago">Forma de pago</label>
                            <select
                                class="form-select <?= claseInvalida($errores, 'forma_pago') ?>"
                                id="forma_pago"
                                name="forma_pago"
                            >
                                <option value="">Elige una forma de pago</option>
                                <?php foreach ($formasPago as $forma): ?>
                                    <option
                                        value="<?= e($forma) ?>"
                                        <?= seleccionado($datos['forma_pago'] === $forma) ?>
                                    >
                                        <?= e(etiquetaFormaPago($forma)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= mensajeError($errores, 'forma_pago') ?>
                        </div>
                    </div>

                    <p class="texto-ayuda mb-3">
                        La tarifa o el salario los captura el Contador o el Carpintero.
                    </p>
                <?php endif; ?>

                <button class="btn btn-carpin-primario" type="submit">Guardar cambios</button>
            </form>
        </section>
    </div>

    <div class="col-12 col-lg-5">

        <!-- ===== Restablecer contraseña ===== -->
        <section class="tarjeta-carpin mb-4">
            <h2 class="titulo-seccion">Restablecer contraseña</h2>

            <form method="post" action="<?= e($urlContrasena) ?>" novalidate>
                <?= Csrf::campo() ?>

                <div class="mb-3">
                    <label class="form-label" for="nueva">Nueva contraseña</label>
                    <input
                        class="form-control"
                        type="password"
                        id="nueva"
                        name="nueva"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="mb-2">
                    <label class="form-label" for="confirmacion">Confirma la contraseña</label>
                    <input
                        class="form-control"
                        type="password"
                        id="confirmacion"
                        name="confirmacion"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <p class="texto-ayuda mb-3">
                    Mínimo 8 caracteres, con al menos una letra y un número.
                </p>

                <button class="btn btn-carpin-secundario" type="submit">Restablecer</button>
            </form>
        </section>

        <!-- ===== Activar / desactivar ===== -->
        <section class="tarjeta-carpin">
            <h2 class="titulo-seccion">Estado de la cuenta</h2>

            <?php if ($estaActiva && $esPropia): ?>
                <p class="texto-ayuda mb-0">No puedes desactivar tu propia cuenta.</p>
            <?php elseif ($estaActiva): ?>
                <p class="texto-ayuda">
                    Al desactivarla, la persona ya no podrá iniciar sesión.
                    Su historial se conserva.
                </p>
                <form
                    method="post"
                    action="<?= e($urlEstado) ?>"
                    data-confirmar="<?= e($confirmarDesactivar) ?>"
                >
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="accion" value="desactivar">
                    <button class="btn btn-carpin-peligro" type="submit">Desactivar cuenta</button>
                </form>
            <?php else: ?>
                <p class="texto-ayuda">La cuenta está desactivada y no puede iniciar sesión.</p>
                <form method="post" action="<?= e($urlEstado) ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="accion" value="activar">
                    <button class="btn btn-carpin-accion" type="submit">Activar cuenta</button>
                </form>
            <?php endif; ?>
        </section>

    </div>
</div>
