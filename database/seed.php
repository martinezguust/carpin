<?php
/*
 * =====================================================
 * Carpintería Carpin – database/seed.php
 * Inserta los datos de prueba que necesitan PHP (SRS 11.4,
 * 5.4 y 14.5):
 *   - 6 usuarios de prueba (uno por rol) con la contraseña
 *     Carpin2026 cifrada con password_hash (bcrypt);
 *   - sus registros en trabajador / cliente;
 *   - 6 productos de catálogo de ejemplo;
 *   - 2 proyectos de ejemplo con actividades, historial de
 *     fases y pago del anticipo;
 *   - 1 notificación sin leer para probar la campana.
 *
 * Uso (desde la carpeta carpin/, después de schema.sql y seed.sql):
 *     php database/seed.php
 * =====================================================
 */

declare(strict_types=1);

/* ===== Solo se permite ejecutar desde la consola ===== */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta desde la consola.');
}

/* ===== Preparación ===== */

require __DIR__ . '/../app/core/Database.php';

$configuracion = require __DIR__ . '/../config/config.php';
date_default_timezone_set($configuracion['app']['timezone']);

const CONTRASENA_PRUEBA = 'Carpin2026';

/**
 * Ejecuta un INSERT con sentencia preparada y devuelve el id generado.
 *
 * Recibe: $db, conexión PDO; $sql, sentencia con marcadores "?";
 *         $valores, valores para los marcadores.
 * Devuelve: id autoincremental del registro insertado (0 si no aplica).
 */
function insertar(PDO $db, string $sql, array $valores): int {
    $sentencia = $db->prepare($sql);
    $sentencia->execute($valores);

    return (int) $db->lastInsertId();
}

/**
 * Busca el porcentaje de avance de una fase (tabla fase, RN-01).
 *
 * Recibe: $db, conexión PDO; $idFase, id de la fase.
 * Devuelve: porcentaje de la fase, ej. "66.67".
 */
function porcentajeDeFase(PDO $db, int $idFase): string {
    $sentencia = $db->prepare('SELECT porcentaje FROM fase WHERE id_fase = ?');
    $sentencia->execute([$idFase]);

    return (string) $sentencia->fetchColumn();
}

$db = Database::get();

// El seed solo se ejecuta sobre una base recién creada
$usuariosExistentes = (int) $db->query('SELECT COUNT(*) FROM usuario')->fetchColumn();

if ($usuariosExistentes > 0) {
    echo "La tabla usuario ya tiene datos. Vuelve a ejecutar schema.sql y seed.sql" . PHP_EOL;
    echo "antes de correr este script." . PHP_EOL;
    exit(1);
}

$hash = password_hash(CONTRASENA_PRUEBA, PASSWORD_BCRYPT);

