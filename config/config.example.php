<?php
/*
 * =====================================================
 * Carpintería Carpin – config/config.example.php
 * Plantilla de configuración del sistema (SRS 14.1).
 * Copiar este archivo como config/config.php y ajustar
 * los datos de conexión. config.php NO se sube a Git.
 * =====================================================
 */

return [

    /* ===== Base de datos (MariaDB de XAMPP) ===== */
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'carpin_db',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    /* ===== Aplicación ===== */
    'app' => [
        'base_url'     => 'http://localhost/carpin/public',
        'timezone'     => 'America/Mexico_City',
        'session_mins' => 60,
        'upload_max'   => 5 * 1024 * 1024,   // 5 MB
    ],

];
