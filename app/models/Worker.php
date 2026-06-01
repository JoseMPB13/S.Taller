<?php

namespace App\Models;

use Config\Database;
use PDO;

/**
 * Modelo de Trabajadores (RF-003)
 * Gestiona la base de datos de mecánicos y personal técnico del taller.
 */
class Worker extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Obtiene el listado de trabajadores activos del sistema.
     * Soporta búsqueda por nombres, apellidos, documento o especialidad.
     * 
     * @param string|null $search Término de búsqueda
     * @return array
     */
    public function getAll(?string $search = null): array {
        $sql = "SELECT * FROM trabajadores WHERE deleted_at IS NULL";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (nombres LIKE :search_nom 
                        OR apellidos LIKE :search_ape 
                        OR documento LIKE :search_doc 
                        OR especialidades LIKE :search_esp)";
            $searchVal = '%' . trim($search) . '%';
            $params['search_nom'] = $searchVal;
            $params['search_ape'] = $searchVal;
            $params['search_doc'] = $searchVal;
            $params['search_esp'] = $searchVal;
        }

        $sql .= " ORDER BY nombres ASC, apellidos ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un trabajador activo por su ID.
     * 
     * @param int $id ID del trabajador
     * @return array|false Datos del trabajador o falso si no existe/fue eliminado
     */
    public function getById(int $id) {
        $sql = "SELECT * FROM trabajadores WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Registra un nuevo trabajador en el sistema.
     * 
     * @param array $data Datos del trabajador
     * @return bool
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO trabajadores (nombres, apellidos, documento, especialidades, nivel, contacto, disponibilidad, costo_hora, estado) 
                VALUES (:nombres, :apellidos, :documento, :especialidades, :nivel, :contacto, :disponibilidad, :costo_hora, :estado)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombres'        => trim($data['nombres']),
            'apellidos'      => trim($data['apellidos']),
            'documento'      => trim($data['documento']),
            'especialidades' => trim($data['especialidades']),
            'nivel'          => $data['nivel'] ?? 'Junior',
            'contacto'       => !empty($data['contacto']) ? trim($data['contacto']) : null,
            'disponibilidad' => $data['disponibilidad'] ?? 'disponible',
            'costo_hora'     => (float)($data['costo_hora'] ?? 0.0),
            'estado'         => $data['estado'] ?? 'activo'
        ]);
    }

    /**
     * Actualiza los datos de un trabajador existente.
     * 
     * @param int $id ID del trabajador
     * @param array $data Nuevos datos
     * @return bool
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE trabajadores SET 
                    nombres = :nombres, 
                    apellidos = :apellidos, 
                    documento = :documento, 
                    especialidades = :especialidades, 
                    nivel = :nivel, 
                    contacto = :contacto, 
                    disponibilidad = :disponibilidad, 
                    costo_hora = :costo_hora, 
                    estado = :estado 
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id'             => $id,
            'nombres'        => trim($data['nombres']),
            'apellidos'      => trim($data['apellidos']),
            'documento'      => trim($data['documento']),
            'especialidades' => trim($data['especialidades']),
            'nivel'          => $data['nivel'] ?? 'Junior',
            'contacto'       => !empty($data['contacto']) ? trim($data['contacto']) : null,
            'disponibilidad' => $data['disponibilidad'] ?? 'disponible',
            'costo_hora'     => (float)($data['costo_hora'] ?? 0.0),
            'estado'         => $data['estado'] ?? 'activo'
        ]);
    }

    /**
     * Realiza la baja lógica (Soft Delete) del trabajador.
     * 
     * @param int $id ID del trabajador
     * @return bool
     */
    public function deleteLogically(int $id): bool {
        $sql = "UPDATE trabajadores SET estado = 'inactivo', deleted_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Verifica si un documento de identidad ya está registrado por otro trabajador.
     * 
     * @param string $documento Documento de identidad
     * @param int|null $excludeId ID a excluir
     * @return bool
     */
    public function existsDocument(string $documento, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM trabajadores WHERE documento = :documento AND deleted_at IS NULL";
        $params = ['documento' => trim($documento)];

        if ($excludeId !== null) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
}
