<?php

namespace App\Controllers;

use App\Models\Inventory;
use App\Helpers\AuthHelper;

/**
 * Controlador de Inventario (RF-006)
 * Procesa las peticiones administrativas de repuestos y stock de taller.
 */
class InventoryController {
    private $inventoryModel;

    public function __construct() {
        AuthHelper::initSession();
        $this->inventoryModel = new Inventory();
    }

    /**
     * Listado general del inventario con buscador y alertas de reabastecimiento.
     */
    public function index() {
        $search = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $items = $this->inventoryModel->getAll($search);
        require_once ROOT_PATH . '/app/views/inventory/index.php';
    }

    /**
     * Formulario para añadir un nuevo artículo.
     */
    public function crear() {
        $item = null;
        require_once ROOT_PATH . '/app/views/inventory/form.php';
    }

    /**
     * Procesa la inserción de un nuevo repuesto.
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/inventario');
            exit();
        }

        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/inventario/crear');
            exit();
        }

        $codigo_sku = trim(filter_input(INPUT_POST, 'codigo_sku', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $categoria = trim(filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $unidad = trim(filter_input(INPUT_POST, 'unidad', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'unidades');
        $costo = (float)($_POST['costo'] ?? 0.00);
        $precio = (float)($_POST['precio'] ?? 0.00);
        $stock = (int)($_POST['stock'] ?? 0);
        $stock_minimo = (int)($_POST['stock_minimo'] ?? 0);
        $ubicacion = trim(filter_input(INPUT_POST, 'ubicacion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $proveedor = trim(filter_input(INPUT_POST, 'proveedor', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

        $errors = [];

        if (empty($codigo_sku)) $errors[] = "El código SKU es obligatorio.";
        if (empty($nombre)) $errors[] = "El nombre del artículo es obligatorio.";
        if (empty($categoria)) $errors[] = "La categoría del artículo es obligatoria.";
        if ($costo < 0) $errors[] = "El costo no puede ser negativo.";
        if ($precio < 0) $errors[] = "El precio de venta no puede ser negativo.";
        if ($stock < 0) $errors[] = "El stock actual no puede ser negativo.";
        if ($stock_minimo < 0) $errors[] = "El stock mínimo no puede ser negativo.";

        // Validar SKU único
        if (empty($errors)) {
            if ($this->inventoryModel->existsSku($codigo_sku)) {
                $errors[] = "El código SKU ya está registrado por otro artículo.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_inventory_data'] = $_POST;
            header('Location: ' . BASE_URL . '/inventario/crear');
            exit();
        }

        $data = [
            'codigo_sku'   => $codigo_sku,
            'nombre'       => $nombre,
            'categoria'    => $categoria,
            'unidad'       => $unidad,
            'costo'        => $costo,
            'precio'       => $precio,
            'stock'        => $stock,
            'stock_minimo' => $stock_minimo,
            'ubicacion'    => $ubicacion,
            'proveedor'    => $proveedor,
            'estado'       => 'activo'
        ];

        if ($this->inventoryModel->create($data)) {
            $_SESSION['success'] = "Artículo registrado en el inventario.";
            unset($_SESSION['form_inventory_data']);
        } else {
            $_SESSION['error'] = "Ocurrió un error al guardar el artículo.";
        }

        header('Location: ' . BASE_URL . '/inventario');
        exit();
    }

    /**
     * Formulario de edición de artículo.
     */
    public function editar($id) {
        $id = (int)$id;
        $item = $this->inventoryModel->getById($id);

        if (!$item) {
            $_SESSION['error'] = "Artículo no encontrado.";
            header('Location: ' . BASE_URL . '/inventario');
            exit();
        }

        require_once ROOT_PATH . '/app/views/inventory/form.php';
    }

    /**
     * Procesa la actualización del artículo de inventario.
     */
    public function actualizar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/inventario');
            exit();
        }

        $id = (int)$id;
        $item = $this->inventoryModel->getById($id);

        if (!$item) {
            $_SESSION['error'] = "Artículo no encontrado.";
            header('Location: ' . BASE_URL . '/inventario');
            exit();
        }

        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/inventario/editar/' . $id);
            exit();
        }

        $codigo_sku = trim(filter_input(INPUT_POST, 'codigo_sku', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $categoria = trim(filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $unidad = trim(filter_input(INPUT_POST, 'unidad', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'unidades');
        $costo = (float)($_POST['costo'] ?? 0.00);
        $precio = (float)($_POST['precio'] ?? 0.00);
        $stock = (int)($_POST['stock'] ?? 0);
        $stock_minimo = (int)($_POST['stock_minimo'] ?? 0);
        $ubicacion = trim(filter_input(INPUT_POST, 'ubicacion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $proveedor = trim(filter_input(INPUT_POST, 'proveedor', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $estado = $_POST['estado'] ?? 'activo';

        $errors = [];

        if (empty($codigo_sku)) $errors[] = "El código SKU es obligatorio.";
        if (empty($nombre)) $errors[] = "El nombre del artículo es obligatorio.";
        if (empty($categoria)) $errors[] = "La categoría del artículo es obligatoria.";
        if ($costo < 0) $errors[] = "El costo no puede ser negativo.";
        if ($precio < 0) $errors[] = "El precio de venta no puede ser negativo.";
        if ($stock < 0) $errors[] = "El stock actual no puede ser negativo.";
        if ($stock_minimo < 0) $errors[] = "El stock mínimo no puede ser negativo.";

        // Validar SKU único (excluyendo este artículo)
        if (empty($errors)) {
            if ($this->inventoryModel->existsSku($codigo_sku, $id)) {
                $errors[] = "El código SKU ya está registrado por otro artículo.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . BASE_URL . '/inventario/editar/' . $id);
            exit();
        }

        $data = [
            'codigo_sku'   => $codigo_sku,
            'nombre'       => $nombre,
            'categoria'    => $categoria,
            'unidad'       => $unidad,
            'costo'        => $costo,
            'precio'       => $precio,
            'stock'        => $stock,
            'stock_minimo' => $stock_minimo,
            'ubicacion'    => $ubicacion,
            'proveedor'    => $proveedor,
            'estado'       => $estado
        ];

        if ($this->inventoryModel->update($id, $data)) {
            $_SESSION['success'] = "Artículo actualizado exitosamente.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al actualizar los datos.";
        }

        header('Location: ' . BASE_URL . '/inventario');
        exit();
    }

    /**
     * Baja lógica del artículo de inventario.
     */
    public function eliminar($id) {
        $id = (int)$id;

        // Validar método POST y token CSRF
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !\App\Helpers\AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Acción no autorizada (CSRF inválido o método no permitido).";
            header('Location: ' . BASE_URL . '/inventario');
            exit();
        }

        if ($this->inventoryModel->deleteLogically($id)) {
            $_SESSION['success'] = "Artículo dado de baja de los catálogos de inventario.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al dar de baja el artículo.";
        }

        header('Location: ' . BASE_URL . '/inventario');
        exit();
    }
}
