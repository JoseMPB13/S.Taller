-- Base de Datos para Taller Mecánico
-- Diseñado para MySQL / MariaDB (XAMPP)

CREATE DATABASE IF NOT EXISTS taller_mecanico
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE taller_mecanico;

-- 1. Roles (Soporte para RF-008)
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    descripcion VARCHAR(255) NULL,
    estado TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Usuarios (RF-001)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    documento VARCHAR(20) UNIQUE NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    telefono VARCHAR(20) NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3. Clientes (RF-002)
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    documento VARCHAR(20) UNIQUE NOT NULL,
    telefono VARCHAR(50) NOT NULL,
    correo VARCHAR(100) UNIQUE NULL,
    direccion VARCHAR(255) NULL,
    observaciones TEXT NULL,
    datos_facturacion JSON NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

-- 4. Trabajadores (RF-003)
CREATE TABLE IF NOT EXISTS trabajadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    documento VARCHAR(20) UNIQUE NOT NULL,
    especialidades TEXT NOT NULL,
    nivel ENUM('Junior', 'Semi-Senior', 'Senior', 'Master') DEFAULT 'Junior',
    contacto VARCHAR(100) NULL,
    disponibilidad ENUM('disponible', 'ocupado', 'ausente') DEFAULT 'disponible',
    costo_hora DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

-- 5. Autos (RF-004, RF-011)
CREATE TABLE IF NOT EXISTS autos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    placa VARCHAR(15) UNIQUE NOT NULL,
    vin VARCHAR(30) UNIQUE NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    anio INT NOT NULL,
    color VARCHAR(30) NULL,
    kilometraje INT NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 6. Órdenes de Trabajo (RF-005)
CREATE TABLE IF NOT EXISTS ordenes_trabajo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    auto_id INT NOT NULL,
    fecha_ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    falla_reportada TEXT NOT NULL,
    estado ENUM('pendiente', 'en_diagnostico', 'presupuestado', 'en_progreso', 'terminado', 'entregado', 'anulado', 'cerrado') DEFAULT 'pendiente',
    prioridad ENUM('baja', 'media', 'alta') DEFAULT 'media',
    diagnosticos TEXT NULL,
    trabajos TEXT NULL,
    observaciones TEXT NULL,
    fecha_cierre DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON UPDATE CASCADE,
    FOREIGN KEY (auto_id) REFERENCES autos(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 7. Inventario (RF-006)
CREATE TABLE IF NOT EXISTS inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_sku VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    unidad VARCHAR(20) NOT NULL DEFAULT 'unidades',
    costo DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    precio DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    ubicacion VARCHAR(100) NULL,
    proveedor VARCHAR(150) NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

-- 8. Servicios (RF-007)
CREATE TABLE IF NOT EXISTS servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_servicio VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    tiempo_estimado INT NOT NULL COMMENT 'Tiempo estimado en minutos',
    precio_base DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    impuestos_descuentos JSON NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

-- 9. Mecánicos Asignados a la OT (RF-012)
CREATE TABLE IF NOT EXISTS ot_mecanicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ot_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    asignado_por INT NOT NULL,
    motivo TEXT NULL,
    estado ENUM('activo', 'reasignado', 'retirado') DEFAULT 'activo',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ot_id) REFERENCES ordenes_trabajo(id) ON DELETE CASCADE,
    FOREIGN KEY (trabajador_id) REFERENCES trabajadores(id) ON UPDATE CASCADE,
    FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 10. Consumo de Repuestos por OT (RF-013)
CREATE TABLE IF NOT EXISTS ot_repuestos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ot_id INT NOT NULL,
    item_id INT NOT NULL,
    cantidad INT NOT NULL COMMENT 'Positivo para consumo, negativo para devolución',
    precio_unitario DECIMAL(10, 2) NOT NULL,
    costo_unitario DECIMAL(10, 2) NOT NULL,
    registrado_por INT NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ot_id) REFERENCES ordenes_trabajo(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventario(id) ON UPDATE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 11. Servicios Aplicados a la OT (RF-014)
CREATE TABLE IF NOT EXISTS ot_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ot_id INT NOT NULL,
    servicio_id INT NOT NULL,
    precio_aplicado DECIMAL(10, 2) NOT NULL,
    descuento_aplicado DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    tiempo_real INT NULL COMMENT 'En minutos',
    registrado_por INT NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ot_id) REFERENCES ordenes_trabajo(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON UPDATE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 12. Auditoría de Accesos (RF-008)
CREATE TABLE IF NOT EXISTS auditoria_accesos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    ip_address VARCHAR(45) NULL,
    evento ENUM('login_exitoso', 'login_fallido', 'bloqueo', 'logout') NOT NULL,
    detalles TEXT NULL,
    fecha_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Índices optimizados para búsquedas avanzadas (RF-009) y relaciones
CREATE INDEX idx_clientes_busqueda ON clientes (nombres, apellidos, documento);
CREATE INDEX idx_autos_placa ON autos (placa);
CREATE INDEX idx_ordenes_codigo ON ordenes_trabajo (codigo);
CREATE INDEX idx_inventario_sku ON inventario (codigo_sku);
