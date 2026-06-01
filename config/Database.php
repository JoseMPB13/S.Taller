<?php

namespace Config;

use PDO;
use PDOException;
use Exception;

/**
 * Clase Database (Patrón Singleton)
 * Proporciona una única conexión PDO segura para todo el ciclo de vida de la aplicación.
 */
class Database {
    private static $instance = null;
    private $connection;

    // Constructor privado para evitar instanciación externa
    private function __construct() {
        $config = require __DIR__ . '/config.php';
        $dbConfig = $config['db'];
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Sentencias preparadas nativas para seguridad contra SQL Injection
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4", // Forzar codificación UTF-8 en la comunicación con MySQL
        ];

        try {
            $this->connection = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
        } catch (PDOException $e) {

            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la instancia única de la clase Database.
     * 
     * @return Database
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene la conexión PDO activa.
     * 
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    // Prevenir la clonación del objeto Singleton
    private function __clone() {}

    // Prevenir la deserialización del objeto Singleton
    public function __wakeup() {
        throw new Exception("No se puede deserializar una instancia de Singleton.");
    }
}
