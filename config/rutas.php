<?php
/*
 * =====================================================
 * Carpintería Carpin – config/rutas.php
 * Tabla de rutas del sistema. Cada ruta se registra con:
 *
 *     $router->get('/ruta', 'Controlador@metodo');
 *     $router->post('/ruta/{id}', 'Controlador@metodo');
 *
 * Los segmentos {nombre} se reciben como parámetros del
 * método del controlador, en el mismo orden. Las rutas fijas
 * (ej. /proyectos/nuevo) van antes que las que llevan {id}.
 * El rol de cada pantalla se valida dentro del controlador.
 * =====================================================
 */

/** @var Router $router */

/* ===== Acceso (HU-18) ===== */
$router->get('/', 'AuthController@raiz');
$router->get('/login', 'AuthController@formulario');
$router->post('/login', 'AuthController@iniciar');
$router->post('/logout', 'AuthController@salir');
$router->get('/cuenta/contrasena', 'AuthController@formularioContrasena');
$router->post('/cuenta/contrasena', 'AuthController@cambiarContrasena');

/* ===== Administrador: panel y usuarios (HU-19) ===== */
$router->get('/admin', 'AdminController@panel');
$router->get('/usuarios', 'UsuarioController@index');
$router->post('/usuarios', 'UsuarioController@guardar');
$router->get('/usuarios/{id}/editar', 'UsuarioController@editar');
$router->post('/usuarios/{id}', 'UsuarioController@actualizar');
$router->post('/usuarios/{id}/contrasena', 'UsuarioController@restablecerContrasena');
$router->post('/usuarios/{id}/estado', 'UsuarioController@cambiarEstado');

/* ===== Contador: panel ===== */
$router->get('/panel', 'PanelController@contador');

/* ===== Carpintero: tablero, proyectos y fases (HU-22, HU-17) ===== */
$router->get('/tablero', 'ProyectoController@tablero');
$router->get('/proyectos', 'ProyectoController@index');
$router->get('/proyectos/nuevo', 'ProyectoController@nuevo');
$router->post('/proyectos', 'ProyectoController@guardar');
$router->get('/proyectos/{id}/credenciales', 'ProyectoController@credenciales');
$router->get('/proyectos/{id}', 'ProyectoController@detalle');
$router->post('/proyectos/{id}/avanzar', 'ProyectoController@avanzar');
$router->post('/proyectos/{id}/corregir', 'ProyectoController@corregir');

/* ===== Carpintero: actividades (HU-25) ===== */
$router->post('/proyectos/{id}/actividades', 'ActividadController@asignar');
$router->post('/actividades/{id}/reasignar', 'ActividadController@reasignar');

/* ===== Lijador y Pintor: mis pendientes (HU-01) ===== */
$router->get('/mis-pendientes', 'ProduccionController@misPendientes');

/* ===== Cliente: portal (HU-23) ===== */
$router->get('/mis-pedidos', 'ClientePortalController@index');
$router->get('/mis-pedidos/proyecto/{id}', 'ClientePortalController@proyecto');

/* ===== Notificaciones (HU-29) ===== */
$router->get('/notificaciones', 'NotificacionController@index');
$router->get('/notificaciones/json', 'NotificacionController@campana');
$router->post('/notificaciones/{id}/leer', 'NotificacionController@leer');
