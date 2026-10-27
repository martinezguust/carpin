<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/ProduccionController.php
 * Pantallas de producción del Lijador y del Pintor:
 *   - "Mis pendientes" con el botón de avance de fase
 *     (HU-01, HU-05, HU-10, RF-19, RF-20);
 *   - granulado de lija (HU-02) y ficha de color (HU-06);
 *   - registro de capas (HU-08, RF-26) y programación del
 *     tiempo de secado (HU-09, RF-27), solo en "Pintado".
 * =====================================================
 */

class ProduccionController extends Controller {

    /* ===== Mensajes de espera de los mockups 6 y 7 (respuesta 3) ===== */
    private const ESPERA_LIJADOR = 'Esperando: el carpintero termina el ensamblado. Te avisaremos.';
    private const ESPERA_PINTOR = 'Esperando: el lijador termina. '
        . 'Te avisaremos cuando esté listo para pintura.';
    private const ESPERA_TERMINADA = 'Tu parte está terminada.';

    /* ===== Límites de las columnas ===== */
    private const MAX_HORAS_SECADO = 999.99;   // DECIMAL(5,2)
    private const MAX_CAPAS = 255;             // TINYINT UNSIGNED

    /**
     * Muestra las actividades pendientes del trabajador, ordenadas por
     * fecha de entrega (RF-19), con lo que necesita para trabajar y el
     * botón para avanzar cuando le toca (RN-02).
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function misPendientes(): void {
        Auth::requireRol([Auth::LIJADOR, Auth::PINTOR]);

        $trabajador = $this->trabajadorActual();
        $esLijador = Auth::rol() === Auth::LIJADOR;
        $pendientes = (new Actividad())->pendientesDeTrabajador((int) $trabajador['id_trabajador']);
        $tarjetas = [];

        foreach ($pendientes as $pendiente) {
            $tarjetas[] = $this->armarTarjeta($pendiente, $esLijador);
        }

        $this->vista($esLijador ? 'lijador/pendientes' : 'pintor/pendientes', [
            'trabajador' => $trabajador,
            'tarjetas'   => $tarjetas,
        ], 'panel');
    }

    /**
     * Registra una capa aplicada al proyecto (RF-26). Solo el Pintor
     * asignado y solo en la fase "Pintado" (respuesta 26 de la Fase 1).
     *
     * Recibe: $id, id del proyecto (segmento de la ruta);
     *         $_POST: producto y observaciones.
     * Devuelve: nada (redirige a "Mis pendientes").
     */
    public function registrarCapa(string $id): void {
        Auth::requireRol([Auth::PINTOR]);

        $proyecto = $this->proyectoEnPintado($this->idDeRuta($id));
        $idProyecto = (int) $proyecto['id_proyecto'];
        $producto = $this->textoPost('producto');
        $observaciones = $this->textoPost('observaciones');
        $capas = new CapaPintura();

        $error = null;

        if ($producto === '' || mb_strlen($producto) > 80) {
            $error = 'Escribe el producto usado (máximo 80 caracteres).';
        } elseif (mb_strlen($observaciones) > 255) {
            $error = 'Las observaciones no pueden pasar de 255 caracteres.';
        } elseif ($capas->contar($idProyecto) >= self::MAX_CAPAS) {
            $error = 'El proyecto ya llegó al máximo de capas que se pueden registrar.';
        }

        if ($error !== null) {
            Flash::agregar('error', $error);
            $this->redirigir('/mis-pendientes');
        }

        $trabajador = $this->trabajadorActual();
        $numero = $capas->registrar(
            $idProyecto,
            $producto,
            ($observaciones !== '') ? $observaciones : null,
            (int) $trabajador['id_trabajador']
        );

        Flash::agregar('exito', "Capa {$numero} registrada en {$proyecto['folio']}.");
        $this->redirigir('/mis-pendientes');
    }