try {
    $db->beginTransaction();

    /* ===== Usuarios de prueba (SRS 14.5) ===== */

    $sqlUsuario = 'INSERT INTO usuario (username, password_hash, id_rol) VALUES (?, ?, ?)';

    $idUsuarioAdmin      = insertar($db, $sqlUsuario, ['admin', $hash, 1]);
    $idUsuarioCarpintero = insertar($db, $sqlUsuario, ['carpintero', $hash, 2]);
    $idUsuarioLijador    = insertar($db, $sqlUsuario, ['lijador1', $hash, 3]);
    $idUsuarioPintor     = insertar($db, $sqlUsuario, ['pintor1', $hash, 4]);
    $idUsuarioContador   = insertar($db, $sqlUsuario, ['contador', $hash, 5]);
    $idUsuarioCliente    = insertar($db, $sqlUsuario, ['cliente1', $hash, 6]);

    /* ===== Trabajadores (SRS 5.4) ===== */

    $sqlTrabajador = 'INSERT INTO trabajador '
        . '(id_usuario, nombre_completo, telefono, forma_pago, tarifa_hora, salario_dia) '
        . 'VALUES (?, ?, ?, ?, ?, ?)';

    // El Administrador queda fuera de la nómina (forma_pago NULL)
    insertar($db, $sqlTrabajador, [
        $idUsuarioAdmin, 'Administrador General', '2281000001', null, null, null,
    ]);

    $idCarpintero = insertar($db, $sqlTrabajador, [
        $idUsuarioCarpintero, 'Carlos Hernández', '2281000002', 'DIA', null, '500.00',
    ]);

    $idLijador = insertar($db, $sqlTrabajador, [
        $idUsuarioLijador, 'Juan Pérez', '2281000003', 'PROYECTO', null, null,
    ]);

    $idPintor = insertar($db, $sqlTrabajador, [
        $idUsuarioPintor, 'Ana Ruiz', '2281000004', 'HORA', '60.00', null,
    ]);

    insertar($db, $sqlTrabajador, [
        $idUsuarioContador, 'Laura Gómez', '2281000005', 'DIA', null, '450.00',
    ]);

    /* ===== Cliente de prueba ===== */

    $idCliente = insertar(
        $db,
        'INSERT INTO cliente (id_usuario, nombre_completo, telefono) VALUES (?, ?, ?)',
        [$idUsuarioCliente, 'María López', '2281234567']
    );

    /* ===== Catálogo de ejemplo (sin imagen) ===== */

    $categorias = $db->query('SELECT nombre, id_categoria FROM categoria_catalogo')
        ->fetchAll(PDO::FETCH_KEY_PAIR);

    $productos = [
        [
            'Mesas',
            'Mesa de comedor de pino',
            'Mesa rectangular de pino para seis personas, hecha a la medida.',
            '4500.00',
            1,
        ],
        [
            'Sillas',
            'Silla de madera clásica',
            'Silla de madera sólida con respaldo clásico, ideal para comedor.',
            '850.00',
            0,
        ],
        [
            'Cocinas integrales',
            'Cocina integral a la medida',
            'Cocina integral diseñada y fabricada a la medida de tu espacio.',
            '25000.00',
            1,
        ],
        [
            'Estantes',
            'Librero de 5 niveles',
            'Librero de cinco entrepaños para libros y objetos decorativos.',
            '3200.00',
            0,
        ],
        [
            'Roperos',
            'Ropero de 2 puertas',
            'Ropero de dos puertas con espacio para colgar y entrepaños.',
            '9500.00',
            1,
        ],
        [
            'Otros',
            'Buró con cajón',
            'Buró de madera con un cajón, perfecto junto a la cama.',
            '1800.00',
            0,
        ],
    ];

    $sqlProducto = 'INSERT INTO producto_catalogo '
        . '(id_categoria, nombre, descripcion, precio_desde, destacado) '
        . 'VALUES (?, ?, ?, ?, ?)';

    foreach ($productos as [$categoria, $nombre, $descripcion, $precio, $destacado]) {
        insertar($db, $sqlProducto, [
            $categorias[$categoria],
            $nombre,
            $descripcion,
            $precio,
            $destacado,
        ]);
    }

    /* ===== Proyectos de ejemplo ===== */

    $sqlProyecto = 'INSERT INTO proyecto '
        . '(folio, id_cliente, tipo_mueble, descripcion, alto_cm, ancho_cm, fondo_cm, '
        . 'madera, acabado, color, granulado_lija, ficha_color, fecha_registro, '
        . 'fecha_entrega, fecha_limite_pago, precio, id_fase, porcentaje_avance, '
        . 'status_pago, id_usuario_registro, creado_en) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    // Ropero de cedro en "Listo para pintura" (fase 6)
    $idRopero = insertar($db, $sqlProyecto, [
        'PRY-2026-0001',
        $idCliente,
        'Ropero',
        'Ropero de cedro de 2 puertas con cajonera interior',
        '190.0',
        '120.0',
        '55.0',
        'Cedro',
        'Satinado',
        'Nogal',
        '80-120-220',
        'Producto: Barniz | Notas: tinta nogal antes del barniz',
        '2026-10-13',
        '2026-11-04',
        '2026-11-04',
        '12500.00',
        6,
        porcentajeDeFase($db, 6),
        'CON_ANTICIPO',
        $idUsuarioCarpintero,
        '2026-10-13 10:00:00',
    ]);

    // Mesa de comedor de pino en "En armado" (fase 2)
    $idMesa = insertar($db, $sqlProyecto, [
        'PRY-2026-0002',
        $idCliente,
        'Mesa de comedor',
        'Mesa de comedor de pino para 6 personas',
        null,
        null,
        null,
        'Pino',
        null,
        null,
        '100-180',
        null,
        '2026-10-13',
        '2026-11-15',
        '2026-11-15',
        '4500.00',
        2,
        porcentajeDeFase($db, 2),
        'CON_ANTICIPO',
        $idUsuarioCarpintero,
        '2026-10-13 11:00:00',
    ]);

    /* ===== Actividades asignadas ===== */

    $sqlActividad = 'INSERT INTO actividad '
        . '(id_proyecto, id_trabajador, tipo, fecha_compromiso, estado, asignada_en, terminada_en) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?)';

    $actividades = [
        // Ropero: armado y lijado terminados, pintura pendiente
        [$idRopero, $idCarpintero, 'ARMADO', '2026-10-17', 'TERMINADA', '2026-10-20 17:00:00'],
        [$idRopero, $idLijador, 'LIJADO', '2026-10-24', 'TERMINADA', '2026-10-24 17:00:00'],
        [$idRopero, $idPintor, 'PINTURA', '2026-10-31', 'PENDIENTE', null],

        // Mesa: las tres actividades pendientes
        [$idMesa, $idCarpintero, 'ARMADO', '2026-10-31', 'PENDIENTE', null],
        [$idMesa, $idLijador, 'LIJADO', '2026-11-07', 'PENDIENTE', null],
        [$idMesa, $idPintor, 'PINTURA', '2026-11-12', 'PENDIENTE', null],
    ];

    foreach ($actividades as $actividad) {
        [$idProyecto, $idTrabajador, $tipo, $compromiso, $estado, $terminada] = $actividad;

        // Las actividades se asignaron el mismo día que se registró cada proyecto
        if ($idProyecto === $idRopero) {
            $asignada = '2026-10-13 10:00:00';
        } else {
            $asignada = '2026-10-13 11:00:00';
        }

        insertar($db, $sqlActividad, [
            $idProyecto,
            $idTrabajador,
            $tipo,
            $compromiso,
            $estado,
            $asignada,
            $terminada,
        ]);
    }

    /* ===== Historial de fases (RF-22) ===== */

    $sqlHistorial = 'INSERT INTO historial_fase '
        . '(id_proyecto, id_fase_anterior, id_fase_nueva, id_usuario, fecha) '
        . 'VALUES (?, ?, ?, ?, ?)';

    $historial = [
        // Ropero: el carpintero lo lleva hasta "Ensamblado" y el lijador hasta la fase 6
        [$idRopero, null, 1, $idUsuarioCarpintero, '2026-10-13 10:00:00'],
        [$idRopero, 1, 2, $idUsuarioCarpintero, '2026-10-15 17:00:00'],
        [$idRopero, 2, 3, $idUsuarioCarpintero, '2026-10-17 17:00:00'],
        [$idRopero, 3, 4, $idUsuarioCarpintero, '2026-10-20 17:00:00'],
        [$idRopero, 4, 5, $idUsuarioLijador, '2026-10-22 17:00:00'],
        [$idRopero, 5, 6, $idUsuarioLijador, '2026-10-24 17:00:00'],

        // Mesa: registrada y pasada a "En armado"
        [$idMesa, null, 1, $idUsuarioCarpintero, '2026-10-13 11:00:00'],
        [$idMesa, 1, 2, $idUsuarioCarpintero, '2026-10-20 17:00:00'],
    ];

    foreach ($historial as $cambio) {
        insertar($db, $sqlHistorial, $cambio);
    }

    /* ===== Anticipos en efectivo (registrados por el carpintero) ===== */

    $sqlPago = 'INSERT INTO pago_cliente '
        . '(id_proyecto, monto, metodo, fecha, id_usuario, creado_en) '
        . 'VALUES (?, ?, ?, ?, ?, ?)';

    insertar($db, $sqlPago, [
        $idRopero, '5000.00', 'EFECTIVO', '2026-10-13', $idUsuarioCarpintero, '2026-10-13 10:00:00',
    ]);

    insertar($db, $sqlPago, [
        $idMesa, '2000.00', 'EFECTIVO', '2026-10-13', $idUsuarioCarpintero, '2026-10-13 11:00:00',
    ]);

    /* ===== Notificación sin leer para probar la campana ===== */

    insertar(
        $db,
        'INSERT INTO notificacion (id_usuario, tipo, mensaje, enlace, creado_en) '
            . 'VALUES (?, ?, ?, ?, ?)',
        [
            $idUsuarioPintor,
            'FASE',
            'El proyecto PRY-2026-0001 (Ropero) está listo para pintura',
            '/mis-pendientes',
            '2026-10-24 17:00:00',
        ]
    );

    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    echo 'No se pudieron insertar los datos de prueba: ' . $error->getMessage() . PHP_EOL;
    exit(1);
}

/* ===== Resumen ===== */

echo 'Datos de prueba insertados correctamente:' . PHP_EOL;
echo '  - 6 usuarios (contraseña: ' . CONTRASENA_PRUEBA . ')' . PHP_EOL;
echo '  - 5 trabajadores y 1 cliente' . PHP_EOL;
echo '  - 6 productos de catálogo' . PHP_EOL;
echo '  - 2 proyectos con 6 actividades, 8 cambios de fase y 2 anticipos' . PHP_EOL;
echo '  - 1 notificación sin leer para pintor1' . PHP_EOL;
