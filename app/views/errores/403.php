<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/errores/403.php
 * Página de acceso denegado: el usuario intentó entrar a
 * una dirección que no corresponde a su rol (RF-03) o a
 * datos que no le pertenecen (RF-32).
 * =====================================================
 */

$codigo = '403';
$titulo = 'Acceso denegado';
$mensaje = 'No tienes permiso para entrar a esta página.';

require __DIR__ . '/plantilla.php';
