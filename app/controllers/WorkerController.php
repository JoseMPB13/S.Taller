<?php

namespace App\Controllers;

use App\Models\Worker;
use App\Helpers\AuthHelper;

/**
 * Controlador de Trabajadores (RF-003)
 * Procesa las peticiones HTTP del módulo de mecánicos y personal técnico.
 */
class WorkerController {
    private $workerModel;

    public function __construct() {
        AuthHelper::initSession();
        $this->workerModel = new Worker();
    }

    /**
     * Listado general de personal técnico.
     */
    public function index() {
        $search = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $workers = $this->workerModel->getAll($search);
        require_once ROOT_PATH . '/app/views/workers/index.php';
    }

    /**
     * Formulario de creación de trabajadores.
     */
    public function crear() {
        $worker = null;
        require_once ROOT_PATH . '/app/views/workers/form.php';
    }

    /**
     * Procesa la inserción del trabajador en el sistema.
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/trabajadores');
            exit();
        }

        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/trabajadores/crear');
            exit();
        }

        $nombres = trim(filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $apellidos = trim(filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $documento = trim(filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $especialidades = trim(filter_input(INPUT_POST, 'especialidades', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $nivel = $_POST['nivel'] ?? 'Junior';
        $contacto = trim(filter_input(INPUT_POST, 'contacto', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $disponibilidad = $_POST['disponibilidad'] ?? 'disponible';
        $costo_hora = (float)($_POST['costo_hora'] ?? 0.00);

        $errors = [];

        if (empty($nombres)) $errors[] = "El nombre es obligatorio.";
        if (empty($apellidos)) $errors[] = "El apellido es obligatorio.";
        if (empty($documento)) $errors[] = "El documento de identidad es obligatorio.";
        if (empty($especialidades)) $errors[] = "Debe registrar al menos una especialidad.";
        if ($costo_hora < 0) $errors[] = "El costo por hora no puede ser un valor negativo.";

        // Validar niveles permitidos
        $nivelesValidos = ['Junior', 'Semi-Senior', 'Senior', 'Master'];
        if (!in_array($nivel, $nivelesValidos)) {
            $errors[] = "El nivel del trabajador no es válido.";
        }

        // Validar disponibilidades permitidas
        $disponibilidadesValidas = ['disponible', 'ocupado', 'ausente'];
        if (!in_array($disponibilidad, $disponibilidadesValidas)) {
            $errors[] = "La disponibilidad no es válida.";
        }

        // Validar documento único
        if (empty($errors)) {
            if ($this->workerModel->existsDocument($documento)) {
                $errors[] = "El documento de identidad ya está registrado por otro trabajador.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_worker_data'] = $_POST;
            header('Location: ' . BASE_URL . '/trabajadores/crear');
            exit();
        }

        $data = [
            'nombres'        => $nombres,
            'apellidos'      => $apellidos,
            'documento'      => $documento,
            'especialidades' => $especialidades,
            'nivel'          => $nivel,
            'contacto'       => $contacto,
            'disponibilidad' => $disponibilidad,
            'costo_hora'     => $costo_hora,
            'estado'         => 'activo'
        ];

        if ($this->workerModel->create($data)) {
            $_SESSION['success'] = "Trabajador registrado exitosamente.";
            unset($_SESSION['form_worker_data']);
        } else {
            $_SESSION['error'] = "Ocurrió un error al registrar al trabajador.";
        }

        header('Location: ' . BASE_URL . '/trabajadores');
        exit();
    }

    /**
     * Formulario de edición del trabajador.
     */
    public function editar($id) {
        $id = (int)$id;
        $worker = $this->workerModel->getById($id);

        if (!$worker) {
            $_SESSION['error'] = "Trabajador no encontrado o dado de baja.";
            header('Location: ' . BASE_URL . '/trabajadores');
            exit();
        }

        require_once ROOT_PATH . '/app/views/workers/form.php';
    }

    /**
     * Procesa la actualización de datos del trabajador.
     */
    public function actualizar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/trabajadores');
            exit();
        }

        $id = (int)$id;
        $worker = $this->workerModel->getById($id);

        if (!$worker) {
            $_SESSION['error'] = "Trabajador no encontrado.";
            header('Location: ' . BASE_URL . '/trabajadores');
            exit();
        }

        if (!AuthHelper::validateCsrf($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Falsificación de petición detectada (Token CSRF inválido).";
            header('Location: ' . BASE_URL . '/trabajadores/editar/' . $id);
            exit();
        }

        $nombres = trim(filter_input(INPUT_POST, 'nombres', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $apellidos = trim(filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $documento = trim(filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $especialidades = trim(filter_input(INPUT_POST, 'especialidades', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $nivel = $_POST['nivel'] ?? 'Junior';
        $contacto = trim(filter_input(INPUT_POST, 'contacto', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $disponibilidad = $_POST['disponibilidad'] ?? 'disponible';
        $costo_hora = (float)($_POST['costo_hora'] ?? 0.00);
        $estado = $_POST['estado'] ?? 'activo';

        $errors = [];

        if (empty($nombres)) $errors[] = "El nombre es obligatorio.";
        if (empty($apellidos)) $errors[] = "El apellido es obligatorio.";
        if (empty($documento)) $errors[] = "El documento de identidad es obligatorio.";
        if (empty($especialidades)) $errors[] = "Debe registrar al menos una especialidad.";
        if ($costo_hora < 0) $errors[] = "El costo por hora no puede ser un valor negativo.";

        // Validar niveles
        $nivelesValidos = ['Junior', 'Semi-Senior', 'Senior', 'Master'];
        if (!in_array($nivel, $nivelesValidos)) {
            $errors[] = "El nivel del trabajador no es válido.";
        }

        // Validar disponibilidad
        $disponibilidadesValidas = ['disponible', 'ocupado', 'ausente'];
        if (!in_array($disponibilidad, $disponibilidadesValidas)) {
            $errors[] = "La disponibilidad no es válida.";
        }

        // Validar documento único (excluyendo a sí mismo)
        if (empty($errors)) {
            if ($this->workerModel->existsDocument($documento, $id)) {
                $errors[] = "El documento de identidad ya está registrado por otro trabajador.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . BASE_URL . '/trabajadores/editar/' . $id);
            exit();
        }

        $data = [
            'nombres'        => $nombres,
            'apellidos'      => $apellidos,
            'documento'      => $documento,
            'especialidades' => $especialidades,
            'nivel'          => $nivel,
            'contacto'       => $contacto,
            'disponibilidad' => $disponibilidad,
            'costo_hora'     => $costo_hora,
            'estado'         => $estado
        ];

        if ($this->workerModel->update($id, $data)) {
            $_SESSION['success'] = "Trabajador actualizado exitosamente.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al actualizar los datos del trabajador.";
        }

        header('Location: ' . BASE_URL . '/trabajadores');
        exit();
    }

    /**
     * Baja lógica del trabajador.
     */
    public function eliminar($id) {
        $id = (int)$id;

        if ($this->workerModel->deleteLogically($id)) {
            $_SESSION['success'] = "Trabajador dado de baja y desactivado del sistema.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al intentar dar de baja al trabajador.";
        }

        header('Location: ' . BASE_URL . '/trabajadores');
        exit();
    }
}
