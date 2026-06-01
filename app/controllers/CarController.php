<?php

namespace App\Controllers;

use App\Models\Car;
use App\Models\Client;
use App\Helpers\AuthHelper;

/**
 * Controlador de Autos (RF-004 y RF-011)
 * Maneja las peticiones del CRUD y la asignación de propietarios.
 */
class CarController {
    private $carModel;
    private $clientModel;

    public function __construct() {
        AuthHelper::initSession();
        $this->carModel = new Car();
        $this->clientModel = new Client();
    }

    /**
     * Listado de vehículos con buscador por datos del auto y del cliente.
     */
    public function index() {
        $search = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $cars = $this->carModel->getAll($search);
        require_once ROOT_PATH . '/app/views/cars/index.php';
    }

    /**
     * Formulario de creación de vehículos.
     */
    public function crear() {
        $car = null;
        $clients = $this->clientModel->getAll();
        require_once ROOT_PATH . '/app/views/cars/form.php';
    }

    /**
     * Guarda el vehículo en la base de datos con validaciones.
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/autos');
            exit();
        }

        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/autos/crear');
            exit();
        }

        $cliente_id = (int)($_POST['cliente_id'] ?? 0);
        $placa = trim(filter_input(INPUT_POST, 'placa', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $vin = trim(filter_input(INPUT_POST, 'vin', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $marca = trim(filter_input(INPUT_POST, 'marca', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $modelo = trim(filter_input(INPUT_POST, 'modelo', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $anio = (int)($_POST['anio'] ?? 0);
        $color = trim(filter_input(INPUT_POST, 'color', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $kilometraje = (int)($_POST['kilometraje'] ?? 0);
        $observaciones = trim(filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

        $errors = [];

        if ($cliente_id <= 0) $errors[] = "Debe vincular obligatoriamente al propietario (cliente).";
        if (empty($placa)) $errors[] = "La placa es obligatoria.";
        if (empty($marca)) $errors[] = "La marca del vehículo es obligatoria.";
        if (empty($modelo)) $errors[] = "El modelo del vehículo es obligatorio.";
        if ($anio < 1900 || $anio > (int)date('Y') + 1) $errors[] = "El año del vehículo no es válido.";
        if ($kilometraje < 0) $errors[] = "El kilometraje no puede ser negativo.";

        // Validar que el cliente exista
        if ($cliente_id > 0 && !$this->clientModel->getById($cliente_id)) {
            $errors[] = "El cliente seleccionado no existe en el sistema.";
        }

        // Validar placa única
        if (empty($errors)) {
            if ($this->carModel->existsPlaca($placa)) {
                $errors[] = "La placa ingresada ya está registrada por otro auto.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_car_data'] = $_POST;
            header('Location: ' . BASE_URL . '/autos/crear');
            exit();
        }

        $data = [
            'cliente_id'    => $cliente_id,
            'placa'         => $placa,
            'vin'           => $vin,
            'marca'         => $marca,
            'modelo'        => $modelo,
            'anio'          => $anio,
            'color'         => $color,
            'kilometraje'   => $kilometraje,
            'observaciones' => $observaciones,
            'estado'        => 'activo'
        ];

        if ($this->carModel->create($data)) {
            $_SESSION['success'] = "Vehículo registrado exitosamente.";
            unset($_SESSION['form_car_data']);
        } else {
            $_SESSION['error'] = "Ocurrió un error al registrar el vehículo.";
        }

        header('Location: ' . BASE_URL . '/autos');
        exit();
    }

    /**
     * Formulario de edición de vehículos.
     */
    public function editar($id) {
        $id = (int)$id;
        $car = $this->carModel->getById($id);

        if (!$car) {
            $_SESSION['error'] = "Vehículo no encontrado.";
            header('Location: ' . BASE_URL . '/autos');
            exit();
        }

        $clients = $this->clientModel->getAll();
        require_once ROOT_PATH . '/app/views/cars/form.php';
    }

    /**
     * Actualiza los datos de un vehículo en base de datos.
     */
    public function actualizar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/autos');
            exit();
        }

        $id = (int)$id;
        $car = $this->carModel->getById($id);

        if (!$car) {
            $_SESSION['error'] = "Vehículo no encontrado.";
            header('Location: ' . BASE_URL . '/autos');
            exit();
        }

        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/autos/editar/' . $id);
            exit();
        }

        $cliente_id = (int)($_POST['cliente_id'] ?? 0);
        $placa = trim(filter_input(INPUT_POST, 'placa', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $vin = trim(filter_input(INPUT_POST, 'vin', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $marca = trim(filter_input(INPUT_POST, 'marca', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $modelo = trim(filter_input(INPUT_POST, 'modelo', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $anio = (int)($_POST['anio'] ?? 0);
        $color = trim(filter_input(INPUT_POST, 'color', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $kilometraje = (int)($_POST['kilometraje'] ?? 0);
        $observaciones = trim(filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $estado = $_POST['estado'] ?? 'activo';

        $errors = [];

        if ($cliente_id <= 0) $errors[] = "Debe vincular obligatoriamente al propietario (cliente).";
        if (empty($placa)) $errors[] = "La placa es obligatoria.";
        if (empty($marca)) $errors[] = "La marca del vehículo es obligatoria.";
        if (empty($modelo)) $errors[] = "El modelo del vehículo es obligatorio.";
        if ($anio < 1900 || $anio > (int)date('Y') + 1) $errors[] = "El año del vehículo no es válido.";
        if ($kilometraje < 0) $errors[] = "El kilometraje no puede ser negativo.";

        // Validar que el cliente exista
        if ($cliente_id > 0 && !$this->clientModel->getById($cliente_id)) {
            $errors[] = "El cliente seleccionado no existe en el sistema.";
        }

        // Validar placa única (excluyendo este vehículo)
        if (empty($errors)) {
            if ($this->carModel->existsPlaca($placa, $id)) {
                $errors[] = "La placa ingresada ya está registrada por otro auto.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . BASE_URL . '/autos/editar/' . $id);
            exit();
        }

        $data = [
            'cliente_id'    => $cliente_id,
            'placa'         => $placa,
            'vin'           => $vin,
            'marca'         => $marca,
            'modelo'        => $modelo,
            'anio'          => $anio,
            'color'         => $color,
            'kilometraje'   => $kilometraje,
            'observaciones' => $observaciones,
            'estado'        => $estado
        ];

        if ($this->carModel->update($id, $data)) {
            $_SESSION['success'] = "Vehículo actualizado exitosamente.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al actualizar los datos del vehículo.";
        }

        header('Location: ' . BASE_URL . '/autos');
        exit();
    }

    /**
     * Baja lógica del vehículo.
     */
    public function eliminar($id) {
        $id = (int)$id;

        // Validar método POST y token CSRF
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !\App\Helpers\AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Acción no autorizada (CSRF inválido o método no permitido).";
            header('Location: ' . BASE_URL . '/autos');
            exit();
        }

        if ($this->carModel->deleteLogically($id)) {
            $_SESSION['success'] = "Vehículo dado de baja y desactivado del sistema.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al dar de baja el vehículo.";
        }

        header('Location: ' . BASE_URL . '/autos');
        exit();
    }
}
