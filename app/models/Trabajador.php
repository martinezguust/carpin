<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Trabajador.php
 * Acceso a la tabla trabajador: datos de las personas que
 * trabajan en la carpintería y su forma de pago.
 * =====================================================
 */

class Trabajador extends Model {

    /* ===== Formas de pago permitidas por rol (SRS 5.4 y respuesta 30) ===== */
    public const FORMAS_POR_ROL = [
        Auth::ADMINISTRADOR => [],
        Auth::CARPINTERO    => ['HORA', 'DIA'],
        Auth::CONTADOR      => ['HORA', 'DIA'],
        Auth::LIJADOR       => ['HORA', 'DIA', 'PROYECTO'],
        Auth::PINTOR        => ['HORA', 'DIA', 'PROYECTO'],
    ];

    /**
     * Registra los datos de trabajador de una cuenta nueva.
     *
     * Recibe: $idUsuario, cuenta del trabajador; $nombre, nombre completo;
     *         $telefono, 10 dígitos; $formaPago, HORA/DIA/PROYECTO o null.
     * Devuelve: id del trabajador creado.
     */
    public function crear(
        int $idUsuario,
        string $nombre,
        string $telefono,
        ?string $formaPago
    ): int {
        $consulta = $this->db->prepare(
            'INSERT INTO trabajador (id_usuario, nombre_completo, telefono, forma_pago) '
            . 'VALUES (?, ?, ?, ?)'
        );
        $consulta->execute([$idUsuario, $nombre, $telefono, $formaPago]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza nombre, teléfono y tipo de forma de pago. La tarifa y el
     * salario no se tocan: los captura el Contador o el Carpintero (HU-30).
     *
     * Recibe: $idUsuario, cuenta del trabajador; $nombre; $telefono;
     *         $formaPago, HORA/DIA/PROYECTO o null.
     * Devuelve: nada.
     */
    public function actualizar(
        int $idUsuario,
        string $nombre,
        string $telefono,
        ?string $formaPago
    ): void {
        $consulta = $this->db->prepare(
            'UPDATE trabajador SET nombre_completo = ?, telefono = ?, forma_pago = ? '
            . 'WHERE id_usuario = ?'
        );
        $consulta->execute([$nombre, $telefono, $formaPago, $idUsuario]);
    }

    /**
     * Activa o desactiva al trabajador junto con su cuenta.
     *
     * Recibe: $idUsuario, cuenta del trabajador; $activo, true para activar.
     * Devuelve: nada.
     */
    public function cambiarEstado(int $idUsuario, bool $activo): void {
        $consulta = $this->db->prepare('UPDATE trabajador SET activo = ? WHERE id_usuario = ?');
        $consulta->execute([$activo ? 1 : 0, $idUsuario]);
    }

    /**
     * Busca el trabajador que corresponde a una cuenta.
     *
     * Recibe: $idUsuario, id de la cuenta.
     * Devuelve: datos del trabajador, o null si la cuenta no es de trabajador.
     */
    public function buscarPorUsuario(int $idUsuario): ?array {
        $consulta = $this->db->prepare('SELECT * FROM trabajador WHERE id_usuario = ?');
        $consulta->execute([$idUsuario]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Busca un trabajador por id, con su rol y el estado de su cuenta.
     *
     * Recibe: $idTrabajador, id del trabajador.
     * Devuelve: datos del trabajador, o null si no existe.
     */
    public function buscarPorId(int $idTrabajador): ?array {
        $consulta = $this->db->prepare(
            'SELECT t.*, r.nombre AS rol, u.activo AS cuenta_activa '
            . 'FROM trabajador t '
            . 'JOIN usuario u ON u.id_usuario = t.id_usuario '
            . 'JOIN rol r ON r.id_rol = u.id_rol '
            . 'WHERE t.id_trabajador = ?'
        );
        $consulta->execute([$idTrabajador]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Lista los trabajadores que entran a la nómina (todos menos el
     * Administrador), con su rol y estado, para configurar su forma de
     * pago (HU-30).
     *
     * Recibe: nada.
     * Devuelve: lista ordenada por nombre.
     */
    public function listarConFormaPago(): array {
        $consulta = $this->db->prepare(
            'SELECT t.*, r.nombre AS rol, u.activo AS cuenta_activa '
            . 'FROM trabajador t '
            . 'JOIN usuario u ON u.id_usuario = t.id_usuario '
            . 'JOIN rol r ON r.id_rol = u.id_rol '
            . 'WHERE r.nombre <> ? '
            . 'ORDER BY t.nombre_completo'
        );
        $consulta->execute([Auth::ADMINISTRADOR]);

        return $consulta->fetchAll();
    }

    /**
     * Indica si la forma de pago del trabajador está completa: por hora con
     * tarifa, por día con salario o por proyecto (SRS 5.4).
     *
     * Recibe: $trabajador, datos con forma_pago, tarifa_hora y salario_dia.
     * Devuelve: true si ya está configurada.
     */
    public static function estaConfigurado(array $trabajador): bool {
        return match ($trabajador['forma_pago']) {
            'HORA'     => $trabajador['tarifa_hora'] !== null,
            'DIA'      => $trabajador['salario_dia'] !== null,
            'PROYECTO' => true,
            default    => false,
        };
    }

    /**
     * Guarda la forma de pago con su tarifa (HU-30, RF-43). La tarifa que
     * no corresponde a la forma elegida se deja vacía.
     *
     * Recibe: $idTrabajador; $formaPago, HORA/DIA/PROYECTO;
     *         $tarifaHora y $salarioDia, textos con 2 decimales o null.
     * Devuelve: nada.
     */
    public function actualizarFormaPago(
        int $idTrabajador,
        string $formaPago,
        ?string $tarifaHora,
        ?string $salarioDia
    ): void {
        $consulta = $this->db->prepare(
            'UPDATE trabajador SET forma_pago = ?, tarifa_hora = ?, salario_dia = ? '
            . 'WHERE id_trabajador = ?'
        );
        $consulta->execute([$formaPago, $tarifaHora, $salarioDia, $idTrabajador]);
    }

    /**
     * Lista los trabajadores activos de un rol (para asignar actividades).
     *
     * Recibe: $rol, nombre del rol, ej. "LIJADOR".
     * Devuelve: lista con id_trabajador y nombre_completo.
     */
    public function activosPorRol(string $rol): array {
        $consulta = $this->db->prepare(
            'SELECT t.id_trabajador, t.nombre_completo '
            . 'FROM trabajador t '
            . 'JOIN usuario u ON u.id_usuario = t.id_usuario '
            . 'JOIN rol r ON r.id_rol = u.id_rol '
            . 'WHERE r.nombre = ? AND t.activo = 1 AND u.activo = 1 '
            . 'ORDER BY t.nombre_completo'
        );
        $consulta->execute([$rol]);

        return $consulta->fetchAll();
    }

}
