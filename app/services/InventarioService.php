<?php
/*
 * =====================================================
 * Carpintería Carpin – app/services/InventarioService.php
 * Reglas del inventario (RF-37 a RF-39, RN-17, CU-14):
 *   - alta de material con su existencia inicial como
 *     entrada "Existencia inicial" (respuesta 2);
 *   - entradas y salidas en una transacción, sin existencia
 *     negativa; la entrada fija el último costo de compra y la
 *     salida copia el costo vigente (respuesta 3);
 *   - alerta de existencia baja al Carpintero y al Contador.
 * =====================================================
 */

final class InventarioService {

    /* ===== Límite de las columnas DECIMAL(10,2) ===== */
    public const MAX_CANTIDAD = 99999999.99;

    /**
     * Registra un material nuevo y, si trae existencia inicial, la guarda
     * como movimiento de entrada (respuesta 2 de la Fase 3).
     *
     * Recibe: $datos, con nombre, categoria, unidad, minimo y
     *         costo_unitario; $existenciaInicial, texto con 2 decimales;
     *         $idUsuario, quien lo registra.
     * Devuelve: id del material.
     */
    public static function crearMaterial(
        array $datos,
        string $existenciaInicial,
        int $idUsuario
    ): int {
        $db = Database::get();

        try {
            $db->beginTransaction();

            $idMaterial = (new Material())->crear($datos);

            if ((float) $existenciaInicial > 0) {
                self::guardarMovimiento(
                    $idMaterial,
                    'ENTRADA',
                    $existenciaInicial,
                    $datos['costo_unitario'],
                    null,
                    null,
                    'Existencia inicial',
                    $idUsuario,
                    false
                );
            }

            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $error;
        }

        self::revisarAlerta($idMaterial, $idUsuario);

        return $idMaterial;
    }

    /**
     * Registra una entrada o una salida y actualiza la existencia (RF-38).
     *
     * Recibe: $idMaterial; $tipo, "ENTRADA" o "SALIDA"; $cantidad, texto
     *         con 2 decimales; $costoUnitario, costo de compra (solo
     *         entradas; null para dejar el costo vigente); $idProyecto e
     *         $idReparacion, destino de la salida (uno de los dos);
     *         $nota, o null; $idUsuario, quien lo registra.
     * Devuelve: nada. Lanza DomainException si la salida no alcanza.
     */
    public static function registrarMovimiento(
        int $idMaterial,
        string $tipo,
        string $cantidad,
        ?string $costoUnitario,
        ?int $idProyecto,
        ?int $idReparacion,
        ?string $nota,
        int $idUsuario
    ): void {
        $db = Database::get();

        try {
            $db->beginTransaction();

            self::guardarMovimiento(
                $idMaterial,
                $tipo,
                $cantidad,
                $costoUnitario,
                $idProyecto,
                $idReparacion,
                $nota,
                $idUsuario,
                true
            );

            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $error;
        }

        self::revisarAlerta($idMaterial, $idUsuario);
    }

    /**
     * Revisa si el material quedó en su mínimo o debajo y, si es así,
     * avisa a los Carpinteros y Contadores activos (RN-17, CU-14): una vez
     * por material al día (SRS 5.4) y no se repite si la persona ya tiene
     * un aviso sin leer de ese material (CU-14, A1). No se avisa a quien
     * hizo la acción.
     *
     * Recibe: $idMaterial; $idUsuarioActor, quien hizo la acción.
     * Devuelve: nada.
     */
    public static function revisarAlerta(int $idMaterial, int $idUsuarioActor): void {
        $material = (new Material())->buscarPorId($idMaterial);

        $sinAlerta = $material === null
            || (int) $material['activo'] !== 1
            || !Material::estaBajo($material);

        if ($sinAlerta) {
            return;
        }

        $notificaciones = new Notificacion();
        $prefijo = 'INV-' . $idMaterial . '-';
        $unidad = $material['unidad'];
        $mensaje = 'Existencia baja: ' . $material['nombre'] . ' ('
            . Material::cantidadConUnidad($material['existencia'], $unidad)
            . ', mínimo ' . Material::cantidadConUnidad($material['minimo'], $unidad) . ')';

        $destinatarios = array_merge(
            NotificacionService::usuariosDeRol(Auth::CARPINTERO),
            NotificacionService::usuariosDeRol(Auth::CONTADOR)
        );

        foreach (array_unique($destinatarios) as $idUsuario) {
            if ($idUsuario === $idUsuarioActor) {
                continue;
            }

            if ($notificaciones->tieneSinLeerConClave($idUsuario, $prefijo)) {
                continue;
            }

            $notificaciones->crear(
                $idUsuario,
                'INVENTARIO',
                $mensaje,
                '/inventario/' . $idMaterial,
                $prefijo . date('Y-m-d')
            );
        }
    }

    /**
     * Guarda el movimiento y la nueva existencia. Debe llamarse dentro de
     * una transacción.
     *
     * Recibe: los mismos datos que registrarMovimiento; $actualizarCosto,
     *         false en la existencia inicial (el costo ya está en el alta).
     * Devuelve: nada. Lanza DomainException si la salida no alcanza.
     */
    private static function guardarMovimiento(
        int $idMaterial,
        string $tipo,
        string $cantidad,
        ?string $costoUnitario,
        ?int $idProyecto,
        ?int $idReparacion,
        ?string $nota,
        int $idUsuario,
        bool $actualizarCosto
    ): void {
        $materiales = new Material();
        $material = $materiales->buscarPorId($idMaterial, true);

        if ($material === null) {
            throw new DomainException('El material no existe.');
        }

        $existenciaCentesimas = (int) round((float) $material['existencia'] * 100);
        $cantidadCentesimas = (int) round((float) $cantidad * 100);

        if ($tipo === 'SALIDA') {
            if ($cantidadCentesimas > $existenciaCentesimas) {
                throw new DomainException(
                    'No hay suficiente existencia: quedan '
                    . Material::cantidadConUnidad($material['existencia'], $material['unidad'])
                    . '.'
                );
            }

            $nuevaCentesimas = $existenciaCentesimas - $cantidadCentesimas;
            $costoMovimiento = (string) $material['costo_unitario'];
            $nuevoCosto = null;
        } else {
            $nuevaCentesimas = $existenciaCentesimas + $cantidadCentesimas;
            $costoMovimiento = $costoUnitario ?? (string) $material['costo_unitario'];
            $nuevoCosto = ($actualizarCosto && $costoUnitario !== null) ? $costoUnitario : null;
        }

        if ($nuevaCentesimas > (int) round(self::MAX_CANTIDAD * 100)) {
            throw new DomainException('La existencia pasaría del máximo permitido.');
        }

        (new MovimientoInventario())->crear([
            'id_material'    => $idMaterial,
            'tipo'           => $tipo,
            'cantidad'       => $cantidad,
            'costo_unitario' => $costoMovimiento,
            'id_proyecto'    => $idProyecto,
            'id_reparacion'  => $idReparacion,
            'id_usuario'     => $idUsuario,
            'nota'           => $nota,
        ]);

        $materiales->actualizarExistencia(
            $idMaterial,
            number_format($nuevaCentesimas / 100, 2, '.', ''),
            $nuevoCosto
        );
    }

}
