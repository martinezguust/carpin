<?php
/*
 * =====================================================
 * Carpintería Carpin – app/services/PagoService.php
 * Reglas de saldo y status de pago (RN-15 y RN-16) y
 * registro de pagos de clientes por el Contador (HU-27,
 * RF-48, RF-49, CU-11). El sistema NO cobra: solo registra
 * pagos hechos en persona.
 * =====================================================
 */

final class PagoService {

    /**
     * Registra un pago de un proyecto o de una reparación y recalcula su
     * status (CU-11). El monto debe ser mayor a 0 y no pasar del saldo
     * (RN-15). Todo va en una transacción con la fila bloqueada.
     *
     * Recibe: $tipo, "PRY" o "REP"; $id; $monto, texto con 2 decimales;
     *         $metodo; $fecha, "aaaa-mm-dd"; $referencia, o null;
     *         $idUsuario, quien lo registra.
     * Devuelve: el status nuevo. Lanza DomainException si no se puede.
     */
    public static function registrarPago(
        string $tipo,
        int $id,
        string $monto,
        string $metodo,
        string $fecha,
        ?string $referencia,
        int $idUsuario
    ): string {
        $modelo = ($tipo === 'REP') ? new Reparacion() : new Proyecto();
        $db = Database::get();

        try {
            $db->beginTransaction();

            $cuenta = $modelo->bloquearParaPago($id);

            if ($cuenta === null) {
                throw new DomainException('El pedido no existe.');
            }

            $saldo = self::saldo($cuenta['importe'], $cuenta['pagado']);
            $montoCentavos = self::aCentavos($monto);

            // CU-11, A2 y A1
            if ($montoCentavos <= 0) {
                throw new DomainException('El monto debe ser mayor a $0.00.');
            }

            if ($montoCentavos > self::aCentavos($saldo)) {
                throw new DomainException(
                    'El pago supera el saldo pendiente de ' . dinero($saldo) . '.'
                );
            }

            (new PagoCliente())->registrar(
                $tipo,
                $id,
                $monto,
                $metodo,
                $fecha,
                $referencia,
                $idUsuario
            );

            $nuevoPagado = (self::aCentavos($cuenta['pagado']) + $montoCentavos) / 100;
            $status = self::status($cuenta['importe'], $nuevoPagado);
            $modelo->actualizarStatusPago($id, $status);

            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $error;
        }

        return $status;
    }

    /**
     * Indica si un saldo está vencido: saldo > 0 y fecha límite de pago
     * anterior a hoy (RN-16).
     *
     * Recibe: $saldo; $fechaLimite, "aaaa-mm-dd".
     * Devuelve: true si está vencido.
     */
    public static function estaVencido(mixed $saldo, string $fechaLimite): bool {
        return self::aCentavos($saldo) > 0 && $fechaLimite < date('Y-m-d');
    }

    /**
     * Convierte un monto a centavos para comparar sin errores de redondeo.
     *
     * Recibe: $monto, número o texto numérico.
     * Devuelve: monto en centavos (entero).
     */
    public static function aCentavos(mixed $monto): int {
        return (int) round(((float) $monto) * 100);
    }

    /**
     * Calcula el saldo pendiente: precio − Σ pagos (RN-15).
     *
     * Recibe: $precio; $pagado, suma de pagos.
     * Devuelve: saldo con 2 decimales (nunca negativo).
     */
    public static function saldo(mixed $precio, mixed $pagado): float {
        $saldoCentavos = self::aCentavos($precio) - self::aCentavos($pagado);

        return max(0, $saldoCentavos) / 100;
    }

    /**
     * Determina el status de pago (RN-15): Pendiente (sin pagos),
     * Con anticipo (0 < pagos < precio) o Pagado (pagos ≥ precio).
     *
     * Recibe: $precio; $pagado, suma de pagos.
     * Devuelve: "PENDIENTE", "CON_ANTICIPO" o "PAGADO".
     */
    public static function status(mixed $precio, mixed $pagado): string {
        $pagadoCentavos = self::aCentavos($pagado);

        if ($pagadoCentavos <= 0) {
            return 'PENDIENTE';
        }

        if ($pagadoCentavos >= self::aCentavos($precio)) {
            return 'PAGADO';
        }

        return 'CON_ANTICIPO';
    }

}
