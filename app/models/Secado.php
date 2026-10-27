<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Secado.php
 * Acceso a la tabla secado: tiempos de secado programados
 * por el Pintor (HU-09, RF-27).
 * =====================================================
 */

class Secado extends Model {

    /**
     * Programa un secado. Los secados anteriores del proyecto que no se
     * habían avisado quedan marcados como avisados, porque el nuevo los
     * reemplaza (cuenta el más reciente, respuesta 2).
     *
     * Recibe: $idProyecto; $inicio, "aaaa-mm-dd hh:mm:ss"; $horas;
     *         $finEstimado, "aaaa-mm-dd hh:mm:ss".
     * Devuelve: nada.
     */
    public function programar(
        int $idProyecto,
        string $inicio,
        string $horas,
        string $finEstimado
    ): void {
        $reemplazar = $this->db->prepare(
            'UPDATE secado SET notificado = 1 WHERE id_proyecto = ? AND notificado = 0'
        );
        $reemplazar->execute([$idProyecto]);

        $consulta = $this->db->prepare(
            'INSERT INTO secado (id_proyecto, inicio, horas, fin_estimado) VALUES (?, ?, ?, ?)'
        );
        $consulta->execute([$idProyecto, $inicio, $horas, $finEstimado]);
    }

    /**
     * Devuelve el secado más reciente que inició a partir de una fecha.
     *
     * Recibe: $idProyecto; $desde, "aaaa-mm-dd hh:mm:ss".
     * Devuelve: datos del secado, o null si no hay.
     */
    public function ultimoDesde(int $idProyecto, string $desde): ?array {
        $consulta = $this->db->prepare(
            'SELECT * FROM secado WHERE id_proyecto = ? AND inicio >= ? '
            . 'ORDER BY id_secado DESC LIMIT 1'
        );
        $consulta->execute([$idProyecto, $desde]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Lista los secados de un proyecto, del más reciente al más antiguo.
     *
     * Recibe: $idProyecto.
     * Devuelve: lista de secados.
     */
    public function listarPorProyecto(int $idProyecto): array {
        $consulta = $this->db->prepare(
            'SELECT * FROM secado WHERE id_proyecto = ? ORDER BY id_secado DESC'
        );
        $consulta->execute([$idProyecto]);

        return $consulta->fetchAll();
    }

    /**
     * Lista los secados cumplidos que todavía no se avisan, con la fase
     * actual de su proyecto.
     *
     * Recibe: nada.
     * Devuelve: lista con id_secado, id_proyecto, folio, tipo_mueble e id_fase.
     */
    public function cumplidosSinAviso(): array {
        return $this->db->query(
            'SELECT s.id_secado, s.id_proyecto, p.folio, p.tipo_mueble, p.id_fase, p.activo '
            . 'FROM secado s JOIN proyecto p ON p.id_proyecto = s.id_proyecto '
            . 'WHERE s.notificado = 0 AND s.fin_estimado <= NOW()'
        )->fetchAll();
    }

    /**
     * Marca un secado como avisado para no repetir la notificación.
     *
     * Recibe: $idSecado.
     * Devuelve: nada.
     */
    public function marcarNotificado(int $idSecado): void {
        $consulta = $this->db->prepare('UPDATE secado SET notificado = 1 WHERE id_secado = ?');
        $consulta->execute([$idSecado]);
    }

}
