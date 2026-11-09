<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/AsistenciaController.php
 * Asistencia (HU-04, RF-42, RF-47, RN-09, CU-09):
 *   - el Contador captura las tarjetas de entrada y salida de
 *     una fecha; las horas se calculan solas;
 *   - "Eliminar" quita una tarjeta capturada por error
 *     (respuesta 12);
 *   - reporte de horas por trabajador en un periodo
 *     (Contador y Carpintero).
 * =====================================================
 */

class AsistenciaController extends Controller {

    /* ===== Formato de las horas capturadas ===== */
    private const PATRON_HORA = '/^([01][0-9]|2[0-3]):[0-5][0-9]$/';

    /**
     * Muestra la captura de tarjetas de una fecha (CU-09).
     *
     * Recibe: nada (usa $_GET['fecha'], por omisión hoy).
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireRol([Auth::CONTADOR]);

        $fecha = $this->fechaElegida((string) ($_GET['fecha'] ?? ''));

        $this->mostrarCaptura($fecha, [], []);
    }

    /**
     * Guarda las tarjetas de una fecha. Cada renglón se valida por
     * separado: los renglones con error se marcan y los demás se guardan
     * (CU-09, A1). Si ya existía la tarjeta, se actualiza (A2).
     *
     * Recibe: nada (usa $_POST: fecha, entrada[id_trabajador],
     *         salida[id_trabajador]).
     * Devuelve: nada.
     */
    public function guardar(): void {
        Auth::requireRol([Auth::CONTADOR]);

        $fechaTexto = $this->textoPost('fecha');

        if (!$this->fechaValida($fechaTexto) || $fechaTexto > date('Y-m-d')) {
            Flash::agregar('error', 'Elige una fecha válida que no sea futura.');
            $this->redirigir('/asistencia');
        }

        $entradas = is_array($_POST['entrada'] ?? null) ? $_POST['entrada'] : [];
        $salidas = is_array($_POST['salida'] ?? null) ? $_POST['salida'] : [];

        $asistencias = new Asistencia();
        $existentes = $asistencias->listarPorFecha($fechaTexto);
        $errores = [];
        $capturados = [];
        $guardados = 0;

        foreach ($this->trabajadoresDeTarjeta() as $trabajador) {
            $idTrabajador = (int) $trabajador['id_trabajador'];
            $entrada = trim((string) ($entradas[$idTrabajador] ?? ''));
            $salida = trim((string) ($salidas[$idTrabajador] ?? ''));
            $capturados[$idTrabajador] = ['entrada' => $entrada, 'salida' => $salida];

            // Renglón vacío: no se captura nada (para quitar una tarjeta se usa "Eliminar")
            if ($entrada === '' && $salida === '') {
                if (isset($existentes[$idTrabajador])) {
                    $errores[$idTrabajador] = 'Para quitar esta tarjeta usa el botón "Eliminar".';
                }

                continue;
            }

            $error = $this->validarRenglon($entrada, $salida);

            if ($error !== null) {
                $errores[$idTrabajador] = $error;
                continue;
            }

            $asistencias->guardar(
                $idTrabajador,
                $fechaTexto,
                $entrada,
                $salida,
                $this->horasTrabajadas($entrada, $salida),
                (int) Auth::id()
            );
            $guardados++;
        }

        if ($guardados > 0) {
            Flash::agregar(
                'exito',
                "Se guardaron {$guardados} tarjeta(s) del " . fecha($fechaTexto) . '.'
            );
        }

        if ($errores !== []) {
            Flash::agregar('error', 'Revisa los renglones marcados en rojo; no se guardaron.');
            $this->mostrarCaptura($fechaTexto, $errores, $capturados);
            return;
        }

        $this->redirigir('/asistencia?fecha=' . $fechaTexto);
    }

    /**
     * Elimina una tarjeta capturada por error (respuesta 12).
     *
     * Recibe: $id, id de la tarjeta (segmento de la ruta).
     * Devuelve: nada.
     */
    public function eliminar(string $id): void {
        Auth::requireRol([Auth::CONTADOR]);

        $asistencias = new Asistencia();
        $tarjeta = $asistencias->buscarPorId($this->idDeRuta($id));

        if ($tarjeta === null) {
            View::error(404);
        }

        $asistencias->eliminar((int) $tarjeta['id_asistencia']);
        Flash::agregar('exito', 'La tarjeta se eliminó.');

        $this->redirigir('/asistencia?fecha=' . $tarjeta['fecha']);
    }

