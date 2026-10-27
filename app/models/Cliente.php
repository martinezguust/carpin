<?php
/*
 * =====================================================
 * Carpintería Carpin – app/models/Cliente.php
 * Acceso a la tabla cliente: dueños de proyectos y
 * reparaciones. Su cuenta la crea el Carpintero (RF-16).
 * =====================================================
 */

class Cliente extends Model {

    /**
     * Registra los datos de cliente de una cuenta nueva.
     *
     * Recibe: $idUsuario, cuenta del cliente; $nombre; $telefono (10 dígitos);
     *         $correo y $direccion, opcionales (null si no se capturaron).
     * Devuelve: id del cliente creado.
     */
    public function crear(
        int $idUsuario,
        string $nombre,
        string $telefono,
        ?string $correo,
        ?string $direccion
    ): int {
        $consulta = $this->db->prepare(
            'INSERT INTO cliente (id_usuario, nombre_completo, telefono, correo, direccion) '
            . 'VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->execute([$idUsuario, $nombre, $telefono, $correo, $direccion]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza los datos de un cliente (lo hace el Administrador).
     *
     * Recibe: $idUsuario, cuenta del cliente; $nombre; $telefono;
     *         $correo y $direccion, opcionales.
     * Devuelve: nada.
     */
    public function actualizar(
        int $idUsuario,
        string $nombre,
        string $telefono,
        ?string $correo,
        ?string $direccion
    ): void {
        $consulta = $this->db->prepare(
            'UPDATE cliente SET nombre_completo = ?, telefono = ?, correo = ?, direccion = ? '
            . 'WHERE id_usuario = ?'
        );
        $consulta->execute([$nombre, $telefono, $correo, $direccion, $idUsuario]);
    }

    /**
     * Lista los clientes con cuenta activa (selector de "cliente existente").
     *
     * Recibe: nada.
     * Devuelve: lista con id_cliente, nombre_completo y telefono.
     */
    public function listarActivos(): array {
        return $this->db->query(
            'SELECT c.id_cliente, c.nombre_completo, c.telefono '
            . 'FROM cliente c '
            . 'JOIN usuario u ON u.id_usuario = c.id_usuario '
            . 'WHERE u.activo = 1 '
            . 'ORDER BY c.nombre_completo'
        )->fetchAll();
    }

    /**
     * Lista todos los clientes (filtro de la lista de proyectos).
     *
     * Recibe: nada.
     * Devuelve: lista con id_cliente y nombre_completo.
     */
    public function listarTodos(): array {
        return $this->db->query(
            'SELECT id_cliente, nombre_completo FROM cliente ORDER BY nombre_completo'
        )->fetchAll();
    }

    /**
     * Busca el cliente que corresponde a una cuenta.
     *
     * Recibe: $idUsuario, id de la cuenta.
     * Devuelve: datos del cliente, o null si la cuenta no es de cliente.
     */
    public function buscarPorUsuario(int $idUsuario): ?array {
        $consulta = $this->db->prepare('SELECT * FROM cliente WHERE id_usuario = ?');
        $consulta->execute([$idUsuario]);

        return $consulta->fetch() ?: null;
    }

    /**
     * Busca un cliente por id, con el estado de su cuenta.
     *
     * Recibe: $idCliente, id del cliente.
     * Devuelve: datos del cliente, o null si no existe.
     */
    public function buscarPorId(int $idCliente): ?array {
        $consulta = $this->db->prepare(
            'SELECT c.*, u.username, u.activo AS cuenta_activa '
            . 'FROM cliente c JOIN usuario u ON u.id_usuario = c.id_usuario '
            . 'WHERE c.id_cliente = ?'
        );
        $consulta->execute([$idCliente]);

        return $consulta->fetch() ?: null;
    }

}
