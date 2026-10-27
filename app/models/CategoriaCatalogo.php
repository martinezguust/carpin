<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/CategoriaCatalogo.php
 * Acceso a la tabla categoria_catalogo: categorías fijas del
 * catálogo público (Mesas, Sillas, Cocinas integrales,
 * Estantes, Roperos y Otros).
 * =====================================================
 */

class CategoriaCatalogo extends Model {

    /**
     * Devuelve todas las categorías en el orden en que se registraron.
     *
     * Recibe: nada.
     * Devuelve: lista con id_categoria y nombre.
     */
    public function todas(): array {
        return $this->db->query(
            'SELECT id_categoria, nombre FROM categoria_catalogo ORDER BY id_categoria'
        )->fetchAll();
    }

    /**
     * Indica si existe una categoría.
     *
     * Recibe: $idCategoria.
     * Devuelve: true si existe.
     */
    public function existe(int $idCategoria): bool {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM categoria_catalogo WHERE id_categoria = ?'
        );
        $consulta->execute([$idCategoria]);

        return (int) $consulta->fetchColumn() > 0;
    }

}
