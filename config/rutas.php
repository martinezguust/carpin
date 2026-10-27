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

/* ===== Página pública (HU-20, HU-24) ===== */
$router->get('/', 'PublicoController@inicio');
$router->get('/catalogo', 'PublicoController@catalogo');
$router->get('/nosotros', 'PublicoController@nosotros');
$router->get('/contacto', 'PublicoController@contacto');
$router->get('/cotizacion', 'PublicoController@cotizacion');
$router->post('/cotizacion', 'PublicoController@guardarCotizacion');

/* ===== Acceso (HU-18) ===== */
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

/* ===== Administrador: catálogo y datos de la carpintería (HU-21) ===== */
$router->get('/admin/catalogo', 'CatalogoController@index');
$router->get('/admin/catalogo/nuevo', 'CatalogoController@nuevo');
$router->post('/admin/catalogo', 'CatalogoController@guardar');
$router->get('/admin/catalogo/{id}/editar', 'CatalogoController@editar');
$router->post('/admin/catalogo/{id}', 'CatalogoController@actualizar');
$router->post('/admin/catalogo/{id}/visibilidad', 'CatalogoController@cambiarVisibilidad');
$router->post('/admin/catalogo/{id}/eliminar', 'CatalogoController@eliminar');
$router->get('/admin/configuracion', 'ConfigController@editar');
$router->post('/admin/configuracion', 'ConfigController@guardar');
$router->post('/admin/configuracion/logo', 'ConfigController@cambiarLogo');
$router->post('/admin/configuracion/logo/quitar', 'ConfigController@quitarLogo');

/* ===== Contador: panel ===== */
$router->get('/panel', 'PanelController@contador');

/* ===== Carpintero: tablero, proyectos y fases (HU-22, HU-17) ===== */
$router->get('/tablero', 'ProyectoController@tablero');
$router->get('/proyectos', 'ProyectoController@index');
$router->get('/proyectos/nuevo', 'ProyectoController@nuevo');
$router->post('/proyectos', 'ProyectoController@guardar');
$router->get('/proyectos/{id}/credenciales', 'ProyectoController@credenciales');
$router->get('/proyectos/{id}', 'ProyectoController@detalle');
$router->post('/proyectos/{id}/corregir', 'ProyectoController@corregir');

/* ===== Avance de fase: Carpintero, Lijador y Pintor (HU-05, HU-10, HU-17) ===== */
$router->post('/proyectos/{id}/avanzar', 'ProyectoController@avanzar');

/* ===== Carpintero: actividades (HU-25) ===== */
$router->post('/proyectos/{id}/actividades', 'ActividadController@asignar');
$router->post('/actividades/{id}/reasignar', 'ActividadController@reasignar');

/* ===== Carpintero: cotizaciones (HU-24) ===== */
$router->get('/cotizaciones', 'CotizacionController@index');
$router->get('/cotizaciones/{id}', 'CotizacionController@detalle');
$router->post('/cotizaciones/{id}/estado', 'CotizacionController@cambiarEstado');

/* ===== Carpintero: reparaciones (HU-26) ===== */
$router->get('/reparaciones', 'ReparacionController@index');
$router->get('/reparaciones/nueva', 'ReparacionController@nueva');
$router->post('/reparaciones', 'ReparacionController@guardar');
$router->get('/reparaciones/{id}/credenciales', 'ReparacionController@credenciales');
$router->get('/reparaciones/{id}', 'ReparacionController@detalle');
$router->post('/reparaciones/{id}/estado', 'ReparacionController@avanzarEstado');
$router->post('/reparaciones/{id}/fotos', 'ReparacionController@subirFotos');

/* ===== Lijador y Pintor: producción (HU-01, HU-02, HU-06, HU-08, HU-09) ===== */
$router->get('/mis-pendientes', 'ProduccionController@misPendientes');
$router->post('/proyectos/{id}/capas', 'ProduccionController@registrarCapa');
$router->post('/proyectos/{id}/secado', 'ProduccionController@programarSecado');

/* ===== Contador y Carpintero: formas de pago y porcentajes (HU-30) ===== */
$router->get('/trabajadores', 'NominaController@trabajadores');
$router->get('/trabajadores/{id}/editar', 'NominaController@editarTrabajador');
$router->post('/trabajadores/{id}', 'NominaController@guardarTrabajador');
$router->get('/porcentajes', 'NominaController@porcentajes');
$router->post('/porcentajes/roles', 'NominaController@guardarPorcentajesRol');
$router->post('/porcentajes/presupuestos', 'NominaController@guardarPorcentajesPresupuesto');

/* ===== Cliente: portal (HU-23, HU-26) ===== */
$router->get('/mis-pedidos', 'ClientePortalController@index');
$router->get('/mis-pedidos/proyecto/{id}', 'ClientePortalController@proyecto');
$router->get('/mis-pedidos/reparacion/{id}', 'ClientePortalController@reparacion');

/* ===== Notificaciones (HU-29) ===== */
$router->get('/notificaciones', 'NotificacionController@index');
$router->get('/notificaciones/json', 'NotificacionController@campana');
$router->post('/notificaciones/{id}/leer', 'NotificacionController@leer');
