<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Usuario.php
 * Acceso a la tabla usuario: cuentas de acceso de todos
 * los roles (trabajadores y clientes). Incluye las reglas
 * de formato del nombre de usuario (RN-06) y de la
 * contraseña (RN-07).
 * =====================================================
 */

class Usuario extends Model {

    /* ===== Reglas de formato ===== */

    // RN-06: 4 a 30 caracteres; letras, números, punto y guion bajo
    private const PATRON_USERNAME = '/^[A-Za-z0-9._]{4,30}$/';

    /**
     * Valida el formato de un nombre de usuario (RN-06).
     *
     * Recibe: $username, texto capturado.
     * Devuelve: mensaje de error, o null si es válido.
     */
    public static function validarUsername(string $username): ?string {
        if (preg_match(self::PATRON_USERNAME, $username) !== 1) {
            return 'El usuario debe tener de 4 a 30 caracteres: '
                . 'solo letras, números, punto y guion bajo.';
        }

        return null;
    }

    /**
     * Valida que una contraseña cumpla la RN-07: mínimo 8 caracteres
     * con al menos una letra y un número.
     *
     * Recibe: $contrasena, texto capturado.
     * Devuelve: mensaje de error, o null si es válida.
     */
    public static function validarContrasena(string $contrasena): ?string {
        $tieneLetra = preg_match('/\p{L}/u', $contrasena) === 1;
        $tieneNumero = preg_match('/[0-9]/', $contrasena) === 1;

        if (mb_strlen($contrasena) < 8 || !$tieneLetra || !$tieneNumero) {
            return 'La contraseña debe tener al menos 8 caracteres, con una letra y un número.';
        }

        return null;
    }

    /**
     * Valida un teléfono de 10 dígitos (SRS 5.4 y respuesta 6).
     *
     * Recibe: $telefono, texto capturado.
     * Devuelve: mensaje de error, o null si es válido.
     */
    public static function validarTelefono(string $telefono): ?string {
        if (preg_match('/^[0-9]{10}$/', $telefono) !== 1) {
            return 'El teléfono debe tener exactamente 10 dígitos.';
        }

        return null;
    }

    /**
     * Valida un correo electrónico opcional.
     *
     * Recibe: $correo, texto capturado ("" si no se capturó).
     * Devuelve: mensaje de error, o null si está vacío o es válido.
     */
    public static function validarCorreo(string $correo): ?string {
        if ($correo === '') {
            return null;
        }

        if (mb_strlen($correo) > 120 || filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            return 'Escribe un correo electrónico válido.';
        }

        return null;
    }

    /* ===== Consultas ===== */

