<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/PagoCliente.php
 * Acceso a la tabla pago_cliente: pagos y anticipos hechos
 * en persona. El sistema NO cobra, solo registra (RF-48).
 * =====================================================
 */

class PagoCliente extends Model {

    /* ===== Métodos de pago permitidos ===== */
    public const METODOS = [
        'EFECTIVO'      => 'Efectivo',
        'TARJETA'       => 'Tarjeta',
        'TRANSFERENCIA' => 'Transferencia',
    ];

    /**
     * Registra un pago de un proyecto.
     *
     * Recibe: $idProyecto; $monto, mayor a 0; $metodo, llave de METODOS;
     *         $fecha, "aaaa-mm-dd"; $idUsuario, quién lo registró.
     * Devuelve: id del pago.
     */
    public function registrarDeProyecto(
        int $idProyecto,
        string $monto,
        string $metodo,
        string $fecha,
        int $idUsuario
    ): int {
        $consulta = $this->db->prepare(
            'INSERT INTO pago_cliente (id_proyecto, monto, metodo, fecha, id_usuario) '
            . 'VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->execute([$idProyecto, $monto, $metodo, $fecha, $idUsuario]);

        return (int) $this->db->lastInsertId();
    }

}
