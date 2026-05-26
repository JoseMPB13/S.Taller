<?php

namespace App\Controllers;

use App\Models\WorkOrder;
use App\Models\Client;
use App\Models\Car;
use App\Models\Worker;
use App\Helpers\AuthHelper;

/**
 * Controlador de Órdenes de Trabajo (RF-005)
 * Procesa la creación, listado y cambio de estados de OTs.
 */
class WorkOrderController {
    private $workOrderModel;
    private $clientModel;
    private $carModel;
    private $workerModel;

    public function __construct() {
        AuthHelper::initSession();
        $this->workOrderModel = new WorkOrder();
        $this->clientModel = new Client();
        $this->carModel = new Car();
        $this->workerModel = new Worker();
    }

    /**
     * Listado general de Órdenes de Trabajo.
     */
    public function index() {
        $search = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $estado = trim(filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        
        $ordenes = $this->workOrderModel->getAll($search, $estado ?: null);
        
        // Adjuntar trabajador a cada orden para mostrarlo en el listado
        foreach ($ordenes as &$ot) {
            $worker = $this->workOrderModel->getWorkerByOtId($ot['id']);
            $ot['mecanico_asignado'] = $worker ? $worker['nombres'] . ' ' . $worker['apellidos'] : 'Sin asignar';
        }

        require_once ROOT_PATH . '/app/views/workorders/index.php';
    }

    /**
     * Formulario de creación de OT.
     */
    public function crear() {
        // Cargar listas activas para el formulario
        $clientes = $this->clientModel->getAll(); // Idealmente solo activos, getAll() de Client ya maneja deleted_at
        $autos = $this->carModel->getAll();
        $trabajadores = $this->workerModel->getAll();

        require_once ROOT_PATH . '/app/views/workorders/form.php';
    }

    /**
     * Procesa el guardado del encabezado de la OT.
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/ordenes');
            exit();
        }

        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/ordenes/crear');
            exit();
        }

        $cliente_id = (int)($_POST['cliente_id'] ?? 0);
        $auto_id = (int)($_POST['auto_id'] ?? 0);
        $trabajador_id = (int)($_POST['trabajador_id'] ?? 0);
        $falla_reportada = trim(filter_input(INPUT_POST, 'falla_reportada', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $prioridad = $_POST['prioridad'] ?? 'media';
        $observaciones = trim(filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

        $errors = [];

        if ($cliente_id <= 0) $errors[] = "Debe seleccionar un cliente.";
        if ($auto_id <= 0) $errors[] = "Debe seleccionar un vehículo.";
        if (empty($falla_reportada)) $errors[] = "La falla reportada es obligatoria.";

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_ot_data'] = $_POST;
            header('Location: ' . BASE_URL . '/ordenes/crear');
            exit();
        }

        $data = [
            'cliente_id' => $cliente_id,
            'auto_id' => $auto_id,
            'falla_reportada' => $falla_reportada,
            'prioridad' => $prioridad,
            'observaciones' => $observaciones
        ];

        // Usuario actual (Asignador)
        $usuario_asignador = $_SESSION['user_id'] ?? 1;

        if ($this->workOrderModel->create($data, $usuario_asignador, $trabajador_id > 0 ? $trabajador_id : null)) {
            $_SESSION['success'] = "Orden de Trabajo registrada exitosamente.";
            unset($_SESSION['form_ot_data']);
        } else {
            $_SESSION['error'] = "Ocurrió un error al registrar la Orden de Trabajo.";
        }

        header('Location: ' . BASE_URL . '/ordenes');
        exit();
    }

    /**
     * Permite cambiar el estado de la OT desde el listado.
     */
    public function cambiar_estado($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/ordenes');
            exit();
        }

        $id = (int)$id;
        $nuevo_estado = $_POST['estado'] ?? '';

        $estados_validos = ['pendiente', 'en_diagnostico', 'presupuestado', 'en_progreso', 'terminado', 'entregado', 'anulado', 'cerrado'];
        
        if (!in_array($nuevo_estado, $estados_validos)) {
            $_SESSION['error'] = "Estado no válido.";
            header('Location: ' . BASE_URL . '/ordenes');
            exit();
        }

        if ($this->workOrderModel->updateStatus($id, $nuevo_estado)) {
            $_SESSION['success'] = "Estado de la OT actualizado correctamente a '" . htmlspecialchars($nuevo_estado) . "'.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al actualizar el estado.";
        }

        header('Location: ' . BASE_URL . '/ordenes');
        exit();
    }
}
