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

    /**
     * Responde con datos en formato JSON y termina la petición.
     *
     * Recibe: $datos, arreglo a convertir en JSON; $codigo, código HTTP.
     * Devuelve: nada (no regresa).
     */
    protected function json(array $datos, int $codigo = 200): never {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Convierte el id que llegó en la URL a entero; si no es un número
     * entero positivo responde 404.
     *
     * Recibe: $valor, texto del segmento de la ruta, ej. "15".
     * Devuelve: el id como entero.
     */
    protected function idDeRuta(string $valor): int {
        if (!ctype_digit($valor) || (int) $valor < 1) {
            View::error(404);
        }

        return (int) $valor;
    }

    /**
     * Lee un campo de texto del formulario recibido por POST, sin espacios
     * al inicio ni al final.
     *
     * Recibe: $campo, nombre del campo.
     * Devuelve: el texto recibido, o "" si no llegó.
     */
    protected function textoPost(string $campo): string {
        $valor = $_POST[$campo] ?? '';

        if (!is_string($valor)) {
            return '';
        }

        return trim($valor);
    }

}
