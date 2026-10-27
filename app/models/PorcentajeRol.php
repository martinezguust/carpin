<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/PorcentajeRol.php
 * Acceso a la tabla porcentaje_rol: porcentaje de la mano
 * de obra por proyecto de cada rol operativo (RF-44, RN-13).
 * =====================================================
 */

class PorcentajeRol extends Model {

    /**
     * Lista los porcentajes con el nombre de su rol.
     *
     * Recibe: nada.
     * Devuelve: lista con id_rol, rol y porcentaje.
     */
    public function listar(): array {
        return $this->db->query(
            'SELECT p.id_rol, r.nombre AS rol, p.porcentaje '
            . 'FROM porcentaje_rol p JOIN rol r ON r.id_rol = p.id_rol '
            . 'ORDER BY p.id_rol'
        )->fetchAll();
    }

    /**
     * Actualiza el porcentaje de un rol.
     *
     * Recibe: $idRol; $porcentaje, texto con 2 decimales.
     * Devuelve: nada.
     */
    public function actualizar(int $idRol, string $porcentaje): void {
        $consulta = $this->db->prepare('UPDATE porcentaje_rol SET porcentaje = ? WHERE id_rol = ?');
        $consulta->execute([$porcentaje, $idRol]);
    }

}
