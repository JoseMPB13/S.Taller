<?php

namespace App\Models;

use Config\Database;
use PDO;

/**
 * Modelo de Servicios (RF-007)
 * Gestiona el catálogo de servicios ofrecidos, tiempos y tarifas de mano de obra.
 */
class Service {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene todos los servicios activos del catálogo.
     * Soporta buscador por nombre de servicio o descripción.
     * 
     * @param string|null $search Término de búsqueda
     * @return array
     */
    public function getAll(?string $search = null): array {
        $sql = "SELECT * FROM servicios WHERE deleted_at IS NULL";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (nombre_servicio LIKE :search_name 
                        OR descripcion LIKE :search_desc)";
            $searchVal = '%' . trim($search) . '%';
            $params['search_name'] = $searchVal;
            $params['search_desc'] = $searchVal;
        }

        $sql .= " ORDER BY nombre_servicio ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los detalles de un servicio por su ID.
     * 
     * @param int $id ID del servicio
     * @return array|false Datos del servicio o falso si no existe
     */
    public function getById(int $id) {
        $sql = "SELECT * FROM servicios WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Registra un nuevo servicio en el catálogo.
     * 
     * @param array $data Datos del servicio
     * @return bool
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO servicios (nombre_servicio, descripcion, tiempo_estimado, precio_base, impuestos_descuentos, estado) 
                VALUES (:nombre_servicio, :descripcion, :tiempo_estimado, :precio_base, :impuestos_descuentos, :estado)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombre_servicio'      => trim($data['nombre_servicio']),
            'descripcion'          => !empty($data['descripcion']) ? trim($data['descripcion']) : null,
            'tiempo_estimado'      => (int)($data['tiempo_estimado'] ?? 0),
            'precio_base'          => (float)($data['precio_base'] ?? 0.0),
            'impuestos_descuentos' => $data['impuestos_descuentos'] ?? null,
            'estado'               => $data['estado'] ?? 'activo'
        ]);
    }

    /**
     * Actualiza un servicio del catálogo existente.
     * 
     * @param int $id ID del servicio
     * @param array $data Nuevos datos
     * @return bool
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE servicios SET 
                    nombre_servicio = :nombre_servicio, 
                    descripcion = :descripcion, 
                    tiempo_estimado = :tiempo_estimado, 
                    precio_base = :precio_base, 
                    impuestos_descuentos = :impuestos_descuentos, 
                    estado = :estado 
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id'                   => $id,
            'nombre_servicio'      => trim($data['nombre_servicio']),
            'descripcion'          => !empty($data['descripcion']) ? trim($data['descripcion']) : null,
            'tiempo_estimado'      => (int)($data['tiempo_estimado'] ?? 0),
            'precio_base'          => (float)($data['precio_base'] ?? 0.0),
            'impuestos_descuentos' => $data['impuestos_descuentos'] ?? null,
            'estado'               => $data['estado'] ?? 'activo'
        ]);
    }

    /**
     * Realiza la baja lógica (Soft Delete) del servicio.
     * 
     * @param int $id ID del servicio
     * @return bool
     */
    public function deleteLogically(int $id): bool {
        $sql = "UPDATE servicios SET estado = 'inactivo', deleted_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
