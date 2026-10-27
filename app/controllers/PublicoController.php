<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/PublicoController.php
 * Página pública sin inicio de sesión (HU-20, HU-24,
 * SRS 10.2): inicio, catálogo, "Nosotros", "Contacto" y
 * formulario para solicitar cotización (CU-07).
 * =====================================================
 */

class PublicoController extends Controller {

    /**
     * Muestra la página de inicio: portada, productos destacados,
     * "Quiénes somos" y servicios (RF-08).
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function inicio(): void {
        $this->vista('publico/inicio', [
            'destacados' => (new ProductoCatalogo())->listarDestacados(),
            'negocio'    => (new Configuracion())->obtener(),
        ], 'publico');
    }

    /**
     * Muestra el catálogo con filtro por categoría (RF-09).
     *
     * Recibe: nada (usa $_GET['categoria'], opcional).
     * Devuelve: nada.
     */
    public function catalogo(): void {
        $categoria = (string) ($_GET['categoria'] ?? '');
        $idCategoria = ctype_digit($categoria) ? (int) $categoria : 0;

        $this->vista('publico/catalogo', [
            'productos'   => (new ProductoCatalogo())->listarVisibles($idCategoria),
            'categorias'  => (new CategoriaCatalogo())->todas(),
            'idCategoria' => $idCategoria,
        ], 'publico');
    }

    /**
     * Muestra "Nosotros": quiénes somos, misión, visión, valores y
     * servicios (cambio pedido por el Product Owner el 27/10/2026).
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function nosotros(): void {
        $this->vista('publico/nosotros', [
            'negocio' => (new Configuracion())->obtener(),
        ], 'publico');
    }

    /**
     * Muestra "Contacto" en su propia página: dirección, horario,
     * teléfono, mapa, WhatsApp y el botón "Solicitar cotización".
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function contacto(): void {
        $negocio = (new Configuracion())->obtener();

        $this->vista('publico/contacto', [
            'negocio'        => $negocio,
            'enlaceWhatsapp' => Configuracion::enlaceWhatsapp($negocio),
        ], 'publico');
    }

    /**
     * Muestra el formulario "Solicitar cotización" (RF-11). Si llega con
     * ?producto=id desde el catálogo, el producto queda elegido.
     *
     * Recibe: nada (usa $_GET['producto'], opcional).
     * Devuelve: nada.
     */
    public function cotizacion(): void {
        $producto = (string) ($_GET['producto'] ?? '');

        $datos = [
            'nombre'      => '',
            'telefono'    => '',
            'correo'      => '',
            'id_producto' => ctype_digit($producto) ? $producto : '',
            'medidas'     => '',
            'descripcion' => '',
        ];

        $this->mostrarCotizacion($datos, []);
    }

    /**
     * Guarda la solicitud de cotización con estado "Nueva" y notifica al
     * Carpintero (CU-07).
     *
     * Recibe: nada (usa $_POST con los campos del formulario).
     * Devuelve: nada.
     */
    public function guardarCotizacion(): void {
        $datos = [
            'nombre'      => $this->textoPost('nombre'),
            'telefono'    => $this->textoPost('telefono'),
            'correo'      => $this->textoPost('correo'),
            'id_producto' => $this->textoPost('id_producto'),
            'medidas'     => $this->textoPost('medidas'),
            'descripcion' => $this->textoPost('descripcion'),
        ];

        $errores = $this->validarCotizacion($datos);

        if ($errores !== []) {
            $this->mostrarCotizacion($datos, $errores);
            return;
        }

        $idSolicitud = (new SolicitudCotizacion())->crear([
            'nombre'      => $datos['nombre'],
            'telefono'    => $datos['telefono'],
            'correo'      => ($datos['correo'] !== '') ? $datos['correo'] : null,
            'id_producto' => ($datos['id_producto'] !== '') ? (int) $datos['id_producto'] : null,
            'medidas'     => ($datos['medidas'] !== '') ? $datos['medidas'] : null,
            'descripcion' => $datos['descripcion'],
        ]);

        // SRS 5.2: nueva solicitud de cotización → Carpintero
        NotificacionService::notificar(
            NotificacionService::usuariosDeRol(Auth::CARPINTERO),
            'COTIZACION',
            'Nueva solicitud de cotización de ' . $datos['nombre'],
            '/cotizaciones/' . $idSolicitud
        );

        Flash::agregar('exito', 'Gracias, nos comunicaremos contigo');
        $this->redirigir('/cotizacion');
    }

    /* ===== Funciones internas ===== */

    /**
     * Dibuja el formulario de cotización.
     *
     * Recibe: $datos, valores del formulario; $errores, errores por campo.
     * Devuelve: nada.
     */
    private function mostrarCotizacion(array $datos, array $errores): void {
        $this->vista('publico/cotizacion', [
            'datos'     => $datos,
            'errores'   => $errores,
            'productos' => (new ProductoCatalogo())->listarVisibles(),
        ], 'publico');
    }

    /**
     * Valida la solicitud de cotización (RF-11, CU-07 A1).
     *
     * Recibe: $datos, valores capturados.
     * Devuelve: errores por campo (vacío si todo es válido).
     */
    private function validarCotizacion(array $datos): array {
        $errores = [];

        if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 120) {
            $errores['nombre'] = 'Escribe tu nombre (máximo 120 caracteres).';
        }

        $errorTelefono = Usuario::validarTelefono($datos['telefono']);

        if ($errorTelefono !== null) {
            $errores['telefono'] = $errorTelefono;
        }

        $errorCorreo = Usuario::validarCorreo($datos['correo']);

        if ($errorCorreo !== null) {
            $errores['correo'] = $errorCorreo;
        }

        if ($datos['id_producto'] !== '') {
            $producto = ctype_digit($datos['id_producto'])
                ? (new ProductoCatalogo())->buscarPorId((int) $datos['id_producto'])
                : null;

            if ($producto === null || (int) $producto['activo'] !== 1) {
                $errores['id_producto'] = 'Elige un producto del catálogo '
                    . 'o deja la opción vacía.';
            }
        }

        if (mb_strlen($datos['medidas']) > 100) {
            $errores['medidas'] = 'Las medidas no pueden pasar de 100 caracteres.';
        }

        if ($datos['descripcion'] === '' || mb_strlen($datos['descripcion']) > 2000) {
            $errores['descripcion'] = 'Describe el mueble que necesitas (máximo 2000 caracteres).';
        }

        return $errores;
    }

}