    /**
     * Busca una cuenta por su nombre de usuario para iniciar sesión.
     *
     * Recibe: $username, nombre de usuario.
     * Devuelve: arreglo con los datos de la cuenta, su rol y el nombre de
     *           la persona, o null si no existe.
     */
    public function buscarParaLogin(string $username): ?array {
        $consulta = $this->db->prepare(
            'SELECT u.id_usuario, u.username, u.password_hash, u.activo, '
            . 'u.intentos_fallidos, u.bloqueado_hasta, r.nombre AS rol, '
            . 'COALESCE(t.nombre_completo, c.nombre_completo) AS nombre '
            . 'FROM usuario u '
            . 'JOIN rol r ON r.id_rol = u.id_rol '
            . 'LEFT JOIN trabajador t ON t.id_usuario = u.id_usuario '
            . 'LEFT JOIN cliente c ON c.id_usuario = u.id_usuario '
            . 'WHERE u.username = ?'
        );
        $consulta->execute([$username]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Busca una cuenta por id con los datos de trabajador o de cliente.
     *
     * Recibe: $idUsuario, id de la cuenta.
     * Devuelve: arreglo con usuario, rol, trabajador y cliente, o null.
     */
    public function buscarPorId(int $idUsuario): ?array {
        $consulta = $this->db->prepare(
            'SELECT u.id_usuario, u.username, u.activo, u.ultimo_acceso, r.nombre AS rol, '
            . 't.id_trabajador, t.forma_pago, '
            . 'c.id_cliente, c.correo, c.direccion, '
            . 'COALESCE(t.nombre_completo, c.nombre_completo) AS nombre, '
            . 'COALESCE(t.telefono, c.telefono) AS telefono '
            . 'FROM usuario u '
            . 'JOIN rol r ON r.id_rol = u.id_rol '
            . 'LEFT JOIN trabajador t ON t.id_usuario = u.id_usuario '
            . 'LEFT JOIN cliente c ON c.id_usuario = u.id_usuario '
            . 'WHERE u.id_usuario = ?'
        );
        $consulta->execute([$idUsuario]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Lista las cuentas para la pantalla de Usuarios, con búsqueda por
     * nombre o usuario y filtro por rol.
     *
     * Recibe: $busqueda, texto a buscar ("" para todos);
     *         $rol, nombre del rol ("" para todos).
     * Devuelve: lista de cuentas ordenadas por nombre.
     */
    public function listar(string $busqueda, string $rol): array {
        $sql = 'SELECT u.id_usuario, u.username, u.activo, r.nombre AS rol, '
            . 'COALESCE(t.nombre_completo, c.nombre_completo) AS nombre, '
            . 'COALESCE(t.telefono, c.telefono) AS telefono '
            . 'FROM usuario u '
            . 'JOIN rol r ON r.id_rol = u.id_rol '
            . 'LEFT JOIN trabajador t ON t.id_usuario = u.id_usuario '
            . 'LEFT JOIN cliente c ON c.id_usuario = u.id_usuario '
            . 'WHERE 1 = 1';
        $valores = [];

        if ($busqueda !== '') {
            $sql .= ' AND (u.username LIKE ? '
                . 'OR COALESCE(t.nombre_completo, c.nombre_completo) LIKE ?)';
            $valores[] = '%' . $busqueda . '%';
            $valores[] = '%' . $busqueda . '%';
        }

        if ($rol !== '') {
            $sql .= ' AND r.nombre = ?';
            $valores[] = $rol;
        }

        $sql .= ' ORDER BY nombre';

        $consulta = $this->db->prepare($sql);
        $consulta->execute($valores);

        return $consulta->fetchAll();
    }

    /**
     * Indica si un nombre de usuario ya existe en todo el sistema (RN-06).
     *
     * Recibe: $username, nombre a revisar;
     *         $excluirId, id de la cuenta que se está editando (o null).
     * Devuelve: true si ya lo usa otra cuenta.
     */
    public function existeUsername(string $username, ?int $excluirId = null): bool {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM usuario WHERE username = ? AND id_usuario <> ?'
        );
        $consulta->execute([$username, $excluirId ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    /**
     * Devuelve el id de un rol a partir de su nombre.
     *
     * Recibe: $rol, nombre del rol, ej. "CLIENTE".
     * Devuelve: id_rol.
     */
    public function idDeRol(string $rol): int {
        $consulta = $this->db->prepare('SELECT id_rol FROM rol WHERE nombre = ?');
        $consulta->execute([$rol]);
        $idRol = $consulta->fetchColumn();

        if ($idRol === false) {
            throw new RuntimeException("No existe el rol {$rol}.");
        }

        return (int) $idRol;
    }

    /**
     * Cuenta las cuentas activas (panel del Administrador).
     *
     * Recibe: nada.
     * Devuelve: número de usuarios con activo = 1.
     */
    public function contarActivos(): int {
        return (int) $this->db->query('SELECT COUNT(*) FROM usuario WHERE activo = 1')
            ->fetchColumn();
    }

    /**
     * Revisa si la contraseña dada es la actual de la cuenta.
     *
     * Recibe: $idUsuario, id de la cuenta; $contrasena, texto capturado.
     * Devuelve: true si coincide con el hash guardado.
     */
    public function verificarContrasena(int $idUsuario, string $contrasena): bool {
        $consulta = $this->db->prepare('SELECT password_hash FROM usuario WHERE id_usuario = ?');
        $consulta->execute([$idUsuario]);
        $hash = $consulta->fetchColumn();

        return $hash !== false && password_verify($contrasena, $hash);
    }

    /* ===== Altas y cambios ===== */

    /**
     * Crea una cuenta con la contraseña cifrada con bcrypt (RNF-01).
     *
     * Recibe: $username, nombre de usuario; $contrasena, texto plano;
     *         $rol, nombre del rol.
     * Devuelve: id de la cuenta creada.
     */
    public function crear(string $username, string $contrasena, string $rol): int {
        $consulta = $this->db->prepare(
            'INSERT INTO usuario (username, password_hash, id_rol) VALUES (?, ?, ?)'
        );
        $consulta->execute([
            $username,
            password_hash($contrasena, PASSWORD_BCRYPT),
            $this->idDeRol($rol),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Cambia el nombre de usuario y el rol de una cuenta.
     *
     * Recibe: $idUsuario, id de la cuenta; $username, nuevo nombre;
     *         $rol, nombre del nuevo rol.
     * Devuelve: nada.
     */
    public function actualizarCuenta(int $idUsuario, string $username, string $rol): void {
        $consulta = $this->db->prepare(
            'UPDATE usuario SET username = ?, id_rol = ? WHERE id_usuario = ?'
        );
        $consulta->execute([$username, $this->idDeRol($rol), $idUsuario]);
    }

    /**
     * Guarda una nueva contraseña cifrada con bcrypt.
     *
     * Recibe: $idUsuario, id de la cuenta; $contrasena, texto plano.
     * Devuelve: nada.
     */
    public function cambiarContrasena(int $idUsuario, string $contrasena): void {
        $consulta = $this->db->prepare(
            'UPDATE usuario SET password_hash = ?, intentos_fallidos = 0, bloqueado_hasta = NULL '
            . 'WHERE id_usuario = ?'
        );
        $consulta->execute([password_hash($contrasena, PASSWORD_BCRYPT), $idUsuario]);
    }

    /**
     * Activa o desactiva una cuenta (baja lógica, RN-18).
     *
     * Recibe: $idUsuario, id de la cuenta; $activo, true para activar.
     * Devuelve: nada.
     */
    public function cambiarEstado(int $idUsuario, bool $activo): void {
        $consulta = $this->db->prepare('UPDATE usuario SET activo = ? WHERE id_usuario = ?');
        $consulta->execute([$activo ? 1 : 0, $idUsuario]);
    }

    /* ===== Intentos de inicio de sesión (CU-01) ===== */

    /**
     * Guarda el número de intentos fallidos y, si aplica, el bloqueo.
     *
     * Recibe: $idUsuario, id de la cuenta; $intentos, intentos acumulados;
     *         $bloqueadoHasta, fecha y hora de fin del bloqueo, o null.
     * Devuelve: nada.
     */
    public function registrarIntentoFallido(
        int $idUsuario,
        int $intentos,
        ?string $bloqueadoHasta
    ): void {
        $consulta = $this->db->prepare(
            'UPDATE usuario SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id_usuario = ?'
        );
        $consulta->execute([$intentos, $bloqueadoHasta, $idUsuario]);
    }

    /**
     * Limpia los intentos fallidos y guarda la fecha del último acceso.
     *
     * Recibe: $idUsuario, id de la cuenta.
     * Devuelve: nada.
     */
    public function registrarAccesoExitoso(int $idUsuario): void {
        $consulta = $this->db->prepare(
            'UPDATE usuario SET intentos_fallidos = 0, bloqueado_hasta = NULL, '
            . 'ultimo_acceso = NOW() WHERE id_usuario = ?'
        );
        $consulta->execute([$idUsuario]);
    }

}
