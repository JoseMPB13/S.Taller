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
        
        // Detectar motor de base de datos basándose en el puerto o variable DB_CONNECTION
        $connectionType = getenv('DB_CONNECTION') ?: '';
        $driver = ($connectionType === 'pgsql' || $dbConfig['port'] == 5432 || $dbConfig['port'] == 6543) ? 'pgsql' : 'mysql';
        
        if ($driver === 'pgsql') {
            $dsn = "pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']}";
        } else {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
        }
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Sentencias preparadas nativas para seguridad contra SQL Injection
        ];

        // Añadir comando de inicialización específico de MySQL si aplica
        if ($driver === 'mysql' && defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES " . ($dbConfig['charset'] ?: 'utf8mb4');
        }
 
        try {
            $this->connection = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
        } catch (PDOException $e) {
            throw new Exception("Error de conexión a la base de datos ({$driver}): " . $e->getMessage());
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
