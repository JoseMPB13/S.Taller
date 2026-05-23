<?php
/**
 * Front Controller del Sistema de Gestión de Taller Mecánico
 * Inicializa el cargador de clases (Autoloader) y arranca la aplicación.
 */

// Habilitar despliegue de errores en desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir constante del directorio raíz de la aplicación
define('ROOT_PATH', dirname(__DIR__));

// Registrar spl_autoload_register para el cargador de clases automático (Autoloader)
spl_autoload_register(function ($class) {
    // 1. Mapeo de Namespaces a Directorios Físicos
    // Ejemplo: App\Controllers\HomeController -> ROOT_PATH/app/controllers/HomeController.php
    if (strpos($class, 'App\\') === 0) {
        $relativeClass = substr($class, 4);
        // Desglosamos por subcarpetas (ej: Controllers/HomeController -> app/controllers/HomeController.php)
        $parts = explode('\\', $relativeClass);
        // Buscamos convertir la primera carpeta del namespace a minúsculas
        // (por ejemplo: Controllers -> controllers, Models -> models, Views -> views)
        if (count($parts) > 0) {
            $parts[0] = strtolower($parts[0]);
        }
        $relativePath = implode(DIRECTORY_SEPARATOR, $parts);
        $file = ROOT_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $relativePath . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // 2. Mapeo de Config\ a /config
    if (strpos($class, 'Config\\') === 0) {
        $relativeClass = substr($class, 7);
        $file = ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // 3. Fallback: Búsqueda directa en carpetas para compatibilidad sin namespaces
    $directories = [
        ROOT_PATH . '/app/controllers/',
        ROOT_PATH . '/app/models/',
        ROOT_PATH . '/config/'
    ];

    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Prueba de conexión e integración del Autoloader con Database
try {
    $db = \Config\Database::getInstance()->getConnection();
    echo "<h1>¡Estructura MVC y conexión a BD exitosas!</h1>";
    echo "<p>El Autoloader ha cargado correctamente la clase <strong>Config\\Database</strong> y se ha establecido la conexión PDO de forma segura.</p>";
} catch (\Exception $e) {
    echo "<h1>Error en configuración: " . $e->getMessage() . "</h1>";
}
