<?php
/*
 * =====================================================
 * Carpintería Carpin – app/services/PagoService.php
 * Reglas de saldo y status de pago (RN-15 y RN-16).
 * Fase 1: se usa para el anticipo al registrar un proyecto
 * y para mostrar el saldo. El registro de pagos del
 * Contador llega en la Fase 3.
 * =====================================================
 */

final class PagoService {

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
