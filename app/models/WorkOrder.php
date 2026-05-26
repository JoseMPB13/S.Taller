<?php

namespace App\Models;

use Config\Database;
use PDO;
use Exception;

/**
 * Modelo de Órdenes de Trabajo (RF-005)
 * Gestiona el encabezado de las OTs y la asignación inicial de mecánicos.
 */
class WorkOrder {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene el listado de órdenes de trabajo.
     */
    public function getAll(?string $search = null, ?string $estado = null): array {
        $sql = "SELECT ot.id, ot.codigo, ot.fecha_ingreso, ot.falla_reportada, ot.estado, ot.prioridad, 
                       c.nombres as cliente_nombres, c.apellidos as cliente_apellidos, 
                       a.placa as auto_placa, a.marca as auto_marca
                FROM ordenes_trabajo ot
                INNER JOIN clientes c ON ot.cliente_id = c.id
                INNER JOIN autos a ON ot.auto_id = a.id
                WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (ot.codigo LIKE :search OR c.nombres LIKE :search OR c.apellidos LIKE :search OR a.placa LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if ($estado) {
            $sql .= " AND ot.estado = :estado";
            $params['estado'] = $estado;
        }

        $sql .= " ORDER BY ot.fecha_ingreso DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una OT por su ID.
     */
    public function getById(int $id) {
        $sql = "SELECT ot.*, c.nombres as cliente_nombres, c.apellidos as cliente_apellidos, c.documento as cliente_documento,
                       a.placa as auto_placa, a.marca as auto_marca, a.modelo as auto_modelo
                FROM ordenes_trabajo ot
                INNER JOIN clientes c ON ot.cliente_id = c.id
                INNER JOIN autos a ON ot.auto_id = a.id
                WHERE ot.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtiene el mecánico activo asignado a una OT.
     */
    public function getWorkerByOtId(int $ot_id) {
        $sql = "SELECT t.id, t.nombres, t.apellidos, om.fecha_asignacion 
                FROM ot_mecanicos om 
                INNER JOIN trabajadores t ON om.trabajador_id = t.id 
                WHERE om.ot_id = :ot_id AND om.estado = 'activo' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['ot_id' => $ot_id]);
        return $stmt->fetch();
    }

    /**
     * Crea una Orden de Trabajo (Encabezado) y asigna un mecánico usando Transacciones.
     */
    public function create(array $data, int $usuario_asignador, ?int $mecanico_id = null): bool {
        try {
            $this->db->beginTransaction();

            // Generar Código (OT-YYYY-XXXX)
            $year = date('Y');
            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM ordenes_trabajo WHERE YEAR(fecha_ingreso) = :year");
            $stmtCount->execute(['year' => $year]);
            $count = $stmtCount->fetchColumn() + 1;
            $codigo = sprintf("OT-%s-%04d", $year, $count);

            // Insertar Encabezado
            $sql = "INSERT INTO ordenes_trabajo (codigo, cliente_id, auto_id, falla_reportada, estado, prioridad, observaciones) 
                    VALUES (:codigo, :cliente_id, :auto_id, :falla_reportada, :estado, :prioridad, :observaciones)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'codigo' => $codigo,
                'cliente_id' => $data['cliente_id'],
                'auto_id' => $data['auto_id'],
                'falla_reportada' => trim($data['falla_reportada']),
                'estado' => 'pendiente',
                'prioridad' => $data['prioridad'] ?? 'media',
                'observaciones' => !empty($data['observaciones']) ? trim($data['observaciones']) : null
            ]);
            $ot_id = $this->db->lastInsertId();

            // Insertar Asignación de Mecánico (si fue provisto)
            if ($mecanico_id) {
                $sqlMecanico = "INSERT INTO ot_mecanicos (ot_id, trabajador_id, asignado_por) VALUES (:ot_id, :trabajador_id, :asignado_por)";
                $stmtMec = $this->db->prepare($sqlMecanico);
                $stmtMec->execute([
                    'ot_id' => $ot_id,
                    'trabajador_id' => $mecanico_id,
                    'asignado_por' => $usuario_asignador
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating WorkOrder: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el estado de la Orden de Trabajo.
     */
    public function updateStatus(int $id, string $nuevo_estado): bool {
        $sql = "UPDATE ordenes_trabajo SET estado = :estado";
        if (in_array($nuevo_estado, ['terminado', 'entregado', 'anulado', 'cerrado'])) {
            $sql .= ", fecha_cierre = NOW()";
        } else {
            $sql .= ", fecha_cierre = NULL"; 
        }
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'estado' => $nuevo_estado,
            'id' => $id
        ]);
    }
    /**
     * Cuenta el total de órdenes activas (no cerradas ni anuladas).
     */
    public function countActive(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM ordenes_trabajo WHERE estado NOT IN ('cerrado', 'anulado', 'entregado')");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtiene las OTs más recientes.
     */
    public function getRecent(int $limit = 5): array {
        $sql = "SELECT ot.id, ot.codigo, ot.fecha_ingreso, ot.estado, 
                       c.nombres as cliente_nombres, c.apellidos as cliente_apellidos, 
                       a.placa as auto_placa
                FROM ordenes_trabajo ot
                INNER JOIN clientes c ON ot.cliente_id = c.id
                INNER JOIN autos a ON ot.auto_id = a.id
                ORDER BY ot.fecha_ingreso DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el total recaudado de OTs 'cerrado'.
     * Suma los repuestos (cantidad * precio) y servicios (precio_aplicado - descuento).
     */
    public function getTotalRevenue(): float {
        // Query para sumar Repuestos de OTs cerradas
        $sqlRepuestos = "SELECT COALESCE(SUM(or_rep.cantidad * or_rep.precio_unitario), 0) as total_rep 
                         FROM ot_repuestos or_rep
                         INNER JOIN ordenes_trabajo ot ON or_rep.ot_id = ot.id
                         WHERE ot.estado = 'cerrado'";
        
        // Query para sumar Servicios de OTs cerradas
        $sqlServicios = "SELECT COALESCE(SUM(os.precio_aplicado - os.descuento_aplicado), 0) as total_ser
                         FROM ot_servicios os
                         INNER JOIN ordenes_trabajo ot ON os.ot_id = ot.id
                         WHERE ot.estado = 'cerrado'";

        $totalRep = (float)$this->db->query($sqlRepuestos)->fetchColumn();
        $totalSer = (float)$this->db->query($sqlServicios)->fetchColumn();

        return $totalRep + $totalSer;
    }
}
