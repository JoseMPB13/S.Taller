<?php

namespace App\Models;

use Config\Database;
use PDO;

/**
 * Modelo de Autos (RF-004 y RF-011)
 * Gestiona el CRUD y las validaciones de vehículos del taller.
 */
class Car {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene la lista de autos activos con los datos de sus propietarios.
     * Soporta buscador por placa, marca, modelo o datos del cliente.
     * 
     * @param string|null $search Término de búsqueda
     * @return array
     */
    public function getAll(?string $search = null): array {
        $sql = "SELECT a.*, CONCAT(c.nombres, ' ', c.apellidos) AS propietario_nombre, c.documento AS propietario_documento
                FROM autos a
                INNER JOIN clientes c ON a.cliente_id = c.id
                WHERE a.deleted_at IS NULL";
        
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (a.placa LIKE :search_placa 
                        OR a.marca LIKE :search_marca 
                        OR a.modelo LIKE :search_modelo 
                        OR c.nombres LIKE :search_nom 
                        OR c.apellidos LIKE :search_ape 
                        OR c.documento LIKE :search_doc)";
            $searchVal = '%' . trim($search) . '%';
            $params['search_placa'] = $searchVal;
            $params['search_marca'] = $searchVal;
            $params['search_modelo'] = $searchVal;
            $params['search_nom'] = $searchVal;
            $params['search_ape'] = $searchVal;
            $params['search_doc'] = $searchVal;
        }

        $sql .= " ORDER BY a.placa ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los detalles de un auto por su ID.
     * 
     * @param int $id ID del auto
     * @return array|false Datos del auto o falso si no existe/fue eliminado
     */
    public function getById(int $id) {
        $sql = "SELECT a.*, CONCAT(c.nombres, ' ', c.apellidos) AS propietario_nombre 
                FROM autos a 
                INNER JOIN clientes c ON a.cliente_id = c.id
                WHERE a.id = :id AND a.deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Registra un nuevo vehículo en el sistema.
     * 
     * @param array $data Datos del vehículo
     * @return bool
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO autos (cliente_id, placa, vin, marca, modelo, anio, color, kilometraje, observaciones, estado) 
                VALUES (:cliente_id, :placa, :vin, :marca, :modelo, :anio, :color, :kilometraje, :observaciones, :estado)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'cliente_id'    => $data['cliente_id'],
            'placa'         => strtoupper(trim($data['placa'])),
            'vin'           => !empty($data['vin']) ? strtoupper(trim($data['vin'])) : null,
            'marca'         => trim($data['marca']),
            'modelo'        => trim($data['modelo']),
            'anio'          => (int)$data['anio'],
            'color'         => !empty($data['color']) ? trim($data['color']) : null,
            'kilometraje'   => (int)$data['kilometraje'],
            'observaciones' => !empty($data['observaciones']) ? trim($data['observaciones']) : null,
            'estado'        => $data['estado'] ?? 'activo'
        ]);
    }

    /**
     * Actualiza los datos de un vehículo existente.
     * 
     * @param int $id ID del vehículo
     * @param array $data Nuevos datos
     * @return bool
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE autos SET 
                    cliente_id = :cliente_id, 
                    placa = :placa, 
                    vin = :vin, 
                    marca = :marca, 
                    modelo = :modelo, 
                    anio = :anio, 
                    color = :color, 
                    kilometraje = :kilometraje, 
                    observaciones = :observaciones, 
                    estado = :estado 
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id'            => $id,
            'cliente_id'    => $data['cliente_id'],
            'placa'         => strtoupper(trim($data['placa'])),
            'vin'           => !empty($data['vin']) ? strtoupper(trim($data['vin'])) : null,
            'marca'         => trim($data['marca']),
            'modelo'        => trim($data['modelo']),
            'anio'          => (int)$data['anio'],
            'color'         => !empty($data['color']) ? trim($data['color']) : null,
            'kilometraje'   => (int)$data['kilometraje'],
            'observaciones' => !empty($data['observaciones']) ? trim($data['observaciones']) : null,
            'estado'        => $data['estado'] ?? 'activo'
        ]);
    }

    /**
     * Realiza la baja lógica (Soft Delete) del vehículo.
     * 
     * @param int $id ID del vehículo
     * @return bool
     */
    public function deleteLogically(int $id): bool {
        $sql = "UPDATE autos SET estado = 'inactivo', deleted_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Verifica si una placa ya está registrada en el sistema.
     * 
     * @param string $placa Placa del auto
     * @param int|null $excludeId ID a excluir
     * @return bool
     */
    public function existsPlaca(string $placa, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM autos WHERE placa = :placa AND deleted_at IS NULL";
        $params = ['placa' => strtoupper(trim($placa))];

        if ($excludeId !== null) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
}