    /**
     * Reporte de horas por trabajador en un periodo (RF-47).
     *
     * Recibe: nada (usa $_GET: desde y hasta; por omisión el mes actual).
     * Devuelve: nada.
     */
    public function reporteHoras(): void {
        Auth::requireRol([Auth::CONTADOR, Auth::CARPINTERO]);

        $desde = (string) ($_GET['desde'] ?? '');
        $hasta = (string) ($_GET['hasta'] ?? '');

        if (!$this->fechaValida($desde)) {
            $desde = date('Y-m-01');
        }

        if (!$this->fechaValida($hasta)) {
            $hasta = date('Y-m-d');
        }

        $error = null;

        if ($desde > $hasta) {
            $error = 'La fecha "desde" no puede ser posterior a "hasta".';
        }

        $filas = ($error === null) ? (new Asistencia())->resumenPorPeriodo($desde, $hasta) : [];

        $this->vista('comun/reporte_horas', [
            'filas' => $filas,
            'desde' => $desde,
            'hasta' => $hasta,
            'error' => $error,
        ], 'panel');
    }

    /* ===== Funciones internas ===== */

    /**
     * Dibuja la captura de tarjetas de una fecha.
     *
     * Recibe: $fecha; $errores, por id_trabajador; $capturados, valores
     *         enviados por id_trabajador (para no perderlos si hubo error).
     * Devuelve: nada.
     */
    private function mostrarCaptura(string $fecha, array $errores, array $capturados): void {
        $this->vista('contador/asistencia', [
            'fecha'        => $fecha,
            'trabajadores' => $this->trabajadoresDeTarjeta(),
            'existentes'   => (new Asistencia())->listarPorFecha($fecha),
            'errores'      => $errores,
            'capturados'   => $capturados,
        ], 'panel');
    }

    /**
     * Trabajadores que aparecen en la tarjeta: todos los activos menos el
     * Administrador (respuesta 11).
     *
     * Recibe: nada.
     * Devuelve: lista de trabajadores con su rol.
     */
    private function trabajadoresDeTarjeta(): array {
        $trabajadores = (new Trabajador())->listarConFormaPago();

        return array_values(array_filter(
            $trabajadores,
            fn ($trabajador) => (int) $trabajador['activo'] === 1
                && (int) $trabajador['cuenta_activa'] === 1
        ));
    }

    /**
     * Devuelve la fecha pedida si es válida y no futura; si no, hoy.
     *
     * Recibe: $texto, fecha "aaaa-mm-dd".
     * Devuelve: fecha válida.
     */
    private function fechaElegida(string $texto): string {
        if ($this->fechaValida($texto) && $texto <= date('Y-m-d')) {
            return $texto;
        }

        return date('Y-m-d');
    }

    /**
     * Valida un renglón de la tarjeta (RN-09, CU-09 A1).
     *
     * Recibe: $entrada y $salida, "hh:mm".
     * Devuelve: mensaje de error, o null si es válido.
     */
    private function validarRenglon(string $entrada, string $salida): ?string {
        if ($entrada === '' || $salida === '') {
            return 'Captura la hora de entrada y la de salida.';
        }

        $horasValidas = preg_match(self::PATRON_HORA, $entrada) === 1
            && preg_match(self::PATRON_HORA, $salida) === 1;

        if (!$horasValidas) {
            return 'Escribe las horas con formato válido.';
        }

        if ($salida <= $entrada) {
            return 'La salida debe ser mayor que la entrada.';
        }

        return null;
    }

    /**
     * Calcula las horas trabajadas: salida − entrada (RN-09).
     *
     * Recibe: $entrada y $salida, "hh:mm".
     * Devuelve: horas con 2 decimales, ej. "8.50".
     */
    private function horasTrabajadas(string $entrada, string $salida): string {
        [$horaEntrada, $minutoEntrada] = array_map('intval', explode(':', $entrada));
        [$horaSalida, $minutoSalida] = array_map('intval', explode(':', $salida));

        $minutos = ($horaSalida * 60 + $minutoSalida) - ($horaEntrada * 60 + $minutoEntrada);

        return number_format($minutos / 60, 2, '.', '');
    }

}
