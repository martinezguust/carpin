<?php
/*
 * =====================================================
 * Carpintería Carpin – app/core/Model.php
 * Clase base de todos los modelos. Cada modelo recibe la
 * conexión PDO compartida y la usa siempre con sentencias
 * preparadas (RNF-02).
 * =====================================================
 */

abstract class Model {

    /* ===== Conexión a la base de datos ===== */
    protected PDO $db;

    /**
     * Toma la conexión PDO compartida del sistema.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function __construct() {
        $this->db = Database::get();
    }

}
