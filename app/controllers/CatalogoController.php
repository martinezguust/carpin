<?php
/*
 * =====================================================
 * Carpintería Carpin – app/controllers/CatalogoController.php
 * Administración del catálogo público por el Administrador
 * (HU-21, RF-12): alta, edición con imagen, ocultar /
 * mostrar y eliminar productos.
 * =====================================================
 */

class CatalogoController extends Controller {

    /* ===== Límite de la columna precio_desde (DECIMAL(10,2)) ===== */
    private const MAX_PRECIO = 99999999.99;

    /**
     * Muestra la lista de productos, visibles y ocultos.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function index(): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $this->vista('admin/catalogo', [
            'productos' => (new ProductoCatalogo())->listarTodos(),
        ], 'panel');
    }

    /**
     * Muestra el formulario de producto nuevo.
     *
     * Recibe: nada.
     * Devuelve: nada.
     */
    public function nuevo(): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $datos = [
            'id_categoria' => '',
            'nombre'       => '',
            'descripcion'  => '',
            'precio_desde' => '',
            'destacado'    => false,
        ];

        $this->mostrarFormulario(null, $datos, []);
    }

    /**
     * Registra un producto nuevo con su imagen opcional.
     *
     * Recibe: nada (usa $_POST y $_FILES['imagen']).
     * Devuelve: nada.
     */
    public function guardar(): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $datos = $this->leerFormulario();
        $errores = $this->validar($datos);
        $rutaImagen = null;

        if ($errores === []) {
            $rutaImagen = $this->subirImagen($errores);
        }

        if ($errores !== []) {
            $this->mostrarFormulario(null, $datos, $errores);
            return;
        }

        (new ProductoCatalogo())->crear($this->paraGuardar($datos, $rutaImagen));

        Flash::agregar('exito', "Producto \"{$datos['nombre']}\" agregado al catálogo.");
        $this->redirigir('/admin/catalogo');
    }

    /**
     * Muestra el formulario de edición de un producto.
     *
     * Recibe: $id, id del producto (segmento de la ruta).
     * Devuelve: nada.
     */
    public function editar(string $id): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $producto = $this->buscarProducto($this->idDeRuta($id));

        $datos = [
            'id_categoria' => (string) $producto['id_categoria'],
            'nombre'       => $producto['nombre'],
            'descripcion'  => (string) $producto['descripcion'],
            'precio_desde' => (string) $producto['precio_desde'],
            'destacado'    => (int) $producto['destacado'] === 1,
        ];

        $this->mostrarFormulario($producto, $datos, []);
    }

    /**
     * Guarda los cambios de un producto; si llega una imagen nueva,
     * reemplaza a la anterior.
     *
     * Recibe: $id, id del producto (segmento de la ruta).
     * Devuelve: nada.
     */
    public function actualizar(string $id): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $producto = $this->buscarProducto($this->idDeRuta($id));
        $datos = $this->leerFormulario();
        $errores = $this->validar($datos);
        $rutaImagen = $producto['imagen_ruta'];

        if ($errores === []) {
            $nuevaImagen = $this->subirImagen($errores);

            if ($nuevaImagen !== null) {
                $this->borrarArchivo($rutaImagen);
                $rutaImagen = $nuevaImagen;
            }
        }

        if ($errores !== []) {
            $this->mostrarFormulario($producto, $datos, $errores);
            return;
        }

        (new ProductoCatalogo())->actualizar(
            (int) $producto['id_producto'],
            $this->paraGuardar($datos, $rutaImagen)
        );

        Flash::agregar('exito', 'Los cambios del producto se guardaron.');
        $this->redirigir('/admin/catalogo');
    }

    /**
     * Oculta o vuelve a mostrar un producto en la página pública (RF-12).
     *
     * Recibe: $id, id del producto (segmento de la ruta);
     *         $_POST['accion'], "ocultar" o "mostrar".
     * Devuelve: nada.
     */
    public function cambiarVisibilidad(string $id): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $producto = $this->buscarProducto($this->idDeRuta($id));
        $mostrar = $this->textoPost('accion') === 'mostrar';

        (new ProductoCatalogo())->cambiarVisibilidad((int) $producto['id_producto'], $mostrar);

        if ($mostrar) {
            Flash::agregar('exito', "\"{$producto['nombre']}\" ya se muestra en el catálogo.");
        } else {
            Flash::agregar('exito', "\"{$producto['nombre']}\" quedó oculto del catálogo.");
        }

        $this->redirigir('/admin/catalogo');
    }

    /**
     * Elimina un producto y su imagen, solo si ninguna solicitud de
     * cotización lo menciona (respuesta 11 de la Fase 2).
     *
     * Recibe: $id, id del producto (segmento de la ruta).
     * Devuelve: nada.
     */
    public function eliminar(string $id): void {
        Auth::requireRol([Auth::ADMINISTRADOR]);

        $productos = new ProductoCatalogo();
        $producto = $this->buscarProducto($this->idDeRuta($id));
        $idProducto = (int) $producto['id_producto'];

        if ($productos->tieneSolicitudes($idProducto)) {
            Flash::agregar(
                'aviso',
                "\"{$producto['nombre']}\" aparece en solicitudes de cotización y no se puede "
                . 'eliminar. Puedes ocultarlo para que ya no se vea en el catálogo.'
            );
            $this->redirigir('/admin/catalogo');
        }

        $productos->eliminar($idProducto);
        $this->borrarArchivo($producto['imagen_ruta']);

        Flash::agregar('exito', "\"{$producto['nombre']}\" se eliminó del catálogo.");
        $this->redirigir('/admin/catalogo');
    }

    /* ===== Funciones internas ===== */

    /**
     * Dibuja el formulario de producto (nuevo o edición).
     *
     * Recibe: $producto, datos guardados o null si es nuevo;
     *         $datos, valores del formulario; $errores, errores por campo.
     * Devuelve: nada.
     */
    private function mostrarFormulario(?array $producto, array $datos, array $errores): void {
        $this->vista('admin/producto_formulario', [
            'producto'   => $producto,
            'datos'      => $datos,
            'errores'    => $errores,
            'categorias' => (new CategoriaCatalogo())->todas(),
        ], 'panel');
    }

    /**
     * Lee del POST los campos del formulario.
     *
     * Recibe: nada.
     * Devuelve: valores capturados.
     */
    private function leerFormulario(): array {
        return [
            'id_categoria' => $this->textoPost('id_categoria'),
            'nombre'       => $this->textoPost('nombre'),
            'descripcion'  => $this->textoPost('descripcion'),
            'precio_desde' => $this->textoPost('precio_desde'),
            'destacado'    => $this->textoPost('destacado') === '1',
        ];
    }

    /**
     * Valida los datos del producto.
     *
     * Recibe: $datos, valores capturados.
     * Devuelve: errores por campo.
     */
    private function validar(array $datos): array {
        $errores = [];

        $categoriaValida = ctype_digit($datos['id_categoria'])
            && (new CategoriaCatalogo())->existe((int) $datos['id_categoria']);

        if (!$categoriaValida) {
            $errores['id_categoria'] = 'Elige una categoría.';
        }

        if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 100) {
            $errores['nombre'] = 'Escribe el nombre del producto (máximo 100 caracteres).';
        }

        if (mb_strlen($datos['descripcion']) > 2000) {
            $errores['descripcion'] = 'La descripción no puede pasar de 2000 caracteres.';
        }

        $precio = $datos['precio_desde'];
        $precioValido = is_numeric($precio)
            && (float) $precio > 0
            && (float) $precio <= self::MAX_PRECIO;

        if ($precio !== '' && !$precioValido) {
            $errores['precio_desde'] = 'El precio debe ser un número mayor a $0.00, '
                . 'o déjalo vacío.';
        }

        return $errores;
    }

    /**
     * Sube la imagen del producto si se eligió una (JPG, PNG o WEBP de
     * máximo 5 MB). Si no es válida, agrega el error.
     *
     * Recibe: $errores, errores por campo (se agrega 'imagen' si falla).
     * Devuelve: ruta de la imagen subida, o null si no se eligió o falló.
     */
    private function subirImagen(array &$errores): ?string {
        $archivo = $_FILES['imagen'] ?? null;

        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        try {
            return Upload::guardar($archivo, 'catalogo', Upload::IMAGENES);
        } catch (RuntimeException $error) {
            $errores['imagen'] = $error->getMessage();
            return null;
        }
    }

    /**
     * Arma los valores que se guardan en la tabla.
     *
     * Recibe: $datos, valores validados; $rutaImagen, ruta o null.
     * Devuelve: arreglo para ProductoCatalogo::crear o actualizar.
     */
    private function paraGuardar(array $datos, ?string $rutaImagen): array {
        $precio = null;

        if ($datos['precio_desde'] !== '') {
            $precio = number_format(round((float) $datos['precio_desde'], 2), 2, '.', '');
        }

        return [
            'id_categoria' => (int) $datos['id_categoria'],
            'nombre'       => $datos['nombre'],
            'descripcion'  => ($datos['descripcion'] !== '') ? $datos['descripcion'] : null,
            'precio_desde' => $precio,
            'imagen_ruta'  => $rutaImagen,
            'destacado'    => $datos['destacado'] ? 1 : 0,
        ];
    }

    /**
     * Borra del disco una imagen del catálogo que ya no se usa.
     *
     * Recibe: $ruta, ruta relativa a public/ (o null).
     * Devuelve: nada.
     */
    private function borrarArchivo(?string $ruta): void {
        if ($ruta === null || !str_starts_with($ruta, 'uploads/catalogo/')) {
            return;
        }

        $archivo = RAIZ . '/public/' . $ruta;

        if (is_file($archivo)) {
            unlink($archivo);
        }
    }

    /**
     * Busca un producto o responde 404.
     *
     * Recibe: $idProducto.
     * Devuelve: datos del producto.
     */
    private function buscarProducto(int $idProducto): array {
        $producto = (new ProductoCatalogo())->buscarPorId($idProducto);

        if ($producto === null) {
            View::error(404);
        }

        return $producto;
    }

}
