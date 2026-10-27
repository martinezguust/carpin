<?php
/*
 * =====================================================
 * Carpintería Carpin – app/core/Flash.php
 * Mensajes de una sola vez (éxito, error o aviso) que se
 * guardan en la sesión y se muestran en la siguiente
 * pantalla, por ejemplo después de guardar un formulario.
 * =====================================================
 */

final class Flash {

    /* ===== Tipos de mensaje permitidos ===== */
    private const TIPOS = ['exito', 'error', 'aviso'];

    /**
     * Agrega un mensaje para mostrarlo en la siguiente pantalla.
     *
     * Recibe: $tipo, "exito", "error" o "aviso"; $mensaje, texto a mostrar.
     * Devuelve: nada.
     */
    public static function agregar(string $tipo, string $mensaje): void {
        if (!in_array($tipo, self::TIPOS, true)) {
            $tipo = 'aviso';
        }

        $_SESSION['flash'][] = [
            'tipo'    => $tipo,
            'mensaje' => $mensaje,
        ];
    }

    /**
     * Devuelve los mensajes pendientes y los borra de la sesión.
     *
     * Recibe: nada.
     * Devuelve: lista de mensajes con 'tipo' y 'mensaje'.
     */
    public static function obtener(): array {
        $mensajes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        return $mensajes;
    }

}
