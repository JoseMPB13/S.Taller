<?php

namespace App\Controllers;

use App\Models\Service;
use App\Helpers\AuthHelper;

/**
 * Controlador de Servicios (RF-007)
 * Procesa las peticiones para el catálogo de servicios de mantenimiento y reparaciones.
 */
class ServiceController {
    private $serviceModel;

    public function __construct() {
        AuthHelper::initSession();
        $this->serviceModel = new Service();
    }

    /**
     * Listado general del catálogo de servicios.
     */
    public function index() {
        $search = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $services = $this->serviceModel->getAll($search);
        require_once ROOT_PATH . '/app/views/services/index.php';
    }

    /**
     * Formulario de creación de servicios.
     */
    public function crear() {
        $service = null;
        require_once ROOT_PATH . '/app/views/services/form.php';
    }

    /**
     * Procesa la inserción del nuevo servicio.
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/servicios');
            exit();
        }

        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/servicios/crear');
            exit();
        }

        $nombre_servicio = trim(filter_input(INPUT_POST, 'nombre_servicio', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $descripcion = trim(filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $tiempo_estimado = (int)($_POST['tiempo_estimado'] ?? 0);
        $precio_base = (float)($_POST['precio_base'] ?? 0.00);

        $errors = [];

        if (empty($nombre_servicio)) $errors[] = "El nombre del servicio es obligatorio.";
        if ($tiempo_estimado <= 0) $errors[] = "El tiempo estimado de realización (en minutos) debe ser mayor a 0.";
        if ($precio_base < 0) $errors[] = "El precio base del servicio no puede ser negativo.";

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_service_data'] = $_POST;
            header('Location: ' . BASE_URL . '/servicios/crear');
            exit();
        }

        $data = [
            'nombre_servicio'      => $nombre_servicio,
            'descripcion'          => $descripcion,
            'tiempo_estimado'      => $tiempo_estimado,
            'precio_base'          => $precio_base,
            'estado'               => 'activo'
        ];

        if ($this->serviceModel->create($data)) {
            $_SESSION['success'] = "Servicio añadido al catálogo exitosamente.";
            unset($_SESSION['form_service_data']);
        } else {
            $_SESSION['error'] = "Ocurrió un error al registrar el servicio.";
        }

        header('Location: ' . BASE_URL . '/servicios');
        exit();
    }

    /**
     * Formulario de edición del servicio.
     */
    public function editar($id) {
        $id = (int)$id;
        $service = $this->serviceModel->getById($id);

        if (!$service) {
            $_SESSION['error'] = "Servicio no encontrado.";
            header('Location: ' . BASE_URL . '/servicios');
            exit();
        }

        require_once ROOT_PATH . '/app/views/services/form.php';
    }

    /**
     * Procesa la actualización de datos del servicio.
     */
    public function actualizar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/servicios');
            exit();
        }

        $id = (int)$id;
        $service = $this->serviceModel->getById($id);

        if (!$service) {
            $_SESSION['error'] = "Servicio no encontrado.";
            header('Location: ' . BASE_URL . '/servicios');
            exit();
        }

        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/servicios/editar/' . $id);
            exit();
        }

        $nombre_servicio = trim(filter_input(INPUT_POST, 'nombre_servicio', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $descripcion = trim(filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $tiempo_estimado = (int)($_POST['tiempo_estimado'] ?? 0);
        $precio_base = (float)($_POST['precio_base'] ?? 0.00);
        $estado = $_POST['estado'] ?? 'activo';

        $errors = [];

        if (empty($nombre_servicio)) $errors[] = "El nombre del servicio es obligatorio.";
        if ($tiempo_estimado <= 0) $errors[] = "El tiempo estimado de realización (en minutos) debe ser mayor a 0.";
        if ($precio_base < 0) $errors[] = "El precio base del servicio no puede ser negativo.";

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . BASE_URL . '/servicios/editar/' . $id);
            exit();
        }

        $data = [
            'nombre_servicio'      => $nombre_servicio,
            'descripcion'          => $descripcion,
            'tiempo_estimado'      => $tiempo_estimado,
            'precio_base'          => $precio_base,
            'estado'               => $estado
        ];

        if ($this->serviceModel->update($id, $data)) {
            $_SESSION['success'] = "Servicio actualizado exitosamente.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al actualizar el servicio.";
        }

        header('Location: ' . BASE_URL . '/servicios');
        exit();
    }

    /**
     * Baja lógica del servicio.
     */
    public function eliminar($id) {
        $id = (int)$id;

        // Validar método POST y token CSRF
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !\App\Helpers\AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Acción no autorizada (CSRF inválido o método no permitido).";
            header('Location: ' . BASE_URL . '/servicios');
            exit();
        }

        if ($this->serviceModel->deleteLogically($id)) {
            $_SESSION['success'] = "Servicio dado de baja del catálogo.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al dar de baja el servicio.";
        }

        header('Location: ' . BASE_URL . '/servicios');
        exit();
    }
}
