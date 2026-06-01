<?php

namespace App\Models;

use Config\Database;
use PDO;

/**
 * Modelo de Rol (Fase 3: Soporte para asignar roles a usuarios)
 */
class Role extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Obtiene todos los roles activos en el sistema.
     * 
     * @return array Lista de roles
     */
    public function getAll(): array {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE estado = 1 ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
