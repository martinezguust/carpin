<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/errores/500.php
 * Error interno: ocurrió un problema no previsto en el
 * servidor. El detalle técnico queda en el log de Apache.
 * =====================================================
 */

$codigo = '500';
$titulo = 'Ocurrió un error';
$mensaje = 'Tuvimos un problema al procesar tu solicitud. Intenta de nuevo en unos minutos.';

require __DIR__ . '/plantilla.php';
