<?php
// Configuración global del sistema

// Cargador nativo simple de variables de entorno desde el archivo .env
$rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
$envFile = $rootPath . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar líneas que comiencen con comentarios (#)
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Separar la clave y el valor por el primer signo '='
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            
            // Remover comillas envolventes simples o dobles del valor si existen
            if (preg_match('/^"(.*)"$/', $val, $matches) || preg_match('/^\'(.*)\'$/', $val, $matches)) {
                $val = $matches[1];
            }
            
            // Registrar en variables de entorno, $_ENV y $_SERVER
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

return [
    'db' => [
        'host'    => getenv('DB_HOST') ?: 'localhost',
        'name'    => getenv('DB_NAME') ?: 'taller_mecanico',
        'user'    => getenv('DB_USER') ?: 'root',
        'pass'    => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4'
    ]
];

