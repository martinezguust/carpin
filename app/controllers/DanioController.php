<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/DanioController.php
 * Reportes de material o herramienta dañada (HU-03, RF-40):
 *   - Carpintero, Lijador y Pintor reportan (SRS 5.4) y ven
 *     sus propios reportes;
 *   - Carpintero y Contador ven todos y cambian el estado:
 *     Reportado → En atención → Resuelto (solo avanza).
 * =====================================================
 */

class DanioController extends Controller {

    /* ===== Roles ===== */
    private const ROLES_REPORTAN = [Auth::CARPINTERO, Auth::LIJADOR, Auth::PINTOR];
    private const ROLES_ATIENDEN = [Auth::CARPINTERO, Auth::CONTADOR];

    /**
     * Muestra todos los reportes con filtro por estado.
     *
     * Recibe: nada (usa $_GET['estado'], opcional).
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireRol(self::ROLES_ATIENDEN);

        $estado = (string) ($_GET['estado'] ?? '');

        if (!isset(ReporteDanio::ESTADOS[$estado])) {
            $estado = '';
        }

        $this->vista('comun/danios', [
            'reportes' => (new ReporteDanio())->listar($estado, null),
            'estado'   => $estado,
        ], 'panel');
    }

    /**
     * Muestra el formulario para reportar un daño y los reportes propios.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function reportar(): void {
        Auth::requireRol(self::ROLES_REPORTAN);

        $datos = [
            'elemento'    => '',
            'tipo'        => '',
            'descripcion' => '',
            'accion'      => '',
        ];

        $this->mostrarFormulario($datos, []);
    }

    /**
     * Guarda el reporte con su foto opcional y avisa al Carpintero y al
     * Contador (SRS 5.2).
     *
     * Recibe: nada (usa $_POST y $_FILES['foto']).
     * Devuelve: nada.
     */
    public function guardar(): void {
        Auth::requireRol(self::ROLES_REPORTAN);

        $datos = [
            'elemento'    => $this->textoPost('elemento'),
            'tipo'        => $this->textoPost('tipo'),
            'descripcion' => $this->textoPost('descripcion'),
            'accion'      => $this->textoPost('accion'),
        ];

        $errores = $this->validar($datos);
        $rutaFoto = null;

        // Foto opcional (JPG, PNG o WEBP de máximo 5 MB)
        $foto = $_FILES['foto'] ?? null;
        $hayFoto = $foto !== null && ($foto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($errores === [] && $hayFoto) {
            try {
                $rutaFoto = Upload::guardar($foto, 'danios', Upload::IMAGENES);
            } catch (RuntimeException $error) {
                $errores['foto'] = $error->getMessage();
            }
        }

        if ($errores !== []) {
            $this->mostrarFormulario($datos, $errores);
            return;
        }

        $idReporte = (new ReporteDanio())->crear([
            'elemento'           => $datos['elemento'],
            'tipo'               => $datos['tipo'],
            'descripcion'        => $datos['descripcion'],
            'accion'             => $datos['accion'],
            'foto_ruta'          => $rutaFoto,
            'id_usuario_reporta' => (int) Auth::id(),
        ]);

        // SRS 5.2: reporte de material o herramienta dañada → Carpintero y Contador
        $usuario = Auth::usuario();
        $mensaje = 'Reporte de daño: ' . $datos['elemento']
            . ' (' . mb_strtolower(ReporteDanio::ACCIONES[$datos['accion']]) . ')'
            . ', de ' . $usuario['nombre'];

        NotificacionService::notificar(
            array_merge(
                NotificacionService::usuariosDeRol(Auth::CARPINTERO),
                NotificacionService::usuariosDeRol(Auth::CONTADOR)
            ),
            'DANIO',
            $mensaje,
            '/danios#reporte' . $idReporte,
            (int) Auth::id()
        );

        Flash::agregar('exito', 'Reporte enviado. Puedes seguir su estado en "Mis reportes".');
        $this->redirigir('/danios/reportar');
    }

    /**
     * Pasa el reporte a su siguiente estado (respuesta 9: solo avanza).
     *
     * Recibe: $id, id del reporte (segmento de la ruta).
     * Devuelve: nada.
     */
    public function avanzarEstado(string $id): void {
        Auth::requireRol(self::ROLES_ATIENDEN);

        $reportes = new ReporteDanio();
        $reporte = $reportes->buscarPorId($this->idDeRuta($id));

        if ($reporte === null) {
            View::error(404);
        }

        $siguiente = ReporteDanio::siguienteEstado($reporte['estado']);

        if ($siguiente === null) {
            Flash::agregar('error', 'El reporte ya está resuelto.');
        } else {
            $reportes->cambiarEstado((int) $reporte['id_reporte'], $siguiente);
            Flash::agregar(
                'exito',
                "\"{$reporte['elemento']}\" quedó como \""
                    . ReporteDanio::ESTADOS[$siguiente] . '".'
            );
        }

        $this->redirigir('/danios');
    }

    /* ===== Funciones internas ===== */

    /**
     * Dibuja el formulario de reporte con la lista de reportes propios.
     *
     * Recibe: $datos, valores del formulario; $errores.
     * Devuelve: nada.
     */
    private function mostrarFormulario(array $datos, array $errores): void {
        $this->vista('comun/danio_reportar', [
            'datos'    => $datos,
            'errores'  => $errores,
            'propios'  => (new ReporteDanio())->listar('', (int) Auth::id()),
            'puedeVerTodos' => Auth::rol() === Auth::CARPINTERO,
        ], 'panel');
    }

    /**
     * Valida los datos del reporte (RF-40).
     *
     * Recibe: $datos, valores capturados.
     * Devuelve: errores por campo.
     */
    private function validar(array $datos): array {
        $errores = [];

        if ($datos['elemento'] === '' || mb_strlen($datos['elemento']) > 100) {
            $errores['elemento'] = 'Escribe qué material o herramienta se dañó '
                . '(máximo 100 caracteres).';
        }

        if (!isset(ReporteDanio::TIPOS[$datos['tipo']])) {
            $errores['tipo'] = 'Elige si es material o herramienta.';
        }

        if ($datos['descripcion'] === '' || mb_strlen($datos['descripcion']) > 2000) {
            $errores['descripcion'] = 'Describe el daño (máximo 2000 caracteres).';
        }

        if (!isset(ReporteDanio::ACCIONES[$datos['accion']])) {
            $errores['accion'] = 'Elige si se debe reparar o reemplazar.';
        }

        return $errores;
    }

}