    /**
     * Programa el tiempo de secado (RF-27): hora de inicio y horas; el
     * sistema calcula la hora estimada de término. Solo el Pintor asignado
     * y solo en la fase "Pintado".
     *
     * Recibe: $id, id del proyecto (segmento de la ruta);
     *         $_POST: inicio ("aaaa-mm-ddThh:mm") y horas.
     * Devuelve: nada (redirige a "Mis pendientes").
     */
    public function programarSecado(string $id): void {
        Auth::requireRol([Auth::PINTOR]);

        $proyecto = $this->proyectoEnPintado($this->idDeRuta($id));
        $idProyecto = (int) $proyecto['id_proyecto'];
        $inicioTexto = $this->textoPost('inicio');
        $horasTexto = $this->textoPost('horas');

        $inicio = DateTime::createFromFormat('Y-m-d\TH:i', $inicioTexto);
        $inicioMinimo = FaseService::inicioMinimoSecado($idProyecto);
        $error = null;

        if ($inicio === false || $inicio->format('Y-m-d\TH:i') !== $inicioTexto) {
            $error = 'Escribe la fecha y hora de inicio del secado.';
        } elseif ($inicioMinimo !== null && $inicio->format('Y-m-d H:i:00') < $inicioMinimo) {
            $error = 'El inicio no puede ser anterior a cuando el mueble pasó a "Pintado" ('
                . fechaHora($inicioMinimo) . ').';
        } elseif (!is_numeric($horasTexto)
            || (float) $horasTexto <= 0
            || (float) $horasTexto > self::MAX_HORAS_SECADO) {
            $error = 'Las horas de secado deben ser un número mayor a 0 (máximo 999.99).';
        }

        if ($error !== null) {
            Flash::agregar('error', $error);
            $this->redirigir('/mis-pendientes');
        }

        // Fin estimado = inicio + horas (RF-27)
        $horas = round((float) $horasTexto, 2);
        $segundos = (int) round($horas * 3600);
        $fin = (clone $inicio)->modify('+' . $segundos . ' seconds');

        (new Secado())->programar(
            $idProyecto,
            $inicio->format('Y-m-d H:i:00'),
            number_format($horas, 2, '.', ''),
            $fin->format('Y-m-d H:i:s')
        );

        Flash::agregar(
            'exito',
            'Secado programado: termina el ' . fechaHora($fin->format('Y-m-d H:i:s'))
        );
        $this->redirigir('/mis-pendientes');
    }

    /* ===== Funciones internas ===== */

    /**
     * Arma los datos de una tarjeta de "Mis pendientes".
     *
     * Recibe: $pendiente, actividad con los datos de su proyecto;
     *         $esLijador, true para el Lijador y false para el Pintor.
     * Devuelve: arreglo con la actividad, la fase siguiente (si le toca),
     *           el mensaje de espera y los datos de pintura.
     */
    private function armarTarjeta(array $pendiente, bool $esLijador): array {
        $idProyecto = (int) $pendiente['id_proyecto'];
        $idFase = (int) $pendiente['id_fase'];
        $siguiente = FaseService::siguienteFasePermitida($pendiente, Auth::usuario());

        // Mensaje cuando todavía no le toca o ya terminó su parte
        $espera = null;

        if ($siguiente === null) {
            $faseInicioPropia = $esLijador ? Fase::ENSAMBLADO : Fase::LISTO_PARA_PINTURA;
            $espera = $esLijador ? self::ESPERA_LIJADOR : self::ESPERA_PINTOR;

            if ($idFase >= $faseInicioPropia) {
                $espera = self::ESPERA_TERMINADA;
            }
        }

        $tarjeta = [
            'actividad' => $pendiente,
            'siguiente' => $siguiente,
            'espera'    => $espera,
        ];

        if ($esLijador) {
            return $tarjeta;
        }

        // Datos del Pintor: ficha de color, capas y secado
        $secado = FaseService::secadoVigente($idProyecto);
        $idSiguiente = ($siguiente !== null) ? (int) $siguiente['id_fase'] : 0;

        $tarjeta['ficha'] = Proyecto::separarFichaColor($pendiente['ficha_color']);
        $tarjeta['capas'] = (new CapaPintura())->contar($idProyecto);
        $tarjeta['secado'] = $secado;
        $tarjeta['enPintado'] = $idFase === Fase::PINTADO;
        $tarjeta['faltaSecado'] = $idSiguiente === Fase::SECADO && $secado === null;
        $tarjeta['secadoSinTerminar'] = $idSiguiente === Fase::LISTO_PARA_ENTREGA
            && $secado !== null
            && strtotime($secado['fin_estimado']) > time();

        return $tarjeta;
    }

    /**
     * Devuelve el trabajador de la cuenta con sesión.
     *
     * Recibe: nada.
     * Devuelve: datos del trabajador (403 si la cuenta no es de trabajador).
     */
    private function trabajadorActual(): array {
        $trabajador = (new Trabajador())->buscarPorUsuario((int) Auth::id());

        if ($trabajador === null) {
            View::error(403);
        }

        return $trabajador;
    }

    /**
     * Busca el proyecto y revisa que el Pintor tenga una actividad de
     * Pintura en él (403 si no) y que esté en la fase "Pintado".
     *
     * Recibe: $idProyecto.
     * Devuelve: datos del proyecto (redirige con error si no está en "Pintado").
     */
    private function proyectoEnPintado(int $idProyecto): array {
        $proyecto = (new Proyecto())->buscarPorId($idProyecto);

        if ($proyecto === null) {
            View::error(404);
        }

        $asignados = (new Actividad())->usuariosAsignados($idProyecto, 'PINTURA');

        if (!in_array((int) Auth::id(), $asignados, true)) {
            View::error(403);
        }

        if ((int) $proyecto['id_fase'] !== Fase::PINTADO) {
            Flash::agregar(
                'error',
                'Las capas y el secado solo se registran cuando el mueble está en "Pintado".'
            );
            $this->redirigir('/mis-pendientes');
        }

        return $proyecto;
    }

}
