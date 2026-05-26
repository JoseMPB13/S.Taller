<?php

namespace App\Models;

use Config\Database;
use PDO;

/**
 * Modelo de Clientes (RF-002)
 * Gestiona el CRUD y la persistencia de datos de clientes con soporte para eliminación lógica.
 */
class Client {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene todos los clientes activos del sistema (no eliminados lógicamente).
     * Soporta filtrado/búsqueda por nombre, apellido o documento.
     * 
     * @param string|null $search Término de búsqueda
     * @return array
     */
    public function getAll(?string $search = null): array {
        $sql = "SELECT * FROM clientes WHERE deleted_at IS NULL";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (nombres LIKE :search_nom 
                        OR apellidos LIKE :search_ape 
                        OR documento LIKE :search_doc)";
            $searchVal = '%' . trim($search) . '%';
            $params['search_nom'] = $searchVal;
            $params['search_ape'] = $searchVal;
            $params['search_doc'] = $searchVal;
        }

        $sql .= " ORDER BY nombres ASC, apellidos ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un cliente activo por su ID.
     * 
     * @param int $id ID del cliente
     * @return array|false Datos del cliente o falso si no existe/fue eliminado
     */
    public function getById(int $id) {
        $sql = "SELECT * FROM clientes WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Registra un nuevo cliente en el sistema.
     * 
     * @param array $data Datos del cliente
     * @return bool Verdadero si se guardó correctamente
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO clientes (nombres, apellidos, documento, telefono, correo, direccion, observaciones, datos_facturacion, estado) 
                VALUES (:nombres, :apellidos, :documento, :telefono, :correo, :direccion, :observaciones, :datos_facturacion, :estado)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombres'           => $data['nombres'],
            'apellidos'         => $data['apellidos'],
            'documento'         => $data['documento'],
            'telefono'          => $data['telefono'],
            'correo'            => $data['correo'] ?: null,
            'direccion'         => $data['direccion'] ?: null,
            'observaciones'     => $data['observaciones'] ?: null,
            'datos_facturacion' => $data['datos_facturacion'] ?: null,
            'estado'            => $data['estado'] ?? 'activo'
        ]);
    }

    /**
     * Actualiza la información de un cliente existente.
     * 
     * @param int $id ID del cliente
     * @param array $data Nuevos datos del cliente
     * @return bool Verdadero si la actualización fue exitosa
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE clientes SET 
                    nombres = :nombres, 
                    apellidos = :apellidos, 
                    documento = :documento, 
                    telefono = :telefono, 
                    correo = :correo, 
                    direccion = :direccion, 
                    observaciones = :observaciones, 
                    datos_facturacion = :datos_facturacion, 
                    estado = :estado 
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id'                => $id,
            'nombres'           => $data['nombres'],
            'apellidos'         => $data['apellidos'],
            'documento'         => $data['documento'],
            'telefono'          => $data['telefono'],
            'correo'            => $data['correo'] ?: null,
            'direccion'         => $data['direccion'] ?: null,
            'observaciones'     => $data['observaciones'] ?: null,
            'datos_facturacion' => $data['datos_facturacion'] ?: null,
            'estado'            => $data['estado'] ?? 'activo'
        ]);
    }

    /**
     * Realiza la desactivación y baja lógica (Soft Delete) del cliente.
     * 
     * @param int $id ID del cliente
     * @return bool Verdadero si la eliminación lógica fue exitosa
     */
    public function deleteLogically(int $id): bool {
        $sql = "UPDATE clientes SET estado = 'inactivo', deleted_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Verifica si un documento de identidad ya está registrado por otro cliente.
     * 
     * @param string $documento Documento a verificar
     * @param int|null $excludeId ID de cliente a excluir de la consulta
     * @return bool Verdadero si el documento ya está registrado
     */
    public function existsDocument(string $documento, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM clientes WHERE documento = :documento AND deleted_at IS NULL";
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
     * Cuenta el total de clientes activos.
     */
    public function countAll(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM clientes WHERE estado = 'activo' AND deleted_at IS NULL");
        return (int)$stmt->fetchColumn();
    }
}
