<?php

namespace App\Controllers;

use App\Models\User;
use App\Helpers\AuthHelper;

/**
 * Controlador de Autenticación (RF-008)
 * Gestiona el inicio de sesión, la verificación de credenciales y el cierre de sesión seguro.
 */
class AuthController {
    private $userModel;

    public function __construct() {
        AuthHelper::initSession();
        $this->userModel = new User();
    }

    /**
     * Muestra la vista de login o procesa la autenticación del usuario.
     */
    public function login() {
        // Redirigir a usuarios si ya ha iniciado sesión
        if (AuthHelper::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/usuarios');
            exit();
        }

        // Si es una petición POST, procesar el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf_token = $_POST['csrf_token'] ?? null;

            // 1. Mitigación CSRF: Validar el token enviado
            if (!AuthHelper::validateCsrf($csrf_token)) {
                $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
                header('Location: ' . BASE_URL . '/login');
                exit();
            }

            // Capturar datos y sanitizar
            $loginInput = trim(filter_input(INPUT_POST, 'login', FILTER_SANITIZE_SPECIAL_CHARS));
            $contrasena = $_POST['contrasena'] ?? '';

            if (empty($loginInput) || empty($contrasena)) {
                $_SESSION['error'] = "Todos los campos son obligatorios.";
                header('Location: ' . BASE_URL . '/login');
                exit();
            }

            // 2. Buscar usuario por nombre de usuario o por email
            $user = $this->userModel->getByUsernameOrEmail($loginInput);

            // 3. Validar contraseña usando password_verify
            if ($user && password_verify($contrasena, $user['contrasena'])) {
                // Regenerar el ID de sesión para prevenir Session Fixation (Ataque de fijación de sesión)
                session_regenerate_id(true);

                // Guardar datos en la sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nombre'] = $user['nombre'];
                $_SESSION['user_usuario'] = $user['usuario'];
                $_SESSION['user_rol'] = $user['rol_nombre'];
                $_SESSION['user_rol_id'] = $user['rol_id'];

                $_SESSION['success'] = "¡Bienvenido de nuevo, " . htmlspecialchars($user['nombre']) . "!";
                header('Location: ' . BASE_URL . '/usuarios');
                exit();
            } else {
                $_SESSION['error'] = "Credenciales incorrectas o usuario inactivo.";
                $_SESSION['form_login_value'] = $loginInput;
                header('Location: ' . BASE_URL . '/login');
                exit();
            }
        }

        // Si es GET, cargar la vista de login
        require_once ROOT_PATH . '/app/views/auth/login.php';
    }

    /**
     * Destruye de forma segura la sesión actual del usuario.
     */
    public function logout() {
        AuthHelper::initSession();

        // Limpiar todas las variables de sesión
        $_SESSION = [];

        // Destruir la cookie de sesión si existe
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

        // Destruir la sesión en servidor
        session_destroy();

        // Redirigir al formulario de login
        header('Location: ' . BASE_URL . '/login');
        exit();
    }
}
