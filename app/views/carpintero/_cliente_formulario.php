<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/carpintero/_cliente_formulario.php
 * Sección "Cliente" de los formularios de proyecto y de
 * reparación (RF-15, RF-16, RF-33): cliente existente o
 * cliente nuevo con su usuario y contraseña.
 * Variables (del formulario que la incluye): $datos,
 * $errores, $clientes.
 * =====================================================
 */

$esClienteNuevo = $datos['tipo_cliente'] !== 'existente';
?>
<section class="tarjeta-carpin mb-4">
    <h2 class="titulo-seccion">Cliente</h2>

    <div class="selector-cliente mb-3" role="radiogroup" aria-label="Tipo de cliente">
        <div class="form-check form-check-inline">
            <input
                class="form-check-input"
                type="radio"
                id="clienteNuevo"
                name="tipo_cliente"
                value="nuevo"
                data-muestra="#bloqueClienteNuevo"
                data-oculta="#bloqueClienteExistente"
                <?= $esClienteNuevo ? 'checked' : '' ?>
            >
            <label class="form-check-label" for="clienteNuevo">Cliente nuevo</label>
        </div>

        <div class="form-check form-check-inline">
            <input
                class="form-check-input"
                type="radio"
                id="clienteExistente"
                name="tipo_cliente"
                value="existente"
                data-muestra="#bloqueClienteExistente"
                data-oculta="#bloqueClienteNuevo"
                <?= $esClienteNuevo ? '' : 'checked' ?>
            >
            <label class="form-check-label" for="clienteExistente">Cliente existente</label>
        </div>
    </div>

    <!-- Cliente existente -->
    <div id="bloqueClienteExistente" <?= $esClienteNuevo ? 'hidden' : '' ?>>
        <label class="form-label" for="id_cliente">Cliente</label>
        <select
            class="form-select <?= claseInvalida($errores, 'id_cliente') ?>"
            id="id_cliente"
            name="id_cliente"
        >
            <option value="">Elige un cliente</option>
            <?php foreach ($clientes as $cliente): ?>
                <?php $idCliente = (string) $cliente['id_cliente']; ?>
                <option
                    value="<?= e($idCliente) ?>"
                    <?= seleccionado($datos['id_cliente'] === $idCliente) ?>
                >
                    <?= e($cliente['nombre_completo']) ?> · <?= e($cliente['telefono']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= mensajeError($errores, 'id_cliente') ?>
    </div>

    <!-- Cliente nuevo con su cuenta (RF-16) -->
    <div id="bloqueClienteNuevo" <?= $esClienteNuevo ? '' : 'hidden' ?>>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="cliente_nombre">Nombre completo</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'cliente_nombre') ?>"
                    type="text"
                    id="cliente_nombre"
                    name="cliente_nombre"
                    maxlength="120"
                    value="<?= e($datos['cliente_nombre']) ?>"
                >
                <?= mensajeError($errores, 'cliente_nombre') ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="cliente_telefono">Teléfono (10 dígitos)</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'cliente_telefono') ?>"
                    type="tel"
                    id="cliente_telefono"
                    name="cliente_telefono"
                    inputmode="numeric"
                    maxlength="10"
                    value="<?= e($datos['cliente_telefono']) ?>"
                >
                <?= mensajeError($errores, 'cliente_telefono') ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="cliente_correo">Correo (opcional)</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'cliente_correo') ?>"
                    type="email"
                    id="cliente_correo"
                    name="cliente_correo"
                    maxlength="120"
                    value="<?= e($datos['cliente_correo']) ?>"
                >
                <?= mensajeError($errores, 'cliente_correo') ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="cliente_direccion">Dirección (opcional)</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'cliente_direccion') ?>"
                    type="text"
                    id="cliente_direccion"
                    name="cliente_direccion"
                    maxlength="255"
                    value="<?= e($datos['cliente_direccion']) ?>"
                >
                <?= mensajeError($errores, 'cliente_direccion') ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="cliente_username">Usuario</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'cliente_username') ?>"
                    type="text"
                    id="cliente_username"
                    name="cliente_username"
                    maxlength="30"
                    autocapitalize="none"
                    autocomplete="off"
                    value="<?= e($datos['cliente_username']) ?>"
                >
                <?= mensajeError($errores, 'cliente_username') ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="cliente_password">Contraseña</label>
                <input
                    class="form-control <?= claseInvalida($errores, 'cliente_password') ?>"
                    type="text"
                    id="cliente_password"
                    name="cliente_password"
                    autocomplete="off"
                    autocapitalize="none"
                    value="<?= e($datos['cliente_password']) ?>"
                >
                <?= mensajeError($errores, 'cliente_password') ?>
            </div>
        </div>

        <p class="texto-ayuda mt-2 mb-0">
            Usuario de 4 a 30 caracteres (letras, números, punto y guion bajo).
            Contraseña de al menos 8 caracteres con una letra y un número.
            Al guardar se mostrará una hoja para imprimir y entregar al cliente.
        </p>
    </div>
</section>
