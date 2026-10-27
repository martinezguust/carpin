<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/publico/login.php
 * Pantalla de inicio de sesión (CU-01, mockup 2).
 * Variables: $username, usuario capturado; $error, mensaje
 * de error o null.
 * =====================================================
 */

View::$titulo = 'Iniciar sesión';
?>
<main class="pagina-login">

    <!-- ===== Marca ===== -->
    <div class="pagina-login__marca">
        <?php require __DIR__ . '/../layouts/_logo.php'; ?>
        <p class="pagina-login__subtitulo">Carpintería · Sistema de gestión</p>
    </div>

    <!-- ===== Formulario ===== -->
    <section class="pagina-login__tarjeta">
        <h1 class="pagina-login__titulo">Iniciar sesión</h1>

        <?php require __DIR__ . '/../layouts/_mensajes.php'; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(View::url('/login')) ?>" novalidate>
            <?= Csrf::campo() ?>

            <div class="mb-3">
                <label class="form-label fw-semibold" for="username">Usuario</label>
                <input
                    class="form-control campo-login"
                    type="text"
                    id="username"
                    name="username"
                    value="<?= e($username) ?>"
                    autocomplete="username"
                    autocapitalize="none"
                    required
                    autofocus
                >
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold" for="password">Contraseña</label>
                <input
                    class="form-control campo-login"
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button class="btn btn-carpin-primario w-100" type="submit">Entrar</button>
        </form>

        <p class="pagina-login__ayuda">
            ¿Olvidaste tu contraseña? Pídela en la carpintería.
        </p>
    </section>

    <a class="pagina-login__regreso" href="<?= e(View::url('/')) ?>">
        Volver a la página de inicio
    </a>

</main>
