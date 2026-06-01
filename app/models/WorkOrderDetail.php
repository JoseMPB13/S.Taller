<?php

namespace App\Models;

use Config\Database;
use PDO;
use Exception;

/**
 * Modelo de Detalles de Órdenes de Trabajo
 * Maneja asignaciones de mecánicos (RF-012), consumos de repuestos (RF-013) y servicios aplicados (RF-014).
 */
class WorkOrderDetail extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Obtiene los mecánicos asignados a la OT.
     */
    public function getMechanics(int $ot_id): array {
        $sql = "SELECT om.*, t.nombres, t.apellidos, u.nombre as asignado_por_nombre 
                FROM ot_mecanicos om
                INNER JOIN trabajadores t ON om.trabajador_id = t.id
                INNER JOIN usuarios u ON om.asignado_por = u.id
                WHERE om.ot_id = :ot_id
                ORDER BY om.fecha_asignacion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['ot_id' => $ot_id]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los repuestos/insumos utilizados en la OT.
     */
    public function getParts(int $ot_id): array {
        $sql = "SELECT orp.*, i.codigo_sku, i.nombre as repuesto_nombre, u.nombre as registrado_por_nombre 
                FROM ot_repuestos orp
                INNER JOIN inventario i ON orp.item_id = i.id
                INNER JOIN usuarios u ON orp.registrado_por = u.id
                WHERE orp.ot_id = :ot_id
                ORDER BY orp.fecha_registro DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['ot_id' => $ot_id]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los servicios aplicados en la OT.
     */
    public function getServices(int $ot_id): array {
        $sql = "SELECT os.*, s.nombre_servicio, u.nombre as registrado_por_nombre 
                FROM ot_servicios os
                INNER JOIN servicios s ON os.servicio_id = s.id
                INNER JOIN usuarios u ON os.registrado_por = u.id
                WHERE os.ot_id = :ot_id
                ORDER BY os.fecha_registro DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['ot_id' => $ot_id]);
        return $stmt->fetchAll();
    }

    /**
     * Asigna un mecánico a la OT.
     */
    public function addMechanic(int $ot_id, int $trabajador_id, int $asignado_por, ?string $motivo = null): bool {
        // Verificar si ya está asignado y activo
        $check = $this->db->prepare("SELECT COUNT(*) FROM ot_mecanicos WHERE ot_id = :ot_id AND trabajador_id = :trab_id AND estado = 'activo'");
        $check->execute(['ot_id' => $ot_id, 'trab_id' => $trabajador_id]);
        if ($check->fetchColumn() > 0) {
            return false; // Ya está asignado
        }

        $sql = "INSERT INTO ot_mecanicos (ot_id, trabajador_id, asignado_por, motivo, estado) 
                VALUES (:ot_id, :trab_id, :asignado_por, :motivo, 'activo')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'ot_id' => $ot_id,
            'trab_id' => $trabajador_id,
            'asignado_por' => $asignado_por,
            'motivo' => $motivo
        ]);
    }

    /**
     * Añade un repuesto a la OT y descuenta el stock atómicamente.
     */
    public function addPart(int $ot_id, int $item_id, int $cantidad, float $precio, float $costo, int $user_id): bool {
        if ($cantidad <= 0) return false;

        try {
            $this->db->beginTransaction();

            // 1. Descontar stock (asegurando que haya suficiente con la condición WHERE stock >= :cant)
            $sqlDeduct = "UPDATE inventario SET stock = stock - :cant WHERE id = :item_id AND stock >= :cant";
            $stmtDeduct = $this->db->prepare($sqlDeduct);
            $stmtDeduct->execute([
                'cant' => $cantidad,
                'item_id' => $item_id
            ]);

            // Si no se afectó ninguna fila, es porque no hay stock suficiente o el item no existe
            if ($stmtDeduct->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }

            // 2. Registrar el consumo en la OT
            $sqlInsert = "INSERT INTO ot_repuestos (ot_id, item_id, cantidad, precio_unitario, costo_unitario, registrado_por) 
                          VALUES (:ot_id, :item_id, :cantidad, :precio, :costo, :reg_por)";
            $stmtInsert = $this->db->prepare($sqlInsert);
            $stmtInsert->execute([
                'ot_id' => $ot_id,
                'item_id' => $item_id,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'costo' => $costo,
                'reg_por' => $user_id
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error adding part to OT: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Añade un servicio a la OT.
     */
    public function addService(int $ot_id, int $servicio_id, float $precio_aplicado, float $descuento_aplicado, int $tiempo_real, int $user_id): bool {
        $sql = "INSERT INTO ot_servicios (ot_id, servicio_id, precio_aplicado, descuento_aplicado, tiempo_real, registrado_por) 
                VALUES (:ot_id, :serv_id, :precio, :desc, :tiempo, :reg_por)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'ot_id' => $ot_id,
            'serv_id' => $servicio_id,
            'precio' => $precio_aplicado,
            'desc' => $descuento_aplicado,
            'tiempo' => $tiempo_real,
            'reg_por' => $user_id
        ]);
    }
}
