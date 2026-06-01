<?php

namespace App\Helpers;

/**
 * Helper de Autenticación, Control de Roles y CSRF (RF-008)
 * Implementa middlewares de verificación de sesión, roles y mitigación de ataques CSRF.
 */
class AuthHelper {
    
    /**
     * Destruye de forma segura la sesión y limpia las cookies de sesión.
     */
    public static function destroySession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Inicializa la sesión si no está ya activa, aplicando configuraciones de seguridad.
     */
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar seguridad en cookies de sesión
            ini_set('session.cookie_httponly', 1); // Previene acceso a cookies por javascript
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict'); // Mitiga CSRF en navegadores modernos
            
            // Forzar cookie de sesión segura en HTTPS/producción
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            if ($isSecure) {
                ini_set('session.cookie_secure', 1);
            }
            
            session_start();
        }

        // Control de inactividad de sesión (30 minutos = 1800 segundos)
        if (isset($_SESSION['user_id'])) {
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
                self::destroySession();
                header('Location: ' . BASE_URL . '/login');
                exit();
            }
            $_SESSION['last_activity'] = time(); // Renovar marca de tiempo de actividad
        }
    }

    /**
     * Comprueba si el usuario actual ha iniciado sesión.
     * 
     * @return bool Verdadero si está autenticado
     */
    public static function isLoggedIn(): bool {
        self::initSession();
        return isset($_SESSION['user_id']);
    }

    /**
     * Obtiene los datos del usuario logueado en la sesión.
     * 
     * @return array|null Datos de sesión o nulo
     */
    public static function user() {
        self::initSession();
        return $_SESSION['user'] ?? null;
    }

    /**
     * Verifica si el usuario logueado posee un rol determinado.
     * 
     * @param string $rol Nombre del rol a validar (insensible a mayúsculas/minúsculas)
     * @return bool Verdadero si tiene el rol
     */
    public static function hasRole(string $rol): bool {
        self::initSession();
        return isset($_SESSION['user_rol']) && strtolower($_SESSION['user_rol']) === strtolower($rol);
    }

    /**
     * Verifica si el usuario logueado es Administrador.
     * 
     * @return bool Verdadero si es Administrador
     */
    public static function isAdmin(): bool {
        return self::hasRole('Administrador');
    }

    /**
     * Middleware de redirección: Exige que el usuario esté logueado.
     * Si no, redirige a la vista de login.
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login');
            exit();
        }
    }

    /**
     * Middleware de redirección: Exige que el usuario sea Administrador.
     * Si no, detiene la ejecución y muestra una vista elegante de acceso restringido.
     */
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            require_once ROOT_PATH . '/app/views/errors/403.php';
            exit();
        }
    }

    /**
     * Genera y retorna un token CSRF seguro para el formulario actual.
     * 
     * @return string Token CSRF
     */
    public static function getCsrfToken(): string {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Genera y retorna un token CSRF seguro (alias de getCsrfToken).
     * 
     * @return string Token CSRF
     */
    public static function generateCsrf(): string {
        return self::getCsrfToken();
    }

    /**
     * Valida si el token CSRF enviado coincide con el guardado en la sesión.
     * 
     * @param string|null $token Token recibido de la petición
     * @return bool Verdadero si el token es válido
     */
    public static function validateCsrf(?string $token): bool {
        self::initSession();
        if (!$token || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
