<?php
/*
 * =====================================================
 * Carpintería Carpin – app/core/Router.php
 * Enrutador del front controller. Registra las rutas GET y
 * POST de config/rutas.php, identifica la ruta pedida y
 * llama al método del controlador que le corresponde.
 * Todo POST se rechaza si no trae un token CSRF válido.
 * =====================================================
 */

final class Router {

    /* ===== Estado del enrutador ===== */

    // Rutas registradas: ['GET' => [patrón => acción], 'POST' => [...]]
    private array $rutas = [
        'GET'  => [],
        'POST' => [],
    ];

    // Carpeta base de la aplicación en la URL, ej. "/carpin/public"
    private string $rutaBase;

    /**
     * Prepara el enrutador a partir de la URL base del sistema.
     *
     * Recibe: $urlBase, URL completa de public/ (config 'base_url').
     * Devuelve: nada.
     */
    public function __construct(string $urlBase) {
        $ruta = parse_url($urlBase, PHP_URL_PATH) ?? '';
        $this->rutaBase = rtrim($ruta, '/');
    }

    /* ===== Registro de rutas ===== */

    /**
     * Registra una ruta que responde a peticiones GET.
     *
     * Recibe: $ruta, patrón como "/proyectos/{id}";
     *         $accion, texto "Controlador@metodo".
     * Devuelve: nada.
     */
    public function get(string $ruta, string $accion): void {
        $this->rutas['GET'][$this->normalizar($ruta)] = $accion;
    }

    /**
     * Registra una ruta que responde a peticiones POST.
     *
     * Recibe: $ruta, patrón como "/proyectos/{id}/avanzar";
     *         $accion, texto "Controlador@metodo".
     * Devuelve: nada.
     */
    public function post(string $ruta, string $accion): void {
        $this->rutas['POST'][$this->normalizar($ruta)] = $accion;
    }

    /* ===== Atención de la petición ===== */

    /**
     * Atiende la petición actual: busca la ruta, valida el CSRF en los
     * POST y ejecuta el controlador. Si no hay ruta responde 404.
     *
     * Recibe: nada (usa $_SERVER).
     * Devuelve: nada.
     */
    public function despachar(): void {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // HEAD se atiende igual que GET
        if ($metodo === 'HEAD') {
            $metodo = 'GET';
        }

        if (!isset($this->rutas[$metodo])) {
            View::error(404);
        }

        $ruta = $this->rutaSolicitada();

        foreach ($this->rutas[$metodo] as $patron => $accion) {
            $parametros = $this->coincide($patron, $ruta);

            if ($parametros === null) {
                continue;
            }

            // Todo formulario POST debe traer un token CSRF válido (RNF-03)
            if ($metodo === 'POST' && !Csrf::validar()) {
                View::error(403);
            }

            $this->ejecutar($accion, $parametros);
            return;
        }

        View::error(404);
    }

    /* ===== Funciones internas ===== */

    /**
     * Obtiene la ruta pedida sin la carpeta base ni la cadena de consulta.
     *
     * Recibe: nada (usa $_SERVER['REQUEST_URI']).
     * Devuelve: ruta normalizada, ej. "/proyectos/5".
     */
    private function rutaSolicitada(): string {
        $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $ruta = rawurldecode($ruta);

        if ($this->rutaBase !== '' && str_starts_with($ruta, $this->rutaBase)) {
            $ruta = substr($ruta, strlen($this->rutaBase));
        }

        // Peticiones directas a /index.php se tratan como la raíz
        if ($ruta === '/index.php') {
            $ruta = '/';
        }

        return $this->normalizar($ruta);
    }

    /**
     * Deja una ruta con una sola diagonal al inicio y ninguna al final.
     *
     * Recibe: $ruta, texto de la ruta.
     * Devuelve: ruta normalizada ("/" para la raíz).
     */
    private function normalizar(string $ruta): string {
        $ruta = '/' . trim($ruta, '/');

        return $ruta;
    }

    /**
     * Compara un patrón registrado con la ruta pedida.
     *
     * Recibe: $patron, ej. "/proyectos/{id}"; $ruta, ej. "/proyectos/5".
     * Devuelve: arreglo con los valores de los {parámetros} si coincide,
     *           o null si no coincide.
     */
    private function coincide(string $patron, string $ruta): ?array {
        $expresion = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $patron);
        $expresion = '#^' . $expresion . '$#u';

        if (preg_match($expresion, $ruta, $coincidencias) !== 1) {
            return null;
        }

        array_shift($coincidencias);

        return $coincidencias;
    }

    /**
     * Crea el controlador indicado y llama a su método con los parámetros.
     *
     * Recibe: $accion, texto "Controlador@metodo"; $parametros de la ruta.
     * Devuelve: nada. Lanza una excepción si la acción no existe.
     */
    private function ejecutar(string $accion, array $parametros): void {
        [$clase, $metodo] = explode('@', $accion, 2);

        if (!class_exists($clase) || !method_exists($clase, $metodo)) {
            throw new RuntimeException("La acción de la ruta no existe: {$accion}");
        }

        $controlador = new $clase();
        $controlador->$metodo(...$parametros);
    }

}
