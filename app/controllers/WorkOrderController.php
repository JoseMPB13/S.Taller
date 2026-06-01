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

        $estados_validos = ['pendiente', 'en_diagnostico', 'presupuestado', 'en_progreso', 'terminado', 'entregado', 'anulado', 'cerrado', 'pagada'];
        
        if (!in_array($nuevo_estado, $estados_validos)) {
            $_SESSION['error'] = "Estado no válido.";
            header('Location: ' . BASE_URL . '/ordenes');
            exit();
        }

        if ($this->workOrderModel->updateStatus($id, $nuevo_estado)) {
            $_SESSION['success'] = "Estado de la OT actualizado correctamente a '" . htmlspecialchars($nuevo_estado) . "'.";
            if ($nuevo_estado === 'pagada') {
                $this->generar_factura_pdf($id, true);
            }
        } else {
            $_SESSION['error'] = "Ocurrió un error al actualizar el estado.";
        }

        header('Location: ' . BASE_URL . '/ordenes');
        exit();
    }

    /**
     * Actualiza manualmente el costo de mano de obra de la Orden de Trabajo.
     */
    public function actualizar_mano_obra($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $id);
            exit();
        }

        $id = (int)$id;

        // Validar CSRF
        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $id);
            exit();
        }

        $costo_mano_obra = (float)($_POST['costo_mano_obra'] ?? 0.0);

        if ($costo_mano_obra < 0) {
            $_SESSION['error'] = "El costo de mano de obra no puede ser un valor negativo.";
            header('Location: ' . BASE_URL . '/ordenes/detalles/' . $id);
            exit();
        }

        if ($this->workOrderModel->updateLaborCost($id, $costo_mano_obra)) {
            $_SESSION['success'] = "Costo de Mano de Obra actualizado exitosamente.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al actualizar el Costo de Mano de Obra.";
        }

        header('Location: ' . BASE_URL . '/ordenes/detalles/' . $id);
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

        $costo_mano_obra = (float)($ot['costo_mano_obra'] ?? 0.0);
        $subtotal = $subtotal_repuestos + $subtotal_servicios - $total_descuentos;
        
        // Impuestos (Ej: 13% IVA Bolivia si aplica)
        $tasa_iva = 0.13;
        $base_total = $subtotal + $costo_mano_obra;
        $monto_iva = $base_total * $tasa_iva;
        $subtotal_sin_iva = $base_total - $monto_iva;
        $total_general = $base_total; // El total a pagar incluye la mano de obra

        require_once ROOT_PATH . '/app/views/workorders/comprobante.php';
    }

    /**
     * Genera dinámicamente el reporte de la Orden de Trabajo en PDF.
     */
    public function descargar_pdf($id) {
        $id = (int)$id;
        $this->generar_ot_pdf($id, true);
    }

    /**
     * Descarga la factura física o la genera si la orden está pagada.
     */
    public function descargar_factura($id) {
        $id = (int)$id;
        $ot = $this->workOrderModel->getById($id);
        
        if (!$ot) {
            $_SESSION['error'] = "Orden de Trabajo no encontrada.";
            header('Location: ' . BASE_URL . '/ordenes');
            exit();
        }

        $filePath = ROOT_PATH . '/public/invoices/FAC-' . $id . '.pdf';

        if (file_exists($filePath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="FAC-' . $ot['codigo'] . '.pdf"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit();
        } else {
            if ($ot['estado'] === 'pagada') {
                $this->generar_factura_pdf($id, true);
            } else {
                $_SESSION['error'] = "La factura no está disponible porque la orden no ha sido pagada.";
                header('Location: ' . BASE_URL . '/ordenes/detalles/' . $id);
                exit();
            }
        }
    }

    /**
     * Genera, guarda en servidor y opcionalmente descarga la factura en PDF.
     */
    private function generar_factura_pdf(int $id, bool $descargar = true) {
        $ot = $this->workOrderModel->getById($id);
        if (!$ot) {
            $_SESSION['error'] = "Orden de Trabajo no encontrada.";
            header('Location: ' . BASE_URL . '/ordenes');
            exit();
        }

        $repuestos = $this->detailModel->getParts($id);
        $servicios = $this->detailModel->getServices($id);
        $cliente = $this->clientModel->getById($ot['cliente_id']);

        // Decodificar JSON de facturación
        $datos_facturacion = null;
        if (!empty($cliente['datos_facturacion'])) {
            $datos_facturacion = json_decode($cliente['datos_facturacion'], true);
        }
        
        $razon_social = $datos_facturacion['razon_social'] ?? 'Sin Nombre';
        $nit_ci = $datos_facturacion['nit_ci'] ?? '0';

        if (empty(trim($razon_social))) { $razon_social = 'Sin Nombre'; }
        if (empty(trim($nit_ci))) { $nit_ci = '0'; }

        // Crear instancia del helper PDF
        $pdf = new \App\Helpers\PdfReportHelper();
        $pdf->documentTitle = 'FACTURA DE VENTA';
        $pdf->documentSubTitle = 'No. FAC-' . $ot['codigo'];
        $pdf->AliasNbPages();
        $pdf->AddPage();

        // Sección: Datos del Cliente
        $pdf->renderSectionHeader('DATOS DE FACTURACIÓN Y CLIENTE');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(95, 5, utf8_decode('Razón Social: ' . $razon_social), 0, 0);
        $pdf->Cell(95, 5, utf8_decode('NIT / CI: ' . $nit_ci), 0, 1);
        $pdf->Cell(95, 5, utf8_decode('Cliente Registrado: ' . $ot['cliente_nombres'] . ' ' . $ot['cliente_apellidos']), 0, 0);
        $pdf->Cell(95, 5, utf8_decode('Fecha Emisión: ' . date('d/m/Y')), 0, 1);
        $pdf->Ln(4);

        // Sección: Datos del Vehículo
        $pdf->renderSectionHeader('DETALLES DEL VEHÍCULO');
        $pdf->Cell(95, 5, utf8_decode('Marca/Modelo: ' . $ot['auto_marca'] . ' ' . $ot['auto_modelo']), 0, 0);
        $pdf->Cell(95, 5, utf8_decode('Placa: ' . $ot['auto_placa']), 0, 1);
        $pdf->Ln(4);

        // Sección: Repuestos
        $pdf->renderSectionHeader('1. REPUESTOS E INSUMOS CONSUMIDOS');
        $headerRep = ['Cant', 'Descripción', 'P. Unitario (BOB)', 'Subtotal (BOB)'];
        $dataRep = [];
        $totalRepuestos = 0.0;
        foreach ($repuestos as $rp) {
            $sub = $rp['cantidad'] * $rp['precio_unitario'];
            $totalRepuestos += $sub;
            $dataRep[] = [
                $rp['cantidad'],
                $rp['codigo_sku'] . ' - ' . $rp['repuesto_nombre'],
                number_format($rp['precio_unitario'], 2, ',', '.'),
                number_format($sub, 2, ',', '.')
            ];
        }
        if (empty($dataRep)) {
            $dataRep[] = ['-', 'No se registraron repuestos consumidos', '-', '0,00'];
        }
        $pdf->renderTable($headerRep, $dataRep, [15, 100, 35, 40], ['C', 'L', 'R', 'R']);

        // Sección: Servicios
        $pdf->renderSectionHeader('2. SERVICIOS Y TAREAS REALIZADAS');
        $headerSer = ['Descripción del Servicio', 'Precio (BOB)', 'Descuento (BOB)', 'Subtotal (BOB)'];
        $dataSer = [];
        $totalServicios = 0.0;
        foreach ($servicios as $sv) {
            $sub = $sv['precio_aplicado'] - $sv['descuento_aplicado'];
            $totalServicios += $sub;
            $dataSer[] = [
                $sv['nombre_servicio'],
                number_format($sv['precio_aplicado'], 2, ',', '.'),
                number_format($sv['descuento_aplicado'], 2, ',', '.'),
                number_format($sub, 2, ',', '.')
            ];
        }
        if (empty($dataSer)) {
            $dataSer[] = ['No se aplicaron servicios', '-', '-', '0,00'];
        }
        $pdf->renderTable($headerSer, $dataSer, [100, 30, 30, 30], ['L', 'R', 'R', 'R']);

        // Cálculos e impuestos
        $costo_mano_obra = (float)($ot['costo_mano_obra'] ?? 0.0);
        $subtotal = $totalRepuestos + $totalServicios;
        $total_general = $subtotal + $costo_mano_obra;

        $tasa_iva = 0.13;
        $monto_iva = $total_general * $tasa_iva;
        $subtotal_sin_iva = $total_general - $monto_iva;

        // Desglose de totales
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(130, 6, 'SUBTOTAL REPUESTOS Y SERVICIOS:', 0, 0, 'R');
        $pdf->Cell(60, 6, number_format($subtotal, 2, ',', '.') . ' BOB', 0, 1, 'R');
        
        $pdf->Cell(130, 6, 'MANO DE OBRA (MANUAL):', 0, 0, 'R');
        $pdf->Cell(60, 6, number_format($costo_mano_obra, 2, ',', '.') . ' BOB', 0, 1, 'R');

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(130, 5, utf8_decode('(Subtotal Neto Sin IVA):'), 0, 0, 'R');
        $pdf->Cell(60, 5, number_format($subtotal_sin_iva, 2, ',', '.') . ' BOB', 0, 1, 'R');
        
        $pdf->Cell(130, 5, utf8_decode('(IVA 13% Incluido):'), 0, 0, 'R');
        $pdf->Cell(60, 5, number_format($monto_iva, 2, ',', '.') . ' BOB', 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(16, 185, 129); // Color verde acento
        $pdf->Cell(130, 7, 'TOTAL A PAGAR:', 'T', 0, 'R');
        $pdf->Cell(60, 7, number_format($total_general, 2, ',', '.') . ' BOB', 'T', 1, 'R');

        // Firmas
        $pdf->Ln(15);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100, 116, 139);
        $y = $pdf->GetY();
        $pdf->Line(20, $y + 10, 80, $y + 10);
        $pdf->Line(110, $y + 10, 170, $y + 10);
        $pdf->SetY($y + 11);
        $pdf->Cell(95, 5, utf8_decode('Conformidad del Cliente (' . ($nit_ci !== '0' ? $nit_ci : 'CI/NIT') . ')'), 0, 0, 'C');
        $pdf->Cell(95, 5, utf8_decode('Autorizado Taller S.Taller'), 0, 1, 'C');

        // Directorio físico de guardado
        $invoicesDir = ROOT_PATH . '/public/invoices';
        if (!file_exists($invoicesDir)) {
            mkdir($invoicesDir, 0777, true);
        }
        
        $filePath = $invoicesDir . '/FAC-' . $id . '.pdf';
        
        // Guardar archivo físico en el servidor
        $pdf->Output('F', $filePath);

        if ($descargar) {
            $pdf->Output('D', 'FAC-' . $ot['codigo'] . '.pdf');
            exit();
        }
    }

    /**
     * Genera dinámicamente y descarga el reporte PDF de la OT.
     */
    private function generar_ot_pdf(int $id, bool $descargar = true) {
        $ot = $this->workOrderModel->getById($id);
        if (!$ot) {
            $_SESSION['error'] = "Orden de Trabajo no encontrada.";
            header('Location: ' . BASE_URL . '/ordenes');
            exit();
        }

        $repuestos = $this->detailModel->getParts($id);
        $servicios = $this->detailModel->getServices($id);
        $mecanicos = $this->detailModel->getMechanics($id);

        $pdf = new \App\Helpers\PdfReportHelper();
        $pdf->documentTitle = 'REPORTE DE ORDEN DE TRABAJO';
        $pdf->documentSubTitle = 'COD: ' . $ot['codigo'];
        $pdf->AliasNbPages();
        $pdf->AddPage();

        // Sección: Información General
        $pdf->renderSectionHeader('INFORMACIÓN GENERAL DE LA ORDEN');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(95, 5, utf8_decode('Cliente: ' . $ot['cliente_nombres'] . ' ' . $ot['cliente_apellidos']), 0, 0);
        $pdf->Cell(95, 5, utf8_decode('Prioridad: ' . ucfirst($ot['prioridad'])), 0, 1);
        $pdf->Cell(95, 5, utf8_decode('Fecha Ingreso: ' . date('d/m/Y', strtotime($ot['fecha_ingreso']))), 0, 0);
        $pdf->Cell(95, 5, utf8_decode('Estado actual: ' . strtoupper(str_replace('_', ' ', $ot['estado']))), 0, 1);
        $pdf->Ln(4);

        // Sección: Datos del Vehículo
        $pdf->renderSectionHeader('DATOS DEL VEHÍCULO');
        $pdf->Cell(95, 5, utf8_decode('Placa: ' . $ot['auto_placa']), 0, 0);
        $pdf->Cell(95, 5, utf8_decode('Marca / Modelo: ' . $ot['auto_marca'] . ' ' . $ot['auto_modelo']), 0, 1);
        $pdf->Ln(4);

        // Sección: Diagnóstico
        $pdf->renderSectionHeader('DIAGNÓSTICO Y FALLAS REPORTADAS');
        $pdf->SetFont('Arial', '', 9);
        $pdf->MultiCell(0, 5, utf8_decode('Falla Reportada: ' . $ot['falla_reportada']), 0, 'L');
        if (!empty($ot['observaciones'])) {
            $pdf->MultiCell(0, 5, utf8_decode('Observaciones adicionales: ' . $ot['observaciones']), 0, 'L');
        }
        $pdf->Ln(4);

        // Sección: Mecánicos
        $pdf->renderSectionHeader('MECÁNICOS ASIGNADOS');
        $thisMecanicos = [];
        foreach ($mecanicos as $mec) {
            $thisMecanicos[] = $mec['nombres'] . ' ' . $mec['apellidos'];
        }
        $pdf->SetFont('Arial', '', 9);
        if (empty($thisMecanicos)) {
            $pdf->Cell(0, 5, utf8_decode('Sin mecánicos asignados.'), 0, 1);
        } else {
            $pdf->Cell(0, 5, utf8_decode(implode(', ', $thisMecanicos)), 0, 1);
        }
        $pdf->Ln(4);

        // Sección: Repuestos
        $pdf->renderSectionHeader('1. REPUESTOS E INSUMOS CONSUMIDOS');
        $headerRep = ['Cant', 'Descripción', 'Precio (BOB)', 'Subtotal (BOB)'];
        $dataRep = [];
        $totalRepuestos = 0.0;
        foreach ($repuestos as $rp) {
            $sub = $rp['cantidad'] * $rp['precio_unitario'];
            $totalRepuestos += $sub;
            $dataRep[] = [
                $rp['cantidad'],
                $rp['codigo_sku'] . ' - ' . $rp['repuesto_nombre'],
                number_format($rp['precio_unitario'], 2, ',', '.'),
                number_format($sub, 2, ',', '.')
            ];
        }
        if (empty($dataRep)) {
            $dataRep[] = ['-', 'No se registraron repuestos', '-', '0,00'];
        }
        $pdf->renderTable($headerRep, $dataRep, [15, 100, 35, 40], ['C', 'L', 'R', 'R']);

        // Sección: Servicios
        $pdf->renderSectionHeader('2. SERVICIOS Y TAREAS REALIZADAS');
        $headerSer = ['Descripción del Servicio', 'Precio (BOB)', 'Descuento (BOB)', 'Subtotal (BOB)'];
        $dataSer = [];
        $totalServicios = 0.0;
        foreach ($servicios as $sv) {
            $sub = $sv['precio_aplicado'] - $sv['descuento_aplicado'];
            $totalServicios += $sub;
            $dataSer[] = [
                $sv['nombre_servicio'],
                number_format($sv['precio_aplicado'], 2, ',', '.'),
                number_format($sv['descuento_aplicado'], 2, ',', '.'),
                number_format($sub, 2, ',', '.')
            ];
        }
        if (empty($dataSer)) {
            $dataSer[] = ['No se aplicaron servicios', '-', '-', '0,00'];
        }
        $pdf->renderTable($headerSer, $dataSer, [100, 30, 30, 30], ['L', 'R', 'R', 'R']);

        // Desglose final
        $costo_mano_obra = (float)($ot['costo_mano_obra'] ?? 0.0);
        $subtotal = $totalRepuestos + $totalServicios;
        $total_general = $subtotal + $costo_mano_obra;

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(130, 6, 'SUBTOTAL REPUESTOS Y SERVICIOS:', 0, 0, 'R');
        $pdf->Cell(60, 6, number_format($subtotal, 2, ',', '.') . ' BOB', 0, 1, 'R');
        
        $pdf->Cell(130, 6, 'MANO DE OBRA (MANUAL):', 0, 0, 'R');
        $pdf->Cell(60, 6, number_format($costo_mano_obra, 2, ',', '.') . ' BOB', 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Cell(130, 7, 'TOTAL GENERAL ESTIMADO:', 'T', 0, 'R');
        $pdf->Cell(60, 7, number_format($total_general, 2, ',', '.') . ' BOB', 'T', 1, 'R');

        if ($descargar) {
            $pdf->Output('D', 'OT-' . $ot['codigo'] . '.pdf');
            exit();
        }
    }
}
