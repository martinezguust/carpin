<?php
/*
 * =====================================================
 * Carpintería Carpin – database/verificar.php
 * Revisa que la instalación esté completa:
 *   1. Extensiones de PHP y Dompdf.
 *   2. Conexión PDO y zona horaria.
 *   3. Base carpin_db con sus 31 tablas.
 *   4. Los 6 usuarios de prueba con su rol y contraseña.
 *   5. Los datos iniciales y de prueba.
 * Imprime [OK] o [ERROR] por cada revisión.
 *
 * Uso (desde la carpeta carpin/):
 *     php database/verificar.php
 * =====================================================
 */

declare(strict_types=1);

/* ===== Solo se permite ejecutar desde la consola ===== */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta desde la consola.');
}

/* ===== Preparación ===== */

$configuracion = require __DIR__ . '/../config/config.php';
date_default_timezone_set($configuracion['app']['timezone']);

$errores = 0;

/**
 * Imprime el resultado de una revisión y cuenta los errores.
 *
 * Recibe: $correcto, si la revisión pasó; $descripcion, qué se revisó.
 * Devuelve: nada.
 */
function revisar(bool $correcto, string $descripcion): void {
    global $errores;

    if ($correcto) {
        echo '  [OK]    ' . $descripcion . PHP_EOL;
    } else {
        echo '  [ERROR] ' . $descripcion . PHP_EOL;
        $errores++;
    }
}

/**
 * Imprime el título de una sección del reporte.
 *
 * Recibe: $titulo, texto de la sección.
 * Devuelve: nada.
 */
function seccion(string $titulo): void {
    echo PHP_EOL . '== ' . $titulo . ' ==' . PHP_EOL;
}

/**
 * Cuenta los registros de una tabla.
 *
 * Recibe: $db, conexión PDO; $tabla, nombre de una tabla conocida.
 * Devuelve: número de registros.
 */
function contar(PDO $db, string $tabla): int {
    // $tabla viene de la lista fija de este script, nunca del usuario
    return (int) $db->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn();
}

echo 'Verificación de la instalación de Carpintería Carpin' . PHP_EOL;

/* ===== 1. PHP y Dompdf ===== */

seccion('1. PHP y Dompdf');

revisar(version_compare(PHP_VERSION, '8.3.0', '>='), 'PHP ' . PHP_VERSION . ' (se requiere 8.3)');

foreach (['pdo_mysql', 'mbstring', 'gd', 'fileinfo'] as $extension) {
    revisar(extension_loaded($extension), "Extensión {$extension} activa");
}

$autoload = __DIR__ . '/../vendor/autoload.php';

if (is_file($autoload)) {
    require $autoload;
}

revisar(class_exists('Dompdf\Dompdf'), 'Dompdf instalado con Composer');

/* ===== 2. Conexión PDO ===== */

seccion('2. Conexión PDO');

require __DIR__ . '/../app/core/Database.php';

try {
    $db = Database::get();
} catch (PDOException $error) {
    revisar(false, 'Conexión a carpin_db: ' . $error->getMessage());
    echo PHP_EOL . 'No se puede continuar sin conexión a la base de datos.' . PHP_EOL;
    exit(1);
}

$servidor = $db->query('SELECT VERSION() AS version, @@session.time_zone AS zona, NOW() AS ahora')
    ->fetch();

revisar(true, 'Conexión PDO a carpin_db (servidor ' . $servidor['version'] . ')');
revisar($servidor['zona'] === '-06:00', 'Zona horaria de la sesión: ' . $servidor['zona']);
revisar(
    date_default_timezone_get() === 'America/Mexico_City',
    'Zona horaria de PHP: ' . date_default_timezone_get()
);
echo '          Fecha y hora de MariaDB: ' . $servidor['ahora'] . PHP_EOL;
echo '          Fecha y hora de PHP:     ' . date('Y-m-d H:i:s') . PHP_EOL;

/* ===== 3. Tablas ===== */

seccion('3. Tablas de carpin_db');

$tablasEsperadas = [
    'rol', 'usuario', 'trabajador', 'cliente', 'configuracion', 'porcentaje_rol',
    'categoria_catalogo', 'producto_catalogo', 'solicitud_cotizacion', 'material',
    'presupuesto', 'presupuesto_detalle', 'fase', 'proyecto', 'actividad',
    'historial_fase', 'capa_pintura', 'secado', 'reparacion', 'imagen_reparacion',
    'movimiento_inventario', 'reporte_danio', 'asistencia', 'nomina', 'nomina_detalle',
    'pago_proyecto_trabajador', 'pago_cliente', 'comprobante_cliente', 'proveedor',
    'comprobante_proveedor', 'notificacion',
];

