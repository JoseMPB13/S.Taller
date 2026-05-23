<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Role;

/**
 * Controlador de Usuarios (RF-001)
 * Gestiona el flujo del CRUD de usuarios e interacciona con los modelos correspondientes.
 */
class UserController {
    private $userModel;
    private $roleModel;

    public function __construct() {
        // Inicializar sesión si no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userModel = new User();
        $this->roleModel = new Role();
    }

    /**
     * Muestra el listado general de usuarios y el formulario de creación.
     */
    public function index() {
        $users = $this->userModel->getAll();
        $roles = $this->roleModel->getAll();
        
        // Incluir la vista index
        require_once ROOT_PATH . '/app/views/users/index.php';
    }

    /**
     * Procesa la inserción de un nuevo usuario con validaciones del lado del servidor.
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/usuarios');
            exit();
        }

        // Sanitización y captura de datos
        $rol_id = filter_input(INPUT_POST, 'rol_id', FILTER_VALIDATE_INT);
        $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS));
        $documento = trim(filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_SPECIAL_CHARS));
        $correo = trim(filter_input(INPUT_POST, 'correo', FILTER_VALIDATE_EMAIL));
        $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS));
        $usuario = trim(filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_SPECIAL_CHARS));
        $contrasena = $_POST['contrasena'] ?? '';
        $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

        $errors = [];

        // Validaciones de campos obligatorios
        if (!$rol_id) $errors[] = "El rol es obligatorio.";
        if (empty($nombre)) $errors[] = "El nombre es obligatorio.";
        if (empty($documento)) $errors[] = "El documento de identidad es obligatorio.";
        if (!$correo) $errors[] = "El correo electrónico es obligatorio o el formato no es válido.";
        if (empty($usuario)) $errors[] = "El nombre de usuario es obligatorio.";
        if (empty($contrasena)) $errors[] = "La contraseña es obligatoria.";
        if ($contrasena !== $confirmar_contrasena) $errors[] = "Las contraseñas no coinciden.";

        // Validaciones de unicidad contra la base de datos
        if (empty($errors)) {
            if ($this->userModel->existsDocument($documento)) {
                $errors[] = "El documento de identidad ya está registrado por otro usuario.";
            }
            if ($this->userModel->existsUsername($usuario)) {
                $errors[] = "El nombre de usuario ya está en uso.";
            }
            if ($this->userModel->existsEmail($correo)) {
                $errors[] = "El correo electrónico ya está registrado.";
            }
        }

        // Manejo de errores
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST; // Preservar datos en el formulario
            header('Location: ' . BASE_URL . '/usuarios');
            exit();
        }

        // Estructura de inserción
        $data = [
            'rol_id'     => $rol_id,
            'nombre'     => $nombre,
            'documento'  => $documento,
            'correo'     => $correo,
            'telefono'   => $telefono,
            'usuario'    => $usuario,
            'contrasena' => $contrasena,
            'estado'     => 'activo'
        ];

        if ($this->userModel->create($data)) {
            $_SESSION['success'] = "Usuario registrado exitosamente.";
            unset($_SESSION['form_data']); // Limpiar buffer de formulario
        } else {
            $_SESSION['error'] = "Hubo un problema al guardar el usuario en el sistema.";
        }

        header('Location: ' . BASE_URL . '/usuarios');
        exit();
    }

    /**
     * Muestra el formulario para editar un usuario.
     * 
     * @param string|int $id ID del usuario a editar
     */
    public function editar($id) {
        $id = (int)$id;
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $_SESSION['error'] = "Usuario no encontrado o dado de baja.";
            header('Location: ' . BASE_URL . '/usuarios');
            exit();
        }

        $roles = $this->roleModel->getAll();
        
        // Incluir la vista de edición
        require_once ROOT_PATH . '/app/views/users/edit.php';
    }

    /**
     * Procesa la actualización de un usuario existente.
     * 
     * @param string|int $id ID del usuario a actualizar
     */
    public function actualizar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/usuarios');
            exit();
        }

        $id = (int)$id;
        $user = $this->userModel->getById($id);

        if (!$user) {
            $_SESSION['error'] = "Usuario no encontrado.";
            header('Location: ' . BASE_URL . '/usuarios');
            exit();
        }

        // Sanitización y captura de datos
        $rol_id = filter_input(INPUT_POST, 'rol_id', FILTER_VALIDATE_INT);
        $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS));
        $documento = trim(filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_SPECIAL_CHARS));
        $correo = trim(filter_input(INPUT_POST, 'correo', FILTER_VALIDATE_EMAIL));
        $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS));
        $usuario = trim(filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_SPECIAL_CHARS));
        $estado = $_POST['estado'] ?? 'activo';
        $contrasena = $_POST['contrasena'] ?? '';
        $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

        $errors = [];

        // Validaciones obligatorias
        if (!$rol_id) $errors[] = "El rol es obligatorio.";
        if (empty($nombre)) $errors[] = "El nombre es obligatorio.";
        if (empty($documento)) $errors[] = "El documento es obligatorio.";
        if (!$correo) $errors[] = "El correo electrónico es obligatorio o no es válido.";
        if (empty($usuario)) $errors[] = "El nombre de usuario es obligatorio.";
        
        // Validar contraseña si el usuario decide cambiarla
        if (!empty($contrasena) && $contrasena !== $confirmar_contrasena) {
            $errors[] = "Las nuevas contraseñas ingresadas no coinciden.";
        }

        // Validaciones de unicidad (excluyendo el usuario actual)
        if (empty($errors)) {
            if ($this->userModel->existsDocument($documento, $id)) {
                $errors[] = "El documento de identidad ya está registrado por otro usuario.";
            }
            if ($this->userModel->existsUsername($usuario, $id)) {
                $errors[] = "El nombre de usuario ya está en uso.";
            }
            if ($this->userModel->existsEmail($correo, $id)) {
                $errors[] = "El correo electrónico ya está registrado.";
            }
        }

        // Redirección con errores
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . BASE_URL . '/usuarios/editar/' . $id);
            exit();
        }

        $data = [
            'rol_id'     => $rol_id,
            'nombre'     => $nombre,
            'documento'  => $documento,
            'correo'     => $correo,
            'telefono'   => $telefono,
            'usuario'    => $usuario,
            'estado'     => $estado,
            'contrasena' => $contrasena
        ];

        if ($this->userModel->update($id, $data)) {
            $_SESSION['success'] = "Usuario actualizado exitosamente.";
        } else {
            $_SESSION['error'] = "Hubo un error al actualizar los datos del usuario.";
        }

        header('Location: ' . BASE_URL . '/usuarios');
        exit();
    }

    /**
     * Procesa la eliminación lógica (Soft Delete) del usuario.
     * 
     * @param string|int $id ID del usuario
     */
    public function eliminar($id) {
        $id = (int)$id;

        if ($this->userModel->deleteLogically($id)) {
            $_SESSION['success'] = "Usuario desactivado y eliminado lógicamente del sistema.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al intentar eliminar el usuario.";
        }

        header('Location: ' . BASE_URL . '/usuarios');
        exit();
    }
}
