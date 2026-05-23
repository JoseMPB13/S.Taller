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
define('BASE_URL', '/taller');

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

// Enrutamiento simple (Front Controller Routing)
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'usuarios';
$urlParts = explode('/', $url);

$currentRoute = strtolower($urlParts[0] ?? 'usuarios');

// 1. Inicializar sesión de forma segura
\App\Helpers\AuthHelper::initSession();

// 2. Control de Acceso (Middleware Global): Solo la ruta 'login' es pública
$publicRoutes = ['login'];
if (!in_array($currentRoute, $publicRoutes)) {
    \App\Helpers\AuthHelper::requireLogin();
}

// 3. Determinar el controlador
$controllerName = 'Usuarios';
if (!empty($urlParts[0])) {
    $controllerName = ucfirst($urlParts[0]);
}

// Mapear el nombre a la clase del controlador
if ($controllerName === 'Login' || $controllerName === 'Logout' || $controllerName === 'Auth') {
    $controllerClass = 'App\\Controllers\\AuthController';
    // Forzar la acción según la URL amigable
    if (strtolower($controllerName) === 'login') {
        $action = 'login';
    } elseif (strtolower($controllerName) === 'logout') {
        $action = 'logout';
    } else {
        $action = isset($urlParts[1]) && !empty($urlParts[1]) ? $urlParts[1] : 'login';
    }
} else {
    if ($controllerName === 'Usuarios' || $controllerName === 'Users' || $controllerName === 'Usuario') {
        $controllerClass = 'App\\Controllers\\UserController';
    } elseif ($controllerName === 'Clientes' || $controllerName === 'Clients' || $controllerName === 'Cliente') {
        $controllerClass = 'App\\Controllers\\ClientController';
    } else {
        $controllerClass = 'App\\Controllers\\' . $controllerName . 'Controller';
    }
    
    // Determinar la acción (método)
    $action = 'index';
    if (isset($urlParts[1]) && !empty($urlParts[1])) {
        $action = $urlParts[1];
    }
}

// Parámetros adicionales
$params = array_slice($urlParts, 2);

// Instanciar y ejecutar
if (class_exists($controllerClass)) {
    $controllerInstance = new $controllerClass();
    if (method_exists($controllerInstance, $action)) {
        call_user_func_array([$controllerInstance, $action], $params);
    } else {
        http_response_code(404);
        echo "<h1>404 - Acción no encontrada</h1>";
        echo "<p>La acción '{$action}' no existe en el controlador '{$controllerClass}'.</p>";
    }
} else {
    http_response_code(404);
    echo "<h1>404 - Recurso no encontrado</h1>";
    echo "<p>El controlador '{$controllerClass}' no se encuentra en el sistema.</p>";
}
