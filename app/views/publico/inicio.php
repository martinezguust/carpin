<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/publico/inicio.php
 * Página de inicio pública (RF-08, SRS 10.2, mockup 1):
 * portada con degradado de la paleta e ícono (SRS 5.4),
 * productos destacados, "Quiénes somos", servicios y
 * "Visítanos" con el mapa, la dirección, el horario y el
 * teléfono.
 * Variables: $destacados, $negocio.
 * =====================================================
 */

View::$titulo = 'Inicio';

// "Visítanos" solo se muestra si el Administrador capturó algún dato
$camposVisita = ['mapa_url', 'direccion', 'horario', 'telefono'];
$hayDatosVisita = false;

foreach ($camposVisita as $campo) {
    if ((string) ($negocio[$campo] ?? '') !== '') {
        $hayDatosVisita = true;
    }
}
?>
<!-- ===== Portada ===== -->
<section class="portada">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-12 col-lg-7">
                <p class="portada__antetitulo">Carpintería familiar</p>
                <h1 class="portada__titulo">Muebles a la medida, hechos a mano para tu casa</h1>
                <p class="portada__texto">
                    Mesas, sillas, cocinas integrales, roperos y reparaciones.
                    Sigue el avance de tu mueble en línea, desde el armado hasta la entrega.
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-carpin-accion" href="<?= e(View::url('/cotizacion')) ?>">
                        Solicitar cotización
                    </a>
                    <a class="btn btn-carpin-contorno" href="<?= e(View::url('/catalogo')) ?>">
                        Ver catálogo
                    </a>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="portada__imagen" aria-hidden="true">
                    <i class="bi bi-tree-fill"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Productos destacados ===== -->
<?php if ($destacados !== []): ?>
    <section class="seccion-publica">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
                <h2 class="titulo-publico mb-0">Productos destacados</h2>
                <a class="enlace-publico" href="<?= e(View::url('/catalogo')) ?>">
                    Ver todo el catálogo
                </a>
            </div>

            <div class="rejilla-productos">
                <?php foreach ($destacados as $producto): ?>
                    <?php require __DIR__ . '/_producto.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- ===== Quiénes somos y servicios ===== -->
<section class="seccion-publica seccion-publica--beige">
    <div class="container">
        <div class="row g-4">
            <?php if (($negocio['descripcion'] ?? '') !== ''): ?>
                <div class="col-12 col-lg-6">
                    <h2 class="titulo-publico">Quiénes somos</h2>
                    <p class="texto-publico"><?= nl2br(e($negocio['descripcion'])) ?></p>
                    <a class="enlace-publico" href="<?= e(View::url('/contacto')) ?>">
                        Dirección, horario y contacto
                    </a>
                </div>
            <?php endif; ?>

            <div class="col-12 col-lg-6">
                <h2 class="titulo-publico">Servicios</h2>
                <?php require __DIR__ . '/_servicios.php'; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($hayDatosVisita): ?>
    <!-- ===== Visítanos: mapa y datos de contacto ===== -->
    <section class="seccion-publica">
        <div class="container">
            <h2 class="titulo-publico">Visítanos</h2>

            <?php require __DIR__ . '/_mapa.php'; ?>
            <?php require __DIR__ . '/_datos_contacto.php'; ?>
        </div>
    </section>
<?php endif; ?>
