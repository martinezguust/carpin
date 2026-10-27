<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/ConfigController.php
 * Datos de la carpintería por el Administrador (HU-21,
 * RF-13): nombre, "Quiénes somos", dirección, horario,
 * teléfono, WhatsApp, mapa y logo. Los cambios se ven en
 * todo el sistema.
 * =====================================================
 */

class ConfigController extends Controller {

    /* ===== Inicio válido de la URL del mapa (respuesta 12) ===== */
    private const PREFIJO_MAPA = 'https://www.google.com/maps/embed';

    /**
     * Muestra el formulario de datos de la carpintería y del logo.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function editar(): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $negocio = (new Configuracion())->obtener();

        $this->mostrar($this->datosDeConfiguracion($negocio), []);
    }

    /**
     * Guarda los datos de la carpintería (RF-13).
     *
     * Recibe: nada (usa $_POST).
     * Devuelve: nada.
     */
    public function guardar(): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $campos = [
            'nombre_negocio', 'descripcion', 'direccion', 'horario',
            'telefono', 'whatsapp', 'mensaje_whatsapp', 'mapa_url',
        ];
        $datos = [];

        foreach ($campos as $campo) {
            $datos[$campo] = $this->textoPost($campo);
        }

        $errores = $this->validar($datos);

        if ($errores !== []) {
            $this->mostrar($datos, $errores);
            return;
        }

        // Los campos opcionales vacíos se guardan como null
        $paraGuardar = [];

        foreach ($datos as $campo => $valor) {
            $paraGuardar[$campo] = ($valor === '' && $campo !== 'nombre_negocio') ? null : $valor;
        }

        (new Configuracion())->actualizarDatos($paraGuardar);

        Flash::agregar('exito', 'Los datos de la carpintería se guardaron.');
        $this->redirigir('/admin/configuracion');
    }

    /**
     * Cambia el logo por una imagen nueva (JPG, PNG o WEBP de máximo
     * 5 MB) y borra la anterior.
     *
     * Recibe: nada (usa $_FILES['logo']).
     * Devuelve: nada.
     */
    public function cambiarLogo(): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        try {
            $ruta = Upload::guardar($_FILES['logo'] ?? [], 'logo', Upload::IMAGENES);
        } catch (RuntimeException $error) {
            Flash::agregar('error', 'Logo: ' . $error->getMessage());
            $this->redirigir('/admin/configuracion');
        }

        $configuracion = new Configuracion();
        $anterior = $configuracion->obtener()['logo_ruta'] ?? null;

        $configuracion->actualizarLogo($ruta);
        $this->borrarLogo($anterior);

        Flash::agregar('exito', 'El logo se cambió en todo el sistema.');
        $this->redirigir('/admin/configuracion');
    }

    /**
     * Quita el logo y vuelve al logo provisional (respuesta 12).
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function quitarLogo(): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $configuracion = new Configuracion();
        $anterior = $configuracion->obtener()['logo_ruta'] ?? null;

        $configuracion->actualizarLogo(null);
        $this->borrarLogo($anterior);

        Flash::agregar('exito', 'Se quitó el logo: se muestra el logo provisional "Carpin".');
        $this->redirigir('/admin/configuracion');
    }

    /* ===== Funciones internas ===== */

    /**
     * Dibuja la pantalla de configuración.
     *
     * Recibe: $datos, valores del formulario; $errores, errores por campo.
     * Devuelve: nada.
     */
    private function mostrar(array $datos, array $errores): void {
        $this->vista('admin/configuracion', [
            'datos'   => $datos,
            'errores' => $errores,
            'negocio' => (new Configuracion())->obtener(),
        ], 'panel');
    }

    /**
     * Convierte la fila de configuración en valores del formulario.
     *
     * Recibe: $negocio, fila de la tabla configuracion.
     * Devuelve: valores como texto ("" para los nulos).
     */
    private function datosDeConfiguracion(array $negocio): array {
        $campos = [
            'nombre_negocio', 'descripcion', 'direccion', 'horario',
            'telefono', 'whatsapp', 'mensaje_whatsapp', 'mapa_url',
        ];
        $datos = [];

        foreach ($campos as $campo) {
            $datos[$campo] = (string) ($negocio[$campo] ?? '');
        }

        return $datos;
    }

    /**
     * Valida los datos de la carpintería (respuesta 12 de la Fase 2).
     *
     * Recibe: $datos, valores capturados.
     * Devuelve: errores por campo.
     */
    private function validar(array $datos): array {
        $errores = [];

        if ($datos['nombre_negocio'] === '' || mb_strlen($datos['nombre_negocio']) > 100) {
            $errores['nombre_negocio'] = 'Escribe el nombre de la carpintería '
                . '(máximo 100 caracteres).';
        }

        if (mb_strlen($datos['descripcion']) > 2000) {
            $errores['descripcion'] = '"Quiénes somos" no puede pasar de 2000 caracteres.';
        }

        if (mb_strlen($datos['direccion']) > 255) {
            $errores['direccion'] = 'La dirección no puede pasar de 255 caracteres.';
        }

        if (mb_strlen($datos['horario']) > 150) {
            $errores['horario'] = 'El horario no puede pasar de 150 caracteres.';
        }

        if ($datos['telefono'] !== '' && Usuario::validarTelefono($datos['telefono']) !== null) {
            $errores['telefono'] = 'El teléfono debe tener exactamente 10 dígitos, '
                . 'o déjalo vacío.';
        }

        if ($datos['whatsapp'] !== '' && preg_match('/^[0-9]{12,15}$/', $datos['whatsapp']) !== 1) {
            $errores['whatsapp'] = 'Escribe el número en formato internacional, solo dígitos '
                . '(12 a 15), por ejemplo 5212281234567.';
        }

        if (mb_strlen($datos['mensaje_whatsapp']) > 255) {
            $errores['mensaje_whatsapp'] = 'El mensaje no puede pasar de 255 caracteres.';
        }

        $mapa = $datos['mapa_url'];
        $mapaValido = str_starts_with($mapa, self::PREFIJO_MAPA)
            && filter_var($mapa, FILTER_VALIDATE_URL) !== false
            && mb_strlen($mapa) <= 2000;

        if ($mapa !== '' && !$mapaValido) {
            $errores['mapa_url'] = 'Pega la dirección "Insertar un mapa" de Google Maps; debe '
                . 'empezar con ' . self::PREFIJO_MAPA . '.';
        }

        return $errores;
    }

    /**
     * Borra del disco un logo que ya no se usa.
     *
     * Recibe: $ruta, ruta relativa a public/ (o null).
     * Devuelve: nada.
     */
    private function borrarLogo(?string $ruta): void {
        if ($ruta === null || !str_starts_with($ruta, 'uploads/logo/')) {
            return;
        }

        $archivo = RAIZ . '/public/' . $ruta;

        if (is_file($archivo)) {
            unlink($archivo);
        }
    }

}
