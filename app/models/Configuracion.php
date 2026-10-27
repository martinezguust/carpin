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

}
