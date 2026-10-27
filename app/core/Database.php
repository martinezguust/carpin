<?php
/*
 * =====================================================
 * Carpintería Carpin – app/core/Database.php
 * Conexión única (singleton) a la base de datos carpin_db
 * mediante PDO (SRS 14.1). Todas las consultas del sistema
 * usan sentencias preparadas sobre esta conexión.
 * =====================================================
 */

final class Database {

    /* ===== Conexión compartida ===== */
    private static ?PDO $pdo = null;

    /**
     * Devuelve la conexión PDO; la crea la primera vez que se pide.
     * Configura el modo de error por excepciones, los resultados como
     * arreglos asociativos, las sentencias preparadas reales y la zona
     * horaria de México (-06:00) para NOW() y CURRENT_TIMESTAMP.
     *
     * Recibe: nada.
     * Devuelve: PDO listo para usarse.
     */
    public static function get(): PDO {
        if (self::$pdo === null) {
            $c = (require __DIR__ . '/../../config/config.php')['db'];

            $dsn = "mysql:host={$c['host']};port={$c['port']};"
                . "dbname={$c['name']};charset={$c['charset']}";

            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // Zona horaria de la sesión de MariaDB (SRS 5.4)
            self::$pdo->exec("SET time_zone = '-06:00'");
        }

        return self::$pdo;
    }

}
