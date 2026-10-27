<?php
/*
 * =====================================================
 * Carpintería Carpin – app/services/FolioService.php
 * Genera los folios consecutivos por año (SRS 14.3, regla 11):
 * PRY-AAAA-NNNN, REP-AAAA-NNNN, PRE-AAAA-NNNN y COM-AAAA-NNNN.
 * Debe llamarse dentro de una transacción: bloquea el último
 * folio del año para que dos registros no tomen el mismo.
 * =====================================================
 */

final class FolioService {

    /* ===== Prefijo → tabla donde se guarda el folio ===== */
    private const TABLA_POR_PREFIJO = [
        'PRY' => 'proyecto',
        'REP' => 'reparacion',
        'PRE' => 'presupuesto',
        'COM' => 'comprobante_cliente',
    ];

    /**
     * Calcula el siguiente folio del año actual para un prefijo.
     *
     * Recibe: $prefijo, "PRY", "REP", "PRE" o "COM".
     * Devuelve: folio nuevo, ej. "PRY-2026-0003".
     */
    public static function siguiente(string $prefijo): string {
        if (!isset(self::TABLA_POR_PREFIJO[$prefijo])) {
            throw new InvalidArgumentException("Prefijo de folio no válido: {$prefijo}");
        }

        $tabla = self::TABLA_POR_PREFIJO[$prefijo];
        $inicio = $prefijo . '-' . date('Y') . '-';

        // El nombre de la tabla viene de la lista fija de arriba
        $consulta = Database::get()->prepare(
            "SELECT folio FROM {$tabla} WHERE folio LIKE ? ORDER BY folio DESC LIMIT 1 FOR UPDATE"
        );
        $consulta->execute([$inicio . '%']);
        $ultimo = $consulta->fetchColumn();

        $consecutivo = 1;

        if ($ultimo !== false) {
            $consecutivo = (int) substr($ultimo, strlen($inicio)) + 1;
        }

        return $inicio . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

}
