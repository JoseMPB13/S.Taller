<?php

namespace App\Models;

use Config\Database;

/**
 * Modelo Base Abstracto (BaseModel)
 * Centraliza la conexión a la base de datos para evitar la duplicación de código (DRY).
 */
abstract class BaseModel {
    /**
     * Instancia de conexión PDO compartida para los modelos hijos.
     * @var \PDO
     */
    protected $db;

    /**
     * Constructor del modelo base.
     * Inicializa la conexión PDO única usando el patrón Singleton de Database.
     */
    protected function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
}
