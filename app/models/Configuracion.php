<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Configuracion.php
 * Acceso a la tabla configuracion: fila única (id = 1) con
 * los datos de la carpintería, el logo y los porcentajes.
 * =====================================================
 */

class Configuracion extends Model {

    /* ===== Fila leída durante la petición actual ===== */
    private static ?array $cache = null;

    /**
     * Devuelve los datos de la carpintería (se leen una vez por petición).
     *
     * Recibe: nada.
     * Devuelve: arreglo con las columnas de la tabla configuracion.
     */
    public function obtener(): array {
        if (self::$cache === null) {
            $fila = $this->db->query('SELECT * FROM configuracion WHERE id_config = 1')->fetch();
            self::$cache = $fila ?: [];
        }

        return self::$cache;
    }

    /**
     * Arma el enlace de WhatsApp con el mensaje predefinido (RF-10):
     * https://wa.me/{número}?text={mensaje}.
     *
     * Recibe: $negocio, fila de la tabla configuracion.
     * Devuelve: el enlace, o null si no se ha capturado el número.
     */
    public static function enlaceWhatsapp(array $negocio): ?string {
        $numero = (string) ($negocio['whatsapp'] ?? '');

        if ($numero === '') {
            return null;
        }

        $enlace = 'https://wa.me/' . rawurlencode($numero);
        $mensaje = (string) ($negocio['mensaje_whatsapp'] ?? '');

        if ($mensaje !== '') {
            $enlace .= '?text=' . rawurlencode($mensaje);
        }

        return $enlace;
    }

    /**
     * Guarda los datos generales de la carpintería (RF-13).
     *
     * Recibe: $datos, con nombre_negocio, descripcion, direccion, horario,
     *         telefono, whatsapp, mensaje_whatsapp y mapa_url (los vacíos
     *         como null).
     * Devuelve: nada.
     */
    public function actualizarDatos(array $datos): void {
        $consulta = $this->db->prepare(
            'UPDATE configuracion SET nombre_negocio = ?, descripcion = ?, direccion = ?, '
            . 'horario = ?, telefono = ?, whatsapp = ?, mensaje_whatsapp = ?, mapa_url = ?, '
            . 'actualizado_en = NOW() WHERE id_config = 1'
        );
        $consulta->execute([
            $datos['nombre_negocio'],
            $datos['descripcion'],
            $datos['direccion'],
            $datos['horario'],
            $datos['telefono'],
            $datos['whatsapp'],
            $datos['mensaje_whatsapp'],
            $datos['mapa_url'],
        ]);

        self::$cache = null;
    }

    /**
     * Cambia o quita el logo de la carpintería.
     *
     * Recibe: $ruta, ruta relativa a public/, o null para volver al
     *         logo provisional.
     * Devuelve: nada.
     */
    public function actualizarLogo(?string $ruta): void {
        $consulta = $this->db->prepare(
            'UPDATE configuracion SET logo_ruta = ?, actualizado_en = NOW() WHERE id_config = 1'
        );
        $consulta->execute([$ruta]);

        self::$cache = null;
    }

    /**
     * Guarda los porcentajes de mano de obra y ganancia de los
     * presupuestos (RF-55).
     *
     * Recibe: $manoObra y $ganancia, textos con 2 decimales.
     * Devuelve: nada.
     */
    public function actualizarPorcentajes(string $manoObra, string $ganancia): void {
        $consulta = $this->db->prepare(
            'UPDATE configuracion SET pct_mano_obra = ?, pct_ganancia = ?, '
            . 'actualizado_en = NOW() WHERE id_config = 1'
        );
        $consulta->execute([$manoObra, $ganancia]);

        self::$cache = null;
    }

}
