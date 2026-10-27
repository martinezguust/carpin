<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/publico/nosotros.php
 * "Nosotros" (cambio pedido por el Product Owner el
 * 27/10/2026): quiénes somos (texto de la configuración),
 * misión, visión, valores y servicios. Misión, visión y
 * valores son texto fijo: no se guardan en la base de datos.
 * Variable: $negocio, datos de la carpintería.
 * =====================================================
 */

View::$titulo = 'Nosotros';

$descripcion = (string) ($negocio['descripcion'] ?? '');

// Valores de la carpintería (texto fijo)
$valores = [
    'Calidad',
    'Honestidad',
    'Responsabilidad',
    'Puntualidad',
    'Trabajo en familia',
];
?>
<!-- ===== Quiénes somos ===== -->
<section class="seccion-publica">
    <div class="container">
        <h1 class="titulo-publico">Quiénes somos</h1>

        <?php if ($descripcion !== ''): ?>
            <p class="texto-publico mb-0"><?= nl2br(e($descripcion)) ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- ===== Misión, visión y valores ===== -->
<section class="seccion-publica seccion-publica--beige">
    <div class="container">
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <article class="tarjeta-identidad">
                    <i class="bi bi-bullseye tarjeta-identidad__icono" aria-hidden="true"></i>
                    <h2 class="tarjeta-identidad__titulo">Misión</h2>
                    <p class="mb-0">
                        Fabricar y reparar muebles de madera a la medida, con trabajo artesanal de
                        calidad, materiales duraderos y un trato cercano con cada cliente.
                    </p>
                </article>
            </div>

            <div class="col-12 col-lg-4">
                <article class="tarjeta-identidad">
                    <i class="bi bi-eye tarjeta-identidad__icono" aria-hidden="true"></i>
                    <h2 class="tarjeta-identidad__titulo">Visión</h2>
                    <p class="mb-0">
                        Ser la carpintería familiar de confianza en Xalapa y la región, reconocida
                        por la calidad de sus muebles y por mantener informados a sus clientes
                        durante todo el proceso.
                    </p>
                </article>
            </div>

            <div class="col-12 col-lg-4">
                <article class="tarjeta-identidad">
                    <i class="bi bi-heart tarjeta-identidad__icono" aria-hidden="true"></i>
                    <h2 class="tarjeta-identidad__titulo">Valores</h2>
                    <ul class="lista-valores">
                        <?php foreach ($valores as $valor): ?>
                            <li>
                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                <?= e($valor) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            </div>
        </div>
    </div>
</section>

<!-- ===== Servicios ===== -->
<section class="seccion-publica">
    <div class="container">
        <h2 class="titulo-publico">Servicios</h2>

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <?php require __DIR__ . '/_servicios.php'; ?>
            </div>
        </div>
    </div>
</section>
