<?php
/*
 * =====================================================
 * Carpintería Carpin – app/core/Upload.php
 * Recepción segura de archivos subidos (RNF-04 y SRS 14.3,
 * regla 9): valida el tipo MIME real con finfo, la extensión
 * y el tamaño máximo de 5 MB, y guarda el archivo con un
 * nombre aleatorio dentro de public/uploads/{carpeta}.
 * =====================================================
 */

final class Upload {

    /* ===== Carpetas de destino permitidas (SRS 14.2) ===== */
    private const CARPETAS = [
        'catalogo',
        'reparaciones',
        'archivos_prov',
        'danios',
        'logo',
        'comprobantes',
    ];

    /* ===== Tipo MIME real que debe tener cada extensión ===== */
    private const MIME_POR_EXTENSION = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
    ];

    /* ===== Extensiones de uso común ===== */
    public const IMAGENES = ['jpg', 'jpeg', 'png', 'webp'];
    public const IMAGENES_Y_PDF = ['jpg', 'jpeg', 'png', 'pdf'];

    /**
     * Valida y guarda un archivo recibido en $_FILES.
     *
     * Recibe: $archivo, el elemento de $_FILES (ej. $_FILES['imagen']);
     *         $carpeta, carpeta destino dentro de public/uploads;
     *         $permitidas, extensiones aceptadas (ej. Upload::IMAGENES).
     * Devuelve: ruta relativa a public/, ej. "uploads/catalogo/3f2a….jpg".
     *           Lanza RuntimeException con un mensaje en español si el
     *           archivo no es válido.
     */
    public static function guardar(array $archivo, string $carpeta, array $permitidas): string {
        if (!in_array($carpeta, self::CARPETAS, true)) {
            throw new RuntimeException('La carpeta de destino no es válida.');
        }

        // Errores reportados por PHP al recibir el archivo
        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('No se seleccionó ningún archivo.');
        }

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('El archivo supera el tamaño máximo de 5 MB.');
        }

        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '')) {
            throw new RuntimeException('No se pudo recibir el archivo. Intenta de nuevo.');
        }

        // Tamaño máximo configurado (5 MB)
        $app = (require __DIR__ . '/../../config/config.php')['app'];

        if ($archivo['size'] > $app['upload_max']) {
            throw new RuntimeException('El archivo supera el tamaño máximo de 5 MB.');
        }

        // Extensión permitida
        $extension = strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION));

        $extensionValida = in_array($extension, $permitidas, true)
            && isset(self::MIME_POR_EXTENSION[$extension]);

        if (!$extensionValida) {
            $lista = strtoupper(implode(', ', $permitidas));
            throw new RuntimeException("Formato no permitido. Solo se aceptan: {$lista}.");
        }

        // Tipo MIME real del contenido (no el que dice el navegador)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($archivo['tmp_name']);

        if ($mimeReal !== self::MIME_POR_EXTENSION[$extension]) {
            throw new RuntimeException('El contenido del archivo no corresponde a su formato.');
        }

        // Nombre aleatorio y guardado definitivo
        $nombre = bin2hex(random_bytes(16)) . '.' . $extension;
        $destino = __DIR__ . '/../../public/uploads/' . $carpeta . '/' . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
        }

        return 'uploads/' . $carpeta . '/' . $nombre;
    }

}
