<?php

namespace App\Controllers;

use App\Models\Client;
use App\Helpers\AuthHelper;

/**
 * Controlador de Clientes (RF-002)
 * Procesa las peticiones HTTP del módulo de clientes bajo patrón MVC.
 */
class ClientController {
    private $clientModel;

    public function __construct() {
        // Inicializar sesión y comprobar autenticación
        AuthHelper::initSession();
        $this->clientModel = new Client();
    }

    /**
     * Listado general de clientes con buscador simple.
     */
    public function index() {
        $search = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $clients = $this->clientModel->getAll($search);

        // Cargar vista de listado
        require_once ROOT_PATH . '/app/views/clients/index.php';
    }

    /**
     * Muestra el formulario para registrar un nuevo cliente.
     */
    public function crear() {
        $client = null; // Indica modo creación
        require_once ROOT_PATH . '/app/views/clients/form.php';
    }

    /**
     * Procesa la inserción de un nuevo cliente.
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/clientes');
            exit();
        }

        // 1. Mitigación CSRF
        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/clientes/crear');
            exit();
        }

        // Sanitización y captura de campos
        $nombres = trim(filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_SPECIAL_CHARS));
        $apellidos = trim(filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_SPECIAL_CHARS));
        $documento = trim(filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_SPECIAL_CHARS));
        $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS));
        $correo = trim(filter_input(INPUT_POST, 'correo', FILTER_VALIDATE_EMAIL));
        $direccion = trim(filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_SPECIAL_CHARS));
        $observaciones = trim(filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_SPECIAL_CHARS));
        
        // Datos de facturación JSON
        $nit = trim(filter_input(INPUT_POST, 'nit_facturacion', FILTER_SANITIZE_SPECIAL_CHARS));
        $razon_social = trim(filter_input(INPUT_POST, 'razon_social_facturacion', FILTER_SANITIZE_SPECIAL_CHARS));
        $datos_facturacion = json_encode([
            'nit' => $nit,
            'razon_social' => $razon_social
        ], JSON_UNESCAPED_UNICODE);

        $errors = [];

        // Validaciones
        if (empty($nombres)) $errors[] = "El nombre del cliente es obligatorio.";
        if (empty($apellidos)) $errors[] = "El apellido del cliente es obligatorio.";
        if (empty($documento)) $errors[] = "El documento de identidad (NIT/CI/DNI) es obligatorio.";
        if (empty($telefono)) $errors[] = "El teléfono de contacto es obligatorio.";
        
        // Comprobar formato de correo opcional
        if (!empty($_POST['correo']) && !$correo) {
            $errors[] = "El formato de correo electrónico no es válido.";
        }

        // Validar unicidad del documento
        if (empty($errors)) {
            if ($this->clientModel->existsDocument($documento)) {
                $errors[] = "El documento de identidad ya está registrado por otro cliente.";
            }
        }

        // Si hay errores, redirigir con el buffer del formulario
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_client_data'] = $_POST;
            header('Location: ' . BASE_URL . '/clientes/crear');
            exit();
        }

        // Registrar
        $data = [
            'nombres'           => $nombres,
            'apellidos'         => $apellidos,
            'documento'         => $documento,
            'telefono'          => $telefono,
            'correo'            => $correo,
            'direccion'         => $direccion,
            'observaciones'     => $observaciones,
            'datos_facturacion' => $datos_facturacion,
            'estado'            => 'activo'
        ];

        if ($this->clientModel->create($data)) {
            $_SESSION['success'] = "Cliente registrado exitosamente.";
            unset($_SESSION['form_client_data']);
        } else {
            $_SESSION['error'] = "Ocurrió un error al registrar al cliente.";
        }

        header('Location: ' . BASE_URL . '/clientes');
        exit();
    }

    /**
     * Muestra el formulario para editar un cliente.
     */
    public function editar($id) {
        $id = (int)$id;
        $client = $this->clientModel->getById($id);

        if (!$client) {
            $_SESSION['error'] = "Cliente no encontrado o dado de baja.";
            header('Location: ' . BASE_URL . '/clientes');
            exit();
        }

        require_once ROOT_PATH . '/app/views/clients/form.php';
    }

    /**
     * Procesa la actualización de datos de un cliente existente.
     */
    public function actualizar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/clientes');
            exit();
        }

        $id = (int)$id;
        $client = $this->clientModel->getById($id);

        if (!$client) {
            $_SESSION['error'] = "Cliente no encontrado.";
            header('Location: ' . BASE_URL . '/clientes');
            exit();
        }

        // 1. Mitigación CSRF
        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/clientes/editar/' . $id);
            exit();
        }

        // Sanitización y captura de campos
        $nombres = trim(filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_SPECIAL_CHARS));
        $apellidos = trim(filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_SPECIAL_CHARS));
        $documento = trim(filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_SPECIAL_CHARS));
        $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS));
        $correo = trim(filter_input(INPUT_POST, 'correo', FILTER_VALIDATE_EMAIL));
        $direccion = trim(filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_SPECIAL_CHARS));
        $observaciones = trim(filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_SPECIAL_CHARS));
        $estado = $_POST['estado'] ?? 'activo';
        
        // Datos de facturación JSON
        $nit = trim(filter_input(INPUT_POST, 'nit_facturacion', FILTER_SANITIZE_SPECIAL_CHARS));
        $razon_social = trim(filter_input(INPUT_POST, 'razon_social_facturacion', FILTER_SANITIZE_SPECIAL_CHARS));
        $datos_facturacion = json_encode([
            'nit' => $nit,
            'razon_social' => $razon_social
        ], JSON_UNESCAPED_UNICODE);

        $errors = [];

        // Validaciones
        if (empty($nombres)) $errors[] = "El nombre del cliente es obligatorio.";
        if (empty($apellidos)) $errors[] = "El apellido del cliente es obligatorio.";
        if (empty($documento)) $errors[] = "El documento de identidad (NIT/CI/DNI) es obligatorio.";
        if (empty($telefono)) $errors[] = "El teléfono de contacto es obligatorio.";
        
        if (!empty($_POST['correo']) && !$correo) {
            $errors[] = "El formato de correo electrónico no es válido.";
        }

        // Validar unicidad del documento (excluyendo cliente actual)
        if (empty($errors)) {
            if ($this->clientModel->existsDocument($documento, $id)) {
                $errors[] = "El documento de identidad ya está registrado por otro cliente.";
            }
        }

        // Redirección con errores
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . BASE_URL . '/clientes/editar/' . $id);
            exit();
        }

        // Actualizar
        $data = [
            'nombres'           => $nombres,
            'apellidos'         => $apellidos,
            'documento'         => $documento,
            'telefono'          => $telefono,
            'correo'            => $correo,
            'direccion'         => $direccion,
            'observaciones'     => $observaciones,
            'datos_facturacion' => $datos_facturacion,
            'estado'            => $estado
        ];

        if ($this->clientModel->update($id, $data)) {
            $_SESSION['success'] = "Cliente actualizado exitosamente.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al actualizar los datos del cliente.";
        }

        header('Location: ' . BASE_URL . '/clientes');
        exit();
    }

    /**
     * Procesa la eliminación lógica (Soft Delete) del cliente.
     */
    public function eliminar($id) {
        $id = (int)$id;

        if ($this->clientModel->deleteLogically($id)) {
            $_SESSION['success'] = "Cliente desactivado y dado de baja lógicamente del sistema.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al intentar dar de baja al cliente.";
        }

        header('Location: ' . BASE_URL . '/clientes');
        exit();
    }
}
