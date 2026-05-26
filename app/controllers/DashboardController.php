<?php

namespace App\Controllers;

use App\Models\WorkOrder;
use App\Models\Client;
use App\Models\Inventory;
use App\Helpers\AuthHelper;

/**
 * Controlador del Dashboard (HU08)
 * Gestiona la pantalla principal y las métricas clave del sistema.
 */
class DashboardController {
    private $workOrderModel;
    private $clientModel;
    private $inventoryModel;

    public function __construct() {
        AuthHelper::initSession();
        // Solo usuarios logueados pueden ver el dashboard
        AuthHelper::requireLogin();
        
        $this->workOrderModel = new WorkOrder();
        $this->clientModel = new Client();
        $this->inventoryModel = new Inventory();
    }

    /**
     * Muestra la vista principal del Dashboard con las métricas.
     */
    public function index() {
        // Métricas rápidas
        $total_ots_activas = $this->workOrderModel->countActive();
        $total_clientes = $this->clientModel->countAll();
        $total_recaudado = $this->workOrderModel->getTotalRevenue();

        // Alertas y Tablas
        $alertas_inventario = $this->inventoryModel->getLowStock();
        $ots_recientes = $this->workOrderModel->getRecent(5);

        require_once ROOT_PATH . '/app/views/dashboard/index.php';
    }
}
