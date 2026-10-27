<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Fase.php
 * Acceso a la tabla fase: las 10 fases de producción con
 * su orden, rol responsable y porcentaje (SRS 5.1, RN-01).
 * =====================================================
 */

class Fase extends Model {

    /* ===== Fases con significado especial ===== */
    public const COTIZADO = 1;
    public const ENSAMBLADO = 4;
    public const LISTO_PARA_PINTURA = 6;
    public const LISTO_PARA_ENTREGA = 9;
    public const ENTREGADO = 10;

    /**
     * Devuelve las 10 fases ordenadas, con el nombre del rol responsable.
     *
     * Recibe: nada.
     * Devuelve: lista de fases indexada por id_fase.
     */
    public function todas(): array {
        $filas = $this->db->query(
            'SELECT f.*, r.nombre AS rol_responsable '
            . 'FROM fase f JOIN rol r ON r.id_rol = f.id_rol_responsable '
            . 'ORDER BY f.orden'
        )->fetchAll();

        $fases = [];

        foreach ($filas as $fila) {
            $fases[(int) $fila['id_fase']] = $fila;
        }

        return $fases;
    }

    /**
     * Busca una fase por id, con el nombre del rol responsable.
     *
     * Recibe: $idFase, id de la fase.
     * Devuelve: datos de la fase, o null si no existe.
     */
    public function buscarPorId(int $idFase): ?array {
        $consulta = $this->db->prepare(
            'SELECT f.*, r.nombre AS rol_responsable '
            . 'FROM fase f JOIN rol r ON r.id_rol = f.id_rol_responsable '
            . 'WHERE f.id_fase = ?'
        );
        $consulta->execute([$idFase]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Busca la fase inmediata siguiente según el orden.
     *
     * Recibe: $orden, orden de la fase actual.
     * Devuelve: datos de la siguiente fase, o null si es la última.
     */
    public function siguiente(int $orden): ?array {
        $consulta = $this->db->prepare(
            'SELECT f.*, r.nombre AS rol_responsable '
            . 'FROM fase f JOIN rol r ON r.id_rol = f.id_rol_responsable '
            . 'WHERE f.orden = ?'
        );
        $consulta->execute([$orden + 1]);

        return $consulta->fetch() ?: null;
    }

}
