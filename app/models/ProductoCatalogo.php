<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/ProductoCatalogo.php
 * Acceso a la tabla producto_catalogo: muebles que se
 * muestran en la página pública (RF-09, RF-12).
 * =====================================================
 */

class ProductoCatalogo extends Model {

    /* ===== Consulta base con el nombre de la categoría ===== */
    private const SELECT_COMPLETO = 'SELECT p.*, c.nombre AS categoria_nombre '
        . 'FROM producto_catalogo p '
        . 'JOIN categoria_catalogo c ON c.id_categoria = p.id_categoria ';

    /**
     * Lista los productos visibles, opcionalmente de una categoría.
     *
     * Recibe: $idCategoria, categoría a filtrar (0 para todas).
     * Devuelve: lista de productos activos.
     */
    public function listarVisibles(int $idCategoria = 0): array {
        $sql = self::SELECT_COMPLETO . 'WHERE p.activo = 1';
        $valores = [];

        if ($idCategoria > 0) {
            $sql .= ' AND p.id_categoria = ?';
            $valores[] = $idCategoria;
        }

        $sql .= ' ORDER BY p.id_categoria, p.nombre';

        $consulta = $this->db->prepare($sql);
        $consulta->execute($valores);

        return $consulta->fetchAll();
    }

    /**
     * Lista los productos visibles marcados como destacados (portada).
     *
     * Recibe: nada.
     * Devuelve: lista de productos destacados.
     */
    public function listarDestacados(): array {
        return $this->db->query(
            self::SELECT_COMPLETO
            . 'WHERE p.activo = 1 AND p.destacado = 1 '
            . 'ORDER BY p.id_categoria, p.nombre'
        )->fetchAll();
    }

    /**
     * Lista todos los productos, visibles y ocultos (pantalla del Administrador).
     *
     * Recibe: nada.
     * Devuelve: lista de productos.
     */
    public function listarTodos(): array {
        return $this->db->query(
            self::SELECT_COMPLETO . 'ORDER BY p.activo DESC, p.id_categoria, p.nombre'
        )->fetchAll();
    }

    /**
     * Busca un producto por id.
     *
     * Recibe: $idProducto.
     * Devuelve: datos del producto, o null si no existe.
     */
    public function buscarPorId(int $idProducto): ?array {
        $consulta = $this->db->prepare(self::SELECT_COMPLETO . 'WHERE p.id_producto = ?');
        $consulta->execute([$idProducto]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Cuenta los productos visibles (panel del Administrador).
     *
     * Recibe: nada.
     * Devuelve: número de productos con activo = 1.
     */
    public function contarVisibles(): int {
        return (int) $this->db->query(
            'SELECT COUNT(*) FROM producto_catalogo WHERE activo = 1'
        )->fetchColumn();
    }

    /**
     * Registra un producto nuevo (visible).
     *
     * Recibe: $datos, con id_categoria, nombre, descripcion, precio_desde,
     *         imagen_ruta y destacado.
     * Devuelve: id del producto creado.
     */
    public function crear(array $datos): int {
        $consulta = $this->db->prepare(
            'INSERT INTO producto_catalogo '
            . '(id_categoria, nombre, descripcion, precio_desde, imagen_ruta, destacado) '
            . 'VALUES (?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            $datos['id_categoria'],
            $datos['nombre'],
            $datos['descripcion'],
            $datos['precio_desde'],
            $datos['imagen_ruta'],
            $datos['destacado'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza los datos de un producto.
     *
     * Recibe: $idProducto; $datos, con id_categoria, nombre, descripcion,
     *         precio_desde, imagen_ruta y destacado.
     * Devuelve: nada.
     */
    public function actualizar(int $idProducto, array $datos): void {
        $consulta = $this->db->prepare(
            'UPDATE producto_catalogo SET id_categoria = ?, nombre = ?, descripcion = ?, '
            . 'precio_desde = ?, imagen_ruta = ?, destacado = ? WHERE id_producto = ?'
        );
        $consulta->execute([
            $datos['id_categoria'],
            $datos['nombre'],
            $datos['descripcion'],
            $datos['precio_desde'],
            $datos['imagen_ruta'],
            $datos['destacado'],
            $idProducto,
        ]);
    }

    /**
     * Muestra u oculta un producto en la página pública (RF-12).
     *
     * Recibe: $idProducto; $visible, true para mostrarlo.
     * Devuelve: nada.
     */
    public function cambiarVisibilidad(int $idProducto, bool $visible): void {
        $consulta = $this->db->prepare(
            'UPDATE producto_catalogo SET activo = ? WHERE id_producto = ?'
        );
        $consulta->execute([$visible ? 1 : 0, $idProducto]);
    }

    /**
     * Indica si alguna solicitud de cotización menciona el producto.
     *
     * Recibe: $idProducto.
     * Devuelve: true si hay solicitudes ligadas (no se puede eliminar).
     */
    public function tieneSolicitudes(int $idProducto): bool {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM solicitud_cotizacion WHERE id_producto = ?'
        );
        $consulta->execute([$idProducto]);

        return (int) $consulta->fetchColumn() > 0;
    }

    /**
     * Elimina un producto (solo si no tiene solicitudes ligadas).
     *
     * Recibe: $idProducto.
     * Devuelve: nada.
     */
    public function eliminar(int $idProducto): void {
        $consulta = $this->db->prepare('DELETE FROM producto_catalogo WHERE id_producto = ?');
        $consulta->execute([$idProducto]);
    }

}
