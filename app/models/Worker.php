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
        $sql = "SELECT t.id, t.nombres, t.apellidos, t.documento, t.nivel, t.contacto, t.disponibilidad, t.estado, t.deleted_at,
                       GROUP_CONCAT(te.especialidad SEPARATOR ', ') AS especialidades 
                FROM trabajadores t
                LEFT JOIN trabajador_especialidades te ON t.id = te.trabajador_id
                WHERE t.deleted_at IS NULL";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (t.nombres LIKE :search_nom 
                        OR t.apellidos LIKE :search_ape 
                        OR t.documento LIKE :search_doc 
                        OR t.id IN (SELECT DISTINCT trabajador_id FROM trabajador_especialidades WHERE especialidad LIKE :search_esp))";
            $searchVal = '%' . trim($search) . '%';
            $params['search_nom'] = $searchVal;
            $params['search_ape'] = $searchVal;
            $params['search_doc'] = $searchVal;
            $params['search_esp'] = $searchVal;
        }

        $sql .= " GROUP BY t.id ORDER BY t.nombres ASC, t.apellidos ASC";
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
        $sql = "SELECT t.id, t.nombres, t.apellidos, t.documento, t.nivel, t.contacto, t.disponibilidad, t.estado, t.deleted_at,
                       GROUP_CONCAT(te.especialidad SEPARATOR ', ') AS especialidades 
                FROM trabajadores t
                LEFT JOIN trabajador_especialidades te ON t.id = te.trabajador_id
                WHERE t.id = :id AND t.deleted_at IS NULL
                GROUP BY t.id";
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
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO trabajadores (nombres, apellidos, documento, nivel, contacto, disponibilidad, estado) 
                    VALUES (:nombres, :apellidos, :documento, :nivel, :contacto, :disponibilidad, :estado)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'nombres'        => trim($data['nombres']),
                'apellidos'      => trim($data['apellidos']),
                'documento'      => trim($data['documento']),
                'nivel'          => $data['nivel'] ?? 'Junior',
                'contacto'       => !empty($data['contacto']) ? trim($data['contacto']) : null,
                'disponibilidad' => $data['disponibilidad'] ?? 'disponible',
                'estado'         => $data['estado'] ?? 'activo'
            ]);

            $trabajador_id = $this->db->lastInsertId();

            // Insertar especialidades en la tabla pivot trabajador_especialidades
            if (!empty($data['especialidades'])) {
                $especialidadesArray = explode(',', $data['especialidades']);
                $sqlPivot = "INSERT INTO trabajador_especialidades (trabajador_id, especialidad) VALUES (:trabajador_id, :especialidad)";
                $stmtPivot = $this->db->prepare($sqlPivot);
                foreach ($especialidadesArray as $esp) {
                    $espTrim = trim($esp);
                    if ($espTrim !== '') {
                        $stmtPivot->execute([
                            'trabajador_id' => $trabajador_id,
                            'especialidad'  => $espTrim
                        ]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    /**
     * Actualiza los datos de un trabajador existente.
     * 
     * @param int $id ID del trabajador
     * @param array $data Nuevos datos
     * @return bool
     */
    public function update(int $id, array $data): bool {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE trabajadores SET 
                        nombres = :nombres, 
                        apellidos = :apellidos, 
                        documento = :documento, 
                        nivel = :nivel, 
                        contacto = :contacto, 
                        disponibilidad = :disponibilidad, 
                        estado = :estado 
                    WHERE id = :id AND deleted_at IS NULL";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id'             => $id,
                'nombres'        => trim($data['nombres']),
                'apellidos'      => trim($data['apellidos']),
                'documento'      => trim($data['documento']),
                'nivel'          => $data['nivel'] ?? 'Junior',
                'contacto'       => !empty($data['contacto']) ? trim($data['contacto']) : null,
                'disponibilidad' => $data['disponibilidad'] ?? 'disponible',
                'estado'         => $data['estado'] ?? 'activo'
            ]);

            // Eliminar especialidades antiguas de la tabla pivot
            $sqlDelete = "DELETE FROM trabajador_especialidades WHERE trabajador_id = :trabajador_id";
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute(['trabajador_id' => $id]);

            // Insertar nuevas especialidades en la tabla pivot
            if (!empty($data['especialidades'])) {
                $especialidadesArray = explode(',', $data['especialidades']);
                $sqlPivot = "INSERT INTO trabajador_especialidades (trabajador_id, especialidad) VALUES (:trabajador_id, :especialidad)";
                $stmtPivot = $this->db->prepare($sqlPivot);
                foreach ($especialidadesArray as $esp) {
                    $espTrim = trim($esp);
                    if ($espTrim !== '') {
                        $stmtPivot->execute([
                            'trabajador_id' => $id,
                            'especialidad'  => $espTrim
                        ]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
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
