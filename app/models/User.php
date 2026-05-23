<?php

namespace App\Models;

use Config\Database;
use PDO;

/**
 * Modelo de Usuario (RF-001)
 * Gestiona la persistencia e integridad de datos de los usuarios en la base de datos.
 */
class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
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
}
