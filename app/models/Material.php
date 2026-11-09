<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Material.php
 * Acceso a la tabla material: materiales, pinturas,
 * solventes, herrajes, etc., con su existencia, mínimo y
 * costo (HU-07, HU-16, RF-37, RN-17).
 * =====================================================
 */

class Material extends Model {

    /* ===== Categorías y su texto (RF-37) ===== */
    public const CATEGORIAS = [
        'MADERA'    => 'Madera',
        'PINTURA'   => 'Pintura',
        'SOLVENTE'  => 'Solvente',
        'BARNIZ'    => 'Barniz',
        'LIJA'      => 'Lija',
        'HERRAJE'   => 'Herraje',
        'PEGAMENTO' => 'Pegamento',
        'OTRO'      => 'Otro',
    ];

    /* ===== Categorías que ve y mueve el Pintor (SRS 5.4) ===== */
    public const CATEGORIAS_PINTOR = ['PINTURA', 'SOLVENTE', 'BARNIZ'];

    /* ===== Unidades: texto completo y abreviatura ===== */
    public const UNIDADES = [
        'PIEZA' => ['nombre' => 'Pieza', 'corta' => 'pzas'],
        'LITRO' => ['nombre' => 'Litro', 'corta' => 'L'],
        'METRO' => ['nombre' => 'Metro', 'corta' => 'm'],
        'KG'    => ['nombre' => 'Kg', 'corta' => 'kg'],
        'HOJA'  => ['nombre' => 'Hoja', 'corta' => 'hojas'],
        'CAJA'  => ['nombre' => 'Caja', 'corta' => 'cajas'],
    ];

    /**
     * Da formato a una cantidad sin ceros de sobra, con su unidad corta.
     *
     * Recibe: $cantidad, número o texto; $unidad, llave de UNIDADES.
     * Devuelve: texto como "2.5 L" o "20 pzas".
     */
    public static function cantidadConUnidad(mixed $cantidad, string $unidad): string {
        $numero = rtrim(rtrim(number_format((float) $cantidad, 2, '.', ','), '0'), '.');

        return $numero . ' ' . (self::UNIDADES[$unidad]['corta'] ?? '');
    }

    /**
     * Indica si la existencia llegó a su mínimo (RN-17).
     *
     * Recibe: $material, datos con existencia y minimo.
     * Devuelve: true si existencia ≤ mínimo.
     */
    public static function estaBajo(array $material): bool {
        return (float) $material['existencia'] <= (float) $material['minimo'];
    }

    /* ===== Consultas ===== */

    /**
     * Lista materiales con filtros.
     *
     * Recibe: $categorias, categorías permitidas (vacío para todas);
     *         $categoria, filtro elegido ("" para todas);
     *         $incluirBajas, true para mostrar también los dados de baja.
     * Devuelve: lista ordenada por categoría y nombre.
     */
    public function listar(array $categorias, string $categoria, bool $incluirBajas): array {
        $sql = 'SELECT * FROM material WHERE 1 = 1';
        $valores = [];

        if ($categorias !== []) {
            $marcadores = implode(', ', array_fill(0, count($categorias), '?'));
            $sql .= " AND categoria IN ({$marcadores})";
            $valores = array_merge($valores, $categorias);
        }

        if ($categoria !== '') {
            $sql .= ' AND categoria = ?';
            $valores[] = $categoria;
        }

        if (!$incluirBajas) {
            $sql .= ' AND activo = 1';
        }

        $sql .= ' ORDER BY activo DESC, categoria, nombre';

        $consulta = $this->db->prepare($sql);
        $consulta->execute($valores);

        return $consulta->fetchAll();
    }

    /**
     * Lista los materiales activos con existencia baja (RN-17), del más
     * crítico (menor existencia frente a su mínimo) al menos crítico.
     *
     * Recibe: nada.
     * Devuelve: lista de materiales bajos.
     */
    public function listarBajos(): array {
        return $this->db->query(
            'SELECT * FROM material '
            . 'WHERE activo = 1 AND existencia <= minimo '
            . 'ORDER BY (existencia - minimo), nombre'
        )->fetchAll();
    }

    /**
     * Busca un material por id.
     *
     * Recibe: $idMaterial; $bloquear, true para bloquear la fila dentro de
     *         una transacción (SELECT … FOR UPDATE).
     * Devuelve: datos del material, o null si no existe.
     */
    public function buscarPorId(int $idMaterial, bool $bloquear = false): ?array {
        $sql = 'SELECT * FROM material WHERE id_material = ?';

        if ($bloquear) {
            $sql .= ' FOR UPDATE';
        }

        $consulta = $this->db->prepare($sql);
        $consulta->execute([$idMaterial]);

        return $consulta->fetch() ?: null;
    }

    /* ===== Altas y cambios ===== */

    /**
     * Registra un material nuevo con existencia 0 (la existencia inicial se
     * agrega como movimiento de entrada).
     *
     * Recibe: $datos, con nombre, categoria, unidad, minimo y costo_unitario.
     * Devuelve: id del material.
     */
    public function crear(array $datos): int {
        $consulta = $this->db->prepare(
            'INSERT INTO material (nombre, categoria, unidad, existencia, minimo, costo_unitario) '
            . 'VALUES (?, ?, ?, 0, ?, ?)'
        );
        $consulta->execute([
            $datos['nombre'],
            $datos['categoria'],
            $datos['unidad'],
            $datos['minimo'],
            $datos['costo_unitario'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza los datos de un material (no la existencia, que solo cambia
     * con movimientos).
     *
     * Recibe: $idMaterial; $datos, con nombre, categoria, unidad, minimo y
     *         costo_unitario.
     * Devuelve: nada.
     */
    public function actualizar(int $idMaterial, array $datos): void {
        $consulta = $this->db->prepare(
            'UPDATE material SET nombre = ?, categoria = ?, unidad = ?, minimo = ?, '
            . 'costo_unitario = ? WHERE id_material = ?'
        );
        $consulta->execute([
            $datos['nombre'],
            $datos['categoria'],
            $datos['unidad'],
            $datos['minimo'],
            $datos['costo_unitario'],
            $idMaterial,
        ]);
    }

    /**
     * Guarda la nueva existencia y, si se indica, el último costo de compra.
     *
     * Recibe: $idMaterial; $existencia, texto con 2 decimales;
     *         $costoUnitario, nuevo costo o null para no cambiarlo.
     * Devuelve: nada.
     */
    public function actualizarExistencia(
        int $idMaterial,
        string $existencia,
        ?string $costoUnitario
    ): void {
        if ($costoUnitario === null) {
            $consulta = $this->db->prepare(
                'UPDATE material SET existencia = ? WHERE id_material = ?'
            );
            $consulta->execute([$existencia, $idMaterial]);
            return;
        }

        $consulta = $this->db->prepare(
            'UPDATE material SET existencia = ?, costo_unitario = ? WHERE id_material = ?'
        );
        $consulta->execute([$existencia, $costoUnitario, $idMaterial]);
    }

    /**
     * Da de baja o reactiva un material (respuesta 1 de la Fase 3).
     *
     * Recibe: $idMaterial; $activo, true para reactivar.
     * Devuelve: nada.
     */
    public function cambiarEstado(int $idMaterial, bool $activo): void {
        $consulta = $this->db->prepare('UPDATE material SET activo = ? WHERE id_material = ?');
        $consulta->execute([$activo ? 1 : 0, $idMaterial]);
    }

}
