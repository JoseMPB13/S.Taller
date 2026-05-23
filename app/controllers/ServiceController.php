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

        // Impuestos y Descuentos como JSON
        $impuesto_iva = (float)($_POST['impuesto_iva'] ?? 0.00);
        $descuento_max = (float)($_POST['descuento_max'] ?? 0.00);
        $impuestos_descuentos = json_encode([
            'impuesto_iva' => $impuesto_iva,
            'descuento_max' => $descuento_max
        ], JSON_UNESCAPED_UNICODE);

        $errors = [];

        if (empty($nombre_servicio)) $errors[] = "El nombre del servicio es obligatorio.";
        if ($tiempo_estimado <= 0) $errors[] = "El tiempo estimado de realización (en minutos) debe ser mayor a 0.";
        if ($precio_base < 0) $errors[] = "El precio base del servicio no puede ser negativo.";
        if ($impuesto_iva < 0 || $impuesto_iva > 100) $errors[] = "El porcentaje de IVA debe estar entre 0% y 100%.";
        if ($descuento_max < 0 || $descuento_max > 100) $errors[] = "El porcentaje de descuento máximo debe estar entre 0% y 100%.";

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
            'impuestos_descuentos' => $impuestos_descuentos,
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

        // Impuestos y Descuentos como JSON
        $impuesto_iva = (float)($_POST['impuesto_iva'] ?? 0.00);
        $descuento_max = (float)($_POST['descuento_max'] ?? 0.00);
        $impuestos_descuentos = json_encode([
            'impuesto_iva' => $impuesto_iva,
            'descuento_max' => $descuento_max
        ], JSON_UNESCAPED_UNICODE);

        $errors = [];

        if (empty($nombre_servicio)) $errors[] = "El nombre del servicio es obligatorio.";
        if ($tiempo_estimado <= 0) $errors[] = "El tiempo estimado de realización (en minutos) debe ser mayor a 0.";
        if ($precio_base < 0) $errors[] = "El precio base del servicio no puede ser negativo.";
        if ($impuesto_iva < 0 || $impuesto_iva > 100) $errors[] = "El porcentaje de IVA debe estar entre 0% y 100%.";
        if ($descuento_max < 0 || $descuento_max > 100) $errors[] = "El porcentaje de descuento máximo debe estar entre 0% y 100%.";

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
            'impuestos_descuentos' => $impuestos_descuentos,
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

        if ($this->serviceModel->deleteLogically($id)) {
            $_SESSION['success'] = "Servicio dado de baja del catálogo.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al dar de baja el servicio.";
        }

        header('Location: ' . BASE_URL . '/servicios');
        exit();
    }
}
