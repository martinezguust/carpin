<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/ImagenReparacion.php
 * Acceso a la tabla imagen_reparacion: hasta 6 fotos por
 * reparación, entre "antes" y "después" (RF-34, respuesta 33).
 * =====================================================
 */

class ImagenReparacion extends Model {

    /* ===== Máximo de fotos por reparación ===== */
    public const MAXIMO = 6;

    /**
     * Registra una foto de la reparación.
     *
     * Recibe: $idReparacion; $ruta, ruta relativa a public/;
     *         $tipo, "ANTES" o "DESPUES".
     * Devuelve: nada.
     */
    public function crear(int $idReparacion, string $ruta, string $tipo): void {
        $consulta = $this->db->prepare(
            'INSERT INTO imagen_reparacion (id_reparacion, ruta, tipo) VALUES (?, ?, ?)'
        );
        $consulta->execute([$idReparacion, $ruta, $tipo]);
    }

    /**
     * Lista las fotos de una reparación en el orden en que se subieron.
     *
     * Recibe: $idReparacion.
     * Devuelve: lista con ruta, tipo y fecha.
     */
    public function listarPorReparacion(int $idReparacion): array {
        $consulta = $this->db->prepare(
            'SELECT * FROM imagen_reparacion WHERE id_reparacion = ? ORDER BY id_imagen'
        );
        $consulta->execute([$idReparacion]);

        return $consulta->fetchAll();
    }

    /**
     * Cuenta las fotos de una reparación.
     *
     * Recibe: $idReparacion.
     * Devuelve: número de fotos.
     */
    public function contar(int $idReparacion): int {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM imagen_reparacion WHERE id_reparacion = ?'
        );
        $consulta->execute([$idReparacion]);

        return (int) $consulta->fetchColumn();
    }

}