$tablasReales = $db->query(
    "SELECT table_name FROM information_schema.tables WHERE table_schema = 'carpin_db'"
)->fetchAll(PDO::FETCH_COLUMN);

$faltantes = array_diff($tablasEsperadas, $tablasReales);
$sobrantes = array_diff($tablasReales, $tablasEsperadas);

revisar(count($tablasReales) === 31, 'Número de tablas: ' . count($tablasReales) . ' de 31');
revisar(
    $faltantes === [],
    'Tablas faltantes: ' . ($faltantes === [] ? 'ninguna' : implode(', ', $faltantes))
);
revisar(
    $sobrantes === [],
    'Tablas no definidas en el SRS: ' . ($sobrantes === [] ? 'ninguna' : implode(', ', $sobrantes))
);

/* ===== 4. Usuarios de prueba ===== */

seccion('4. Usuarios de prueba (contraseña Carpin2026)');

$usuariosEsperados = [
    'admin'      => 'ADMINISTRADOR',
    'carpintero' => 'CARPINTERO',
    'lijador1'   => 'LIJADOR',
    'pintor1'    => 'PINTOR',
    'contador'   => 'CONTADOR',
    'cliente1'   => 'CLIENTE',
];

$consultaUsuario = $db->prepare(
    'SELECT u.password_hash, u.activo, r.nombre AS rol, '
    . 'COALESCE(t.nombre_completo, c.nombre_completo) AS nombre '
    . 'FROM usuario u '
    . 'JOIN rol r ON r.id_rol = u.id_rol '
    . 'LEFT JOIN trabajador t ON t.id_usuario = u.id_usuario '
    . 'LEFT JOIN cliente c ON c.id_usuario = u.id_usuario '
    . 'WHERE u.username = ?'
);

foreach ($usuariosEsperados as $username => $rolEsperado) {
    $consultaUsuario->execute([$username]);
    $usuario = $consultaUsuario->fetch();

    if ($usuario === false) {
        revisar(false, "{$username}: no existe");
        continue;
    }

    $correcto = $usuario['rol'] === $rolEsperado
        && (int) $usuario['activo'] === 1
        && password_verify('Carpin2026', $usuario['password_hash']);

    revisar($correcto, sprintf('%-11s %-14s %s', $username, $usuario['rol'], $usuario['nombre']));
}

/* ===== 5. Datos iniciales y de prueba ===== */

seccion('5. Datos iniciales y de prueba');

$conteosEsperados = [
    'rol'                => 6,
    'fase'               => 10,
    'configuracion'      => 1,
    'porcentaje_rol'     => 2,
    'categoria_catalogo' => 6,
    'material'           => 8,
    'usuario'            => 6,
    'trabajador'         => 5,
    'cliente'            => 1,
    'producto_catalogo'  => 6,
    'proyecto'           => 2,
    'actividad'          => 6,
    'historial_fase'     => 8,
    'pago_cliente'       => 2,
    'notificacion'       => 1,
];

foreach ($conteosEsperados as $tabla => $esperado) {
    $real = contar($db, $tabla);
    $texto = sprintf('%-20s %2d registros (se esperan %d)', $tabla, $real, $esperado);

    revisar($real === $esperado, $texto);
}

$sumaPorcentajes = (float) $db->query('SELECT SUM(porcentaje) FROM porcentaje_rol')
    ->fetchColumn();

revisar(
    abs($sumaPorcentajes - 100) < 0.001,
    'Porcentajes por rol suman ' . $sumaPorcentajes . ' %'
);

$proyectos = $db->query(
    'SELECT p.folio, p.tipo_mueble, f.nombre AS fase, p.porcentaje_avance, p.status_pago '
    . 'FROM proyecto p JOIN fase f ON f.id_fase = p.id_fase ORDER BY p.folio'
)->fetchAll();

foreach ($proyectos as $proyecto) {
    echo sprintf(
        '          %s  %-16s %-19s %6s %%  %s',
        $proyecto['folio'],
        $proyecto['tipo_mueble'],
        $proyecto['fase'],
        $proyecto['porcentaje_avance'],
        $proyecto['status_pago']
    ) . PHP_EOL;
}

/* ===== Resultado final ===== */

echo PHP_EOL;

if ($errores === 0) {
    echo 'RESULTADO: todo correcto. La instalación está lista.' . PHP_EOL;
    exit(0);
}

echo "RESULTADO: se encontraron {$errores} error(es). "
    . 'Revisa los puntos marcados con [ERROR].' . PHP_EOL;
exit(1);
