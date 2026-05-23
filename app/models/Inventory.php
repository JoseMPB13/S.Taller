<?php

namespace App\Models;

use Config\Database;
use PDO;

/**
 * Modelo de Inventario (RF-006)
 * Gestiona el stock, repuestos, consumibles y la lógica de descarga automática.
 */
class Inventory {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene todos los artículos activos del inventario.
     * Soporta buscador por SKU, nombre, categoría o ubicación.
     * 
     * @param string|null $search Término de búsqueda
     * @return array
     */
    public function getAll(?string $search = null): array {
        $sql = "SELECT * FROM inventario WHERE deleted_at IS NULL";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (codigo_sku LIKE :search_sku 
                        OR nombre LIKE :search_nom 
                        OR categoria LIKE :search_cat 
                        OR ubicacion LIKE :search_ub)";
            $searchVal = '%' . trim($search) . '%';
            $params['search_sku'] = $searchVal;
            $params['search_nom'] = $searchVal;
            $params['search_cat'] = $searchVal;
            $params['search_ub'] = $searchVal;
        }

        $sql .= " ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un artículo de inventario por su ID.
     * 
     * @param int $id ID del artículo
     * @return array|false Datos del artículo o falso si no existe
     */
    public function getById(int $id) {
        $sql = "SELECT * FROM inventario WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Registra un nuevo repuesto o artículo en el inventario.
     * 
     * @param array $data Datos del artículo
     * @return bool
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO inventario (codigo_sku, nombre, categoria, unidad, costo, precio, stock, stock_minimo, ubicacion, proveedor, estado) 
                VALUES (:codigo_sku, :nombre, :categoria, :unidad, :costo, :precio, :stock, :stock_minimo, :ubicacion, :proveedor, :estado)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'codigo_sku'   => strtoupper(trim($data['codigo_sku'])),
            'nombre'       => trim($data['nombre']),
            'categoria'    => trim($data['categoria']),
            'unidad'       => trim($data['unidad'] ?: 'unidades'),
            'costo'        => (float)($data['costo'] ?? 0.0),
            'precio'       => (float)($data['precio'] ?? 0.0),
            'stock'        => (int)($data['stock'] ?? 0),
            'stock_minimo' => (int)($data['stock_minimo'] ?? 0),
            'ubicacion'    => !empty($data['ubicacion']) ? trim($data['ubicacion']) : null,
            'proveedor'    => !empty($data['proveedor']) ? trim($data['proveedor']) : null,
            'estado'       => $data['estado'] ?? 'activo'
        ]);
    }

    /**
     * Actualiza un artículo del inventario existente.
     * 
     * @param int $id ID del artículo
     * @param array $data Nuevos datos
     * @return bool
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE inventario SET 
                    codigo_sku = :codigo_sku, 
                    nombre = :nombre, 
                    categoria = :categoria, 
                    unidad = :unidad, 
                    costo = :costo, 
                    precio = :precio, 
                    stock = :stock, 
                    stock_minimo = :stock_minimo, 
                    ubicacion = :ubicacion, 
                    proveedor = :proveedor, 
                    estado = :estado 
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id'           => $id,
            'codigo_sku'   => strtoupper(trim($data['codigo_sku'])),
            'nombre'       => trim($data['nombre']),
            'categoria'    => trim($data['categoria']),
            'unidad'       => trim($data['unidad'] ?: 'unidades'),
            'costo'        => (float)($data['costo'] ?? 0.0),
            'precio'       => (float)($data['precio'] ?? 0.0),
            'stock'        => (int)($data['stock'] ?? 0),
            'stock_minimo' => (int)($data['stock_minimo'] ?? 0),
            'ubicacion'    => !empty($data['ubicacion']) ? trim($data['ubicacion']) : null,
            'proveedor'    => !empty($data['proveedor']) ? trim($data['proveedor']) : null,
            'estado'       => $data['estado'] ?? 'activo'
        ]);
    }

    /**
     * Realiza la baja lógica (Soft Delete) del artículo.
     * 
     * @param int $id ID del artículo
     * @return bool
     */
    public function deleteLogically(int $id): bool {
        $sql = "UPDATE inventario SET estado = 'inactivo', deleted_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Valida si un SKU ya se encuentra registrado.
     * 
     * @param string $sku Código SKU
     * @param int|null $excludeId ID a excluir
     * @return bool
     */
    public function existsSku(string $sku, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM inventario WHERE codigo_sku = :sku AND deleted_at IS NULL";
        $params = ['sku' => strtoupper(trim($sku))];

        if ($excludeId !== null) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Descuenta stock de un artículo automáticamente.
     * Previene inconsistencias asegurando que no quede stock negativo.
     * 
     * @param int $id ID del artículo
     * @param int $quantity Cantidad a descontar
     * @return bool Retorna verdadero si se descontó correctamente, falso si no hay stock suficiente o el artículo no existe
     */
    public function deductStock(int $id, int $quantity): bool {
        if ($quantity <= 0) {
            return false;
        }
        $sql = "UPDATE inventario SET stock = stock - :quantity WHERE id = :id AND stock >= :quantity AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id'       => $id,
            'quantity' => $quantity
        ]);
        return $stmt->rowCount() > 0;
    }
}
