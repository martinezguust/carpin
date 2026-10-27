<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/admin/usuarios.php
 * Lista de usuarios con búsqueda y filtro por rol, y
 * formulario de alta de trabajadores (CU-02, mockup 4).
 * Variables: $usuarios, $busqueda, $filtroRol, $rolesFiltro,
 * $rolesTrabajador, $formasPorRol, $errores, $datos.
 * =====================================================
 */

View::$titulo = 'Usuarios';

$formasPago = ['HORA', 'DIA', 'PROYECTO'];
?>
<div class="row g-4">

    <!-- ===== Lista de usuarios ===== -->
    <div class="col-12 col-xl-8">
        <h1 class="titulo-pagina">Usuarios</h1>

        <form class="filtros" method="get" action="<?= e(View::url('/usuarios')) ?>">
            <div class="filtros__campo filtros__campo--ancho">
                <label class="form-label" for="q">Buscar</label>
                <input
                    class="form-control"
                    type="search"
                    id="q"
                    name="q"
                    value="<?= e($busqueda) ?>"
                    placeholder="Nombre o usuario"
                >
            </div>

            <div class="filtros__campo">
                <label class="form-label" for="filtroRol">Rol</label>
                <select class="form-select" id="filtroRol" name="rol">
                    <option value="">Todos</option>
                    <?php foreach ($rolesFiltro as $rol): ?>
                        <option value="<?= e($rol) ?>" <?= seleccionado($filtroRol === $rol) ?>>
                            <?= e(etiquetaRol($rol)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtros__boton">
                <button class="btn btn-carpin-secundario" type="submit">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    Buscar
                </button>
            </div>
        </form>

        <?php if ($usuarios === []): ?>
            <p class="estado-vacio">No hay usuarios que coincidan con la búsqueda.</p>
        <?php else: ?>
            <table class="table tabla-carpin tabla-tarjetas">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Usuario</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <?php
                        $urlEditar = View::url('/usuarios/' . $usuario['id_usuario'] . '/editar');
                        ?>
                        <tr>
                            <td data-etiqueta="Nombre">
                                <span>
                                    <strong><?= e($usuario['nombre']) ?></strong>
                                    <span class="d-block texto-ayuda">
                                        <?= e($usuario['telefono']) ?>
                                    </span>
                                </span>
                            </td>
                            <td data-etiqueta="Usuario"><?= e($usuario['username']) ?></td>
                            <td data-etiqueta="Rol"><?= e(etiquetaRol($usuario['rol'])) ?></td>
                            <td data-etiqueta="Estado">
                                <?php if ((int) $usuario['activo'] === 1): ?>
                                    <span class="etiqueta etiqueta--exito">Activo</span>
                                <?php else: ?>
                                    <span class="etiqueta etiqueta--error">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td data-etiqueta="Acciones">
                                <a href="<?= e($urlEditar) ?>">
                                    Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- ===== Alta de usuario ===== -->
    <div class="col-12 col-xl-4">
        <section class="tarjeta-carpin">
            <h2 class="titulo-seccion">Nuevo usuario</h2>

            <form method="post" action="<?= e(View::url('/usuarios')) ?>" novalidate>
                <?= Csrf::campo() ?>

                <div class="mb-3">
                    <label class="form-label" for="nombre">Nombre completo</label>
                    <input
                        class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                        type="text"
                        id="nombre"
                        name="nombre"
                        maxlength="120"
                        value="<?= e($datos['nombre']) ?>"
                        required
                    >
                    <?php if (isset($errores['nombre'])): ?>
                        <div class="invalid-feedback"><?= e($errores['nombre']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="telefono">Teléfono</label>
                    <input
                        class="form-control <?= isset($errores['telefono']) ? 'is-invalid' : '' ?>"
                        type="tel"
                        id="telefono"
                        name="telefono"
                        inputmode="numeric"
                        maxlength="10"
                        value="<?= e($datos['telefono']) ?>"
                        required
                    >
                    <?php if (isset($errores['telefono'])): ?>
                        <div class="invalid-feedback"><?= e($errores['telefono']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="username">Usuario</label>
                    <input
                        class="form-control <?= isset($errores['username']) ? 'is-invalid' : '' ?>"
                        type="text"
                        id="username"
                        name="username"
                        maxlength="30"
                        autocapitalize="none"
                        autocomplete="off"
                        value="<?= e($datos['username']) ?>"
                        required
                    >
                    <?php if (isset($errores['username'])): ?>
                        <div class="invalid-feedback"><?= e($errores['username']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Contraseña</label>
                    <input
                        class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>"
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        required
                    >
                    <?php if (isset($errores['password'])): ?>
                        <div class="invalid-feedback"><?= e($errores['password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="rol">Rol</label>
                    <select
                        class="form-select <?= isset($errores['rol']) ? 'is-invalid' : '' ?>"
                        id="rol"
                        name="rol"
                        data-formas-por-rol="<?= e(json_encode($formasPorRol)) ?>"
                        data-selector-forma="#forma_pago"
                        required
                    >
                        <option value="">Elige un rol</option>
                        <?php foreach ($rolesTrabajador as $rol): ?>
                            <option
                                value="<?= e($rol) ?>"
                                <?= seleccionado($datos['rol'] === $rol) ?>
                            >
                                <?= e(etiquetaRol($rol)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['rol'])): ?>
                        <div class="invalid-feedback"><?= e($errores['rol']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-2">
                    <label class="form-label" for="forma_pago">Forma de pago</label>
                    <select
                        class="form-select <?= isset($errores['forma_pago']) ? 'is-invalid' : '' ?>"
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
                    <?php if (isset($errores['forma_pago'])): ?>
                        <div class="invalid-feedback"><?= e($errores['forma_pago']) ?></div>
                    <?php endif; ?>
                </div>

                <p class="texto-ayuda mb-3">
                    La tarifa la captura después el contador.
                    El Administrador no lleva forma de pago.
                </p>

                <button class="btn btn-carpin-primario w-100" type="submit">Guardar usuario</button>
            </form>
        </section>
    </div>

</div>
