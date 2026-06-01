<?php

namespace App\Models;

use PDO;

/**
 * Modelo de Usuario (RF-001)
 * Gestiona la persistencia e integridad de datos de los usuarios en la base de datos.
 */
class User extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Obtiene todos los usuarios activos en el sistema (eliminación lógica excluida).
     * 
     * @return array Lista de usuarios con el nombre de su rol
     */
    public function getAll(): array {
        $sql = "SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE u.deleted_at IS NULL 
                ORDER BY u.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un usuario activo por su ID.
     * 
     * @param int $id ID del usuario
     * @return array|false Datos del usuario o falso si no existe
     */
    public function getById(int $id) {
        $sql = "SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE u.id = :id AND u.deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Registra un nuevo usuario en la base de datos.
     * 
     * @param array $data Datos del usuario
     * @return bool Resultado de la operación
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO usuarios (rol_id, nombre, documento, correo, telefono, usuario, contrasena, estado) 
                VALUES (:rol_id, :nombre, :documento, :correo, :telefono, :usuario, :contrasena, :estado)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'rol_id'     => $data['rol_id'],
            'nombre'     => $data['nombre'],
            'documento'  => $data['documento'],
            'correo'     => $data['correo'],
            'telefono'   => $data['telefono'] ?? null,
            'usuario'    => $data['usuario'],
            'contrasena' => password_hash($data['contrasena'], PASSWORD_BCRYPT), // Cifrado de contraseña
            'estado'     => $data['estado'] ?? 'activo'
        ]);
    }

    /**
     * Actualiza los datos de un usuario existente.
     * 
     * @param int $id ID del usuario
     * @param array $data Datos actualizados
     * @return bool Resultado de la operación
     */
    public function update(int $id, array $data): bool {
        $params = [
            'id'        => $id,
            'rol_id'    => $data['rol_id'],
            'nombre'    => $data['nombre'],
            'documento' => $data['documento'],
            'correo'    => $data['correo'],
            'telefono'  => $data['telefono'] ?? null,
            'usuario'   => $data['usuario'],
            'estado'    => $data['estado']
        ];

        $sql = "UPDATE usuarios SET 
                    rol_id = :rol_id, 
                    nombre = :nombre, 
                    documento = :documento, 
                    correo = :correo, 
                    telefono = :telefono, 
                    usuario = :usuario, 
                    estado = :estado";

        // Si se provee una contraseña nueva, se cifra y se agrega a la consulta
        if (!empty($data['contrasena'])) {
            $sql .= ", contrasena = :contrasena";
            $params['contrasena'] = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        }

        $sql .= " WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Realiza la eliminación lógica de un usuario (Soft Delete).
     * Establece el estado en 'inactivo' y registra la fecha de baja en deleted_at.
     * 
     * @param int $id ID del usuario a dar de baja
     * @return bool Resultado de la operación
     */
    public function deleteLogically(int $id): bool {
        $sql = "UPDATE usuarios SET estado = 'inactivo', deleted_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Verifica la existencia de un documento de identidad para control de unicidad.
     * 
     * @param string $documento Documento a verificar
     * @param int|null $excludeId ID opcional a excluir (útil en actualización)
     * @return bool Verdadero si ya existe
     */
    public function existsDocument(string $documento, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE documento = :documento AND deleted_at IS NULL";
        $params = ['documento' => $documento];

        if ($excludeId !== null) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verifica la existencia de un nombre de usuario para control de unicidad.
     * 
     * @param string $usuario Nombre de usuario a verificar
     * @param int|null $excludeId ID opcional a excluir (útil en actualización)
     * @return bool Verdadero si ya existe
     */
    public function existsUsername(string $usuario, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario AND deleted_at IS NULL";
        $params = ['usuario' => $usuario];

        if ($excludeId !== null) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verifica la existencia de un correo electrónico para control de unicidad.
     * 
     * @param string $correo Correo a verificar
     * @param int|null $excludeId ID opcional a excluir (útil en actualización)
     * @return bool Verdadero si ya existe
     */
    public function existsEmail(string $correo, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE correo = :correo AND deleted_at IS NULL";
        $params = ['correo' => $correo];

        if ($excludeId !== null) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Obtiene un usuario activo por su nombre de usuario o correo electrónico.
     * 
     * @param string $login Nombre de usuario o correo electrónico
     * @return array|false Datos del usuario con su rol, o falso si no se encuentra
     */
    public function getByUsernameOrEmail(string $login) {
        $sql = "SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE (u.usuario = :login_u OR u.correo = :login_c) AND u.estado = 'activo' AND u.deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'login_u' => $login,
            'login_c' => $login
        ]);
        return $stmt->fetch();
    }

    /**
     * Incrementa el contador de intentos fallidos de inicio de sesión de un usuario.
     * 
     * @param int $id ID del usuario
     * @param int $attempts Nuevo contador de intentos
     * @return bool Resultado de la operación
     */
    public function incrementFailedAttempts(int $id, int $attempts): bool {
        $sql = "UPDATE usuarios SET intentos_fallidos = :attempts WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['attempts' => $attempts, 'id' => $id]);
    }

    /**
     * Establece la fecha de bloqueo para un usuario y restablece los intentos fallidos.
     * 
     * @param int $id ID del usuario
     * @param string $blockedUntil Fecha en formato Y-m-d H:i:s hasta la que estará bloqueado
     * @return bool Resultado de la operación
     */
    public function blockUser(int $id, string $blockedUntil): bool {
        $sql = "UPDATE usuarios SET bloqueado_hasta = :blocked_until, intentos_fallidos = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['blocked_until' => $blockedUntil, 'id' => $id]);
    }

    /**
     * Restablece los intentos fallidos y la fecha de bloqueo de un usuario tras un inicio de sesión exitoso.
     * 
     * @param int $id ID del usuario
     * @return bool Resultado de la operación
     */
    public function resetFailedAttempts(int $id): bool {
        $sql = "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
