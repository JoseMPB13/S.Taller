<?php

namespace App\Controllers;

use App\Models\WorkOrder;
use App\Models\Client;
use App\Models\Car;
use App\Models\Worker;
use App\Models\WorkOrderDetail;
use App\Models\Inventory;
use App\Models\Service;
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
    private $detailModel;
    private $inventoryModel;
    private $serviceModel;

    public function __construct() {
        AuthHelper::initSession();
        $this->workOrderModel = new WorkOrder();
        $this->clientModel = new Client();
        $this->carModel = new Car();
        $this->workerModel = new Worker();
        $this->detailModel = new WorkOrderDetail();
        $this->inventoryModel = new Inventory();
        $this->serviceModel = new Service();
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

    /**
     * Vista de detalles operativos de la OT.
     */
    public function detalles($id) {
        $id = (int)$id;
        $ot = $this->workOrderModel->getById($id);

        if (!$ot) {
            $_SESSION['error'] = "Orden de Trabajo no encontrada.";
            header('Location: ' . BASE_URL . '/ordenes');
            exit();
        }

        // Obtener detalles
        $mecanicos_asignados = $this->detailModel->getMechanics($id);
        $repuestos_consumidos = $this->detailModel->getParts($id);
        $servicios_aplicados = $this->detailModel->getServices($id);

        // Catálogos para los selectores del formulario de adición
        $trabajadores = $this->workerModel->getAll();
        $inventario = $this->inventoryModel->getAll();
        $servicios = $this->serviceModel->getAll();

        require_once ROOT_PATH . '/app/views/workorders/detalles.php';
    }

    /**
     * Asigna un mecánico adicional a la OT.
     */
    public function agregar_mecanico($ot_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $ot_id);
            exit();
        }

        $trabajador_id = (int)($_POST['trabajador_id'] ?? 0);
        $motivo = trim(filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $usuario_asignador = $_SESSION['user_id'] ?? 1;

        if ($trabajador_id > 0) {
            if ($this->detailModel->addMechanic($ot_id, $trabajador_id, $usuario_asignador, $motivo)) {
                $_SESSION['success'] = "Mecánico asignado exitosamente.";
            } else {
                $_SESSION['error'] = "No se pudo asignar. (Probablemente ya esté asignado o hubo un error).";
            }
        } else {
            $_SESSION['error'] = "Debe seleccionar un mecánico.";
        }

        header('Location: ' . BASE_URL . '/ordenes/detalles/' . $ot_id);
        exit();
    }

    /**
     * Agrega un repuesto a la OT y descuenta stock (RF-013).
     */
    public function agregar_repuesto($ot_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $ot_id);
            exit();
        }

        $item_id = (int)($_POST['item_id'] ?? 0);
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $usuario = $_SESSION['user_id'] ?? 1;

        if ($item_id <= 0 || $cantidad <= 0) {
            $_SESSION['error'] = "Debe seleccionar un repuesto y la cantidad debe ser mayor a 0.";
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $ot_id);
            exit();
        }

        // Obtener costos y precios actuales del catálogo
        $repuesto = $this->inventoryModel->getById($item_id);
        if (!$repuesto) {
            $_SESSION['error'] = "El repuesto seleccionado no existe.";
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $ot_id);
            exit();
        }

        if ($repuesto['stock'] < $cantidad) {
            $_SESSION['error'] = "Stock insuficiente. Stock actual: " . $repuesto['stock'];
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $ot_id);
            exit();
        }

        // Guardar con precios congelados
        if ($this->detailModel->addPart($ot_id, $item_id, $cantidad, $repuesto['precio'], $repuesto['costo'], $usuario)) {
            $_SESSION['success'] = "Repuesto agregado y stock descontado exitosamente.";
        } else {
            $_SESSION['error'] = "Error al agregar repuesto (Stock modificado concurrentemente).";
        }

        header('Location: ' . BASE_URL . '/ordenes/detalles/' . $ot_id);
        exit();
    }

    /**
     * Agrega un servicio aplicado a la OT.
     */
    public function agregar_servicio($ot_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $ot_id);
            exit();
        }

        $servicio_id = (int)($_POST['servicio_id'] ?? 0);
        $precio_aplicado = (float)($_POST['precio_aplicado'] ?? 0);
        $descuento = (float)($_POST['descuento_aplicado'] ?? 0);
        $tiempo_real = (int)($_POST['tiempo_real'] ?? 0);
        $usuario = $_SESSION['user_id'] ?? 1;

        if ($servicio_id <= 0 || $precio_aplicado < 0) {
            $_SESSION['error'] = "Debe seleccionar un servicio y el precio debe ser mayor o igual a 0.";
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $ot_id);
            exit();
        }

        if ($this->detailModel->addService($ot_id, $servicio_id, $precio_aplicado, $descuento, $tiempo_real, $usuario)) {
            $_SESSION['success'] = "Servicio agregado a la Orden de Trabajo.";
        } else {
            $_SESSION['error'] = "Error al registrar el servicio.";
        }

        header('Location: ' . BASE_URL . '/ordenes/detalles/' . $ot_id);
        exit();
    }
    /**
     * Cierra financieramente la OT (Liquidación). (RF-015, RF-008)
     */
    public function liquidar($id) {
        $id = (int)$id;
        
        if (!AuthHelper::isAdmin()) {
            $_SESSION['error'] = "Acceso denegado: Solo un Administrador puede liquidar la Orden de Trabajo.";
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $id);
            exit();
        }

        $ot = $this->workOrderModel->getById($id);
        if (!$ot) {
            header('Location: ' . BASE_URL . '/ordenes');
            exit();
        }

        if ($this->workOrderModel->updateStatus($id, 'cerrado')) {
            $_SESSION['success'] = "Orden de Trabajo Liquidada y Cerrada exitosamente.";
            header('Location: ' . BASE_URL . '/ordenes/comprobante/' . $id);
        } else {
            $_SESSION['error'] = "Ocurrió un error al liquidar la Orden de Trabajo.";
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $id);
        }
        exit();
    }

    /**
     * Muestra el comprobante final de la OT optimizado para impresión. (RF-015)
     */
    public function comprobante($id) {
        $id = (int)$id;
        $ot = $this->workOrderModel->getById($id);

        if (!$ot) {
            $_SESSION['error'] = "Orden de Trabajo no encontrada.";
            header('Location: ' . BASE_URL . '/ordenes');
            exit();
        }

        // Obtener detalles consumidos (precios históricos)
        $repuestos = $this->detailModel->getParts($id);
        $servicios = $this->detailModel->getServices($id);

        // Cálculos
        $subtotal_repuestos = 0;
        foreach ($repuestos as $rp) {
            $subtotal_repuestos += ($rp['cantidad'] * $rp['precio_unitario']);
        }

        $subtotal_servicios = 0;
        $total_descuentos = 0;
        foreach ($servicios as $sv) {
            $subtotal_servicios += $sv['precio_aplicado'];
            $total_descuentos += $sv['descuento_aplicado'];
        }

        $subtotal = $subtotal_repuestos + $subtotal_servicios - $total_descuentos;
        
        // Impuestos (Ej: 13% IVA Bolivia si aplica)
        // Por simplicidad del modelo, asumimos que los precios ya incluyen IVA,
        // pero podemos desglosarlo para el recibo.
        $tasa_iva = 0.13;
        $monto_iva = $subtotal * $tasa_iva;
        $subtotal_sin_iva = $subtotal - $monto_iva;
        $total_general = $subtotal; // El total a pagar

        require_once ROOT_PATH . '/app/views/workorders/comprobante.php';
    }
}
