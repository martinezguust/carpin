<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/layouts/_galeria_reparacion.php
 * Fotos de una reparación separadas en "Antes" y "Después"
 * (RF-34, RF-36). La usan el detalle del Carpintero y el
 * portal del cliente. Cada foto abre su tamaño completo.
 * Variable: $imagenes, fotos de la reparación.
 * =====================================================
 */

$gruposFotos = [
    'ANTES'   => 'Antes',
    'DESPUES' => 'Después',
];
?>
<?php foreach ($gruposFotos as $tipoFoto => $tituloFotos): ?>
    <?php
    $fotosDelGrupo = array_filter(
        $imagenes,
        fn ($imagen) => $imagen['tipo'] === $tipoFoto
    );
    ?>

    <?php if ($fotosDelGrupo !== []): ?>
        <h3 class="subtitulo-seccion"><?= e($tituloFotos) ?></h3>
        <div class="galeria-fotos mb-3">
            <?php foreach ($fotosDelGrupo as $imagen): ?>
                <a
                    class="galeria-fotos__elemento"
                    href="<?= e(View::url($imagen['ruta'])) ?>"
                    target="_blank"
                    rel="noopener"
                >
                    <img
                        src="<?= e(View::url($imagen['ruta'])) ?>"
                        alt="Foto de <?= e(mb_strtolower($tituloFotos)) ?> de la reparación"
                        loading="lazy"
                    >
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
