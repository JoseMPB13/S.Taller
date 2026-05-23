<?php

namespace App\Helpers;

/**
 * Helper de Autenticación, Control de Roles y CSRF (RF-008)
 * Implementa middlewares de verificación de sesión, roles y mitigación de ataques CSRF.
 */
class AuthHelper {
    
    /**
     * Inicializa la sesión si no está ya activa, aplicando configuraciones de seguridad.
     */
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar seguridad en cookies de sesión
            ini_set('session.cookie_httponly', 1); // Previene acceso a cookies por javascript
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict'); // Mitiga CSRF en navegadores modernos
            
            session_start();
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
            require_once ROOT_PATH . '/app/views/layout/header.php';
            echo '<div class="card" style="max-width:600px; margin: 4rem auto; text-align:center; padding: 3rem;">
                    <h1 style="color:var(--danger); margin-bottom:1rem;">403 - Acceso Denegado</h1>
                    <p style="color:var(--text-muted); margin-bottom: 2rem;">No tienes los permisos necesarios para acceder a este módulo de administración.</p>
                    <a href="' . BASE_URL . '/usuarios" class="btn btn-primary">Volver al Inicio</a>
                  </div>';
            require_once ROOT_PATH . '/app/views/layout/footer.php';
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
