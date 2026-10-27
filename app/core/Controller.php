<?php
/*
 * =====================================================
 * Carpintería Carpin – app/core/Controller.php
 * Clase base de todos los controladores. Ofrece las
 * acciones comunes: mostrar una vista y redirigir.
 * =====================================================
 */

abstract class Controller {

    /**
     * Muestra una vista, opcionalmente dentro de un layout.
     *
     * Recibe: $vista, ruta dentro de app/views sin ".php", ej. "admin/usuarios";
     *         $datos, variables que recibe la vista;
     *         $layout, layout de app/views/layouts o null para no usar ninguno.
     * Devuelve: nada (imprime el HTML).
     */
    protected function vista(string $vista, array $datos = [], ?string $layout = null): void {
        View::mostrar($vista, $datos, $layout);
    }

    /**
     * Envía al navegador a otra ruta del sistema y termina la petición.
     *
     * Recibe: $ruta, ruta interna, ej. "/proyectos".
     * Devuelve: nada (no regresa).
     */
    protected function redirigir(string $ruta): never {
        header('Location: ' . View::url($ruta));
        exit;
    }

}
