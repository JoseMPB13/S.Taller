-- ==========================================================
-- SCRIPT DE SEMILLA (SEED DATA) PARA PROYECTO S.TALLER
-- ==========================================================
-- Puedes importar este archivo directamente en la pestaña SQL 
-- de phpMyAdmin o ejecutarlo a través de tu cliente MySQL.

USE taller_mecanico;

-- Desactivar temporalmente la revisión de llaves foráneas para limpieza
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE auditoria_accesos;
TRUNCATE TABLE ot_servicios;
TRUNCATE TABLE ot_repuestos;
TRUNCATE TABLE ot_mecanicos;
TRUNCATE TABLE ordenes_trabajo;
TRUNCATE TABLE trabajador_especialidades;
TRUNCATE TABLE trabajadores;
TRUNCATE TABLE autos;
TRUNCATE TABLE clientes;
TRUNCATE TABLE inventario;
TRUNCATE TABLE servicios;
TRUNCATE TABLE usuarios;
TRUNCATE TABLE roles;

-- Reactivar la revisión de llaves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Insertar Roles
INSERT INTO roles (id, nombre, descripcion) VALUES 
(1, 'administrador', 'Acceso total al sistema de control y administración.'),
(2, 'operador', 'Acceso a registro de órdenes, autos y consulta de inventario.');

-- 2. Insertar Usuarios
-- Credenciales de Acceso:
-- Administrador:
--   Correo: admin@taller.com
--   Usuario: admin
--   Contraseña: Admin123!
-- Operador:
--   Correo: operador@taller.com
--   Usuario: operador
--   Contraseña: Operador123!
INSERT INTO usuarios (rol_id, nombre, documento, correo, telefono, usuario, contrasena) VALUES 
(1, 'Administrador del Sistema', '10293847', 'admin@taller.com', '+591 71234567', 'admin', '$2y$12$CzkIbIId3C1JUxGt1K7DSOB/nERO/DEPuyzx2mvwnmvi4IO0J027y'),
(2, 'Juan Operador', '87654321', 'operador@taller.com', '+591 61234567', 'operador', '$2y$12$P26sk9Ev3oIHZb5gvp6qYO0MlzU1qMLBn52jLD5R/R4dVljybIAIC');

-- 3. Insertar Clientes
INSERT INTO clientes (id, nombres, apellidos, documento, telefono, correo, direccion, observaciones, datos_facturacion) VALUES 
(1, 'José Miguel', 'Perez Barba', '1234567', '78012345', 'jose@example.com', 'Equipetrol Calle 8 #45, Santa Cruz', 'Cliente habitual de servicios de mantenimiento preventivo.', '{"razon_social": "Jose Miguel Perez", "nit_ci": "1234567"}'),
(2, 'María Fernanda', 'Gomez Suarez', '9876543', '62098765', 'maria@example.com', 'Av. Banzer Km 5, Condominio Sevilla', 'Requiere siempre revisión completa de suspensión.', '{"razon_social": "Maria Fernanda Gomez", "nit_ci": "9876543"}');

-- 4. Insertar Trabajadores (Mecánicos)
INSERT INTO trabajadores (id, nombres, apellidos, documento, nivel, contacto, disponibilidad) VALUES 
(1, 'Carlos', 'Técnico Frenos', '8877665', 'Semi-Senior', '77711122', 'disponible'),
(2, 'Juan', 'Mecánico Principal', '5544332', 'Senior', '77733344', 'disponible'),
(3, 'Luis', 'Electricista Automotriz', '4433221', 'Master', '77755566', 'disponible');

-- 4.1 Insertar Especialidades (Normalización 1NF)
INSERT INTO trabajador_especialidades (trabajador_id, especialidad) VALUES 
(1, 'Frenos'),
(1, 'Suspensión'),
(2, 'Motor'),
(2, 'Transmisiones'),
(2, 'Diagnóstico'),
(3, 'Electricidad'),
(3, 'Inyección Electrónica');

-- 5. Insertar Autos
INSERT INTO autos (cliente_id, placa, vin, marca, modelo, anio, color, kilometraje, observaciones) VALUES 
(1, '4567XYZ', 'Toyota Corolla Sedan 2018', 'Toyota', 'Corolla', 2018, 'Blanco', 45000, 'Auto en excelente estado, mantenimiento al día.'),
(2, '7890ABC', 'Suzuki Grand Vitara SUV 2015', 'Suzuki', 'Grand Vitara', 2015, 'Gris Metálico', 82000, 'Presenta ruidos leves en suspensión trasera.');

-- 6. Insertar Inventario (Repuestos)
INSERT INTO inventario (codigo_sku, nombre, categoria, unidad, costo, precio, stock, stock_minimo, ubicacion, proveedor) VALUES 
('REP-PAST-001', 'Pastillas de Freno Delanteras Toyota', 'Frenos', 'juegos', 120.00, 180.00, 15, 3, 'Estante A-12', 'Impoza Repuestos'),
('REP-FILT-002', 'Filtro de Aceite Original Toyota Corolla', 'Motor', 'unidades', 35.00, 55.00, 40, 5, 'Estante B-03', 'Autopartes El Sol'),
('REP-AMOR-003', 'Amortiguador Delantero Suzuki Grand Vitara', 'Suspensión', 'unidades', 280.00, 420.00, 8, 2, 'Rack C-02', 'Impoza Repuestos'),
('REP-ACEI-004', 'Aceite Sintético 5W30 Castrol 1 Galón', 'Motor', 'galones', 180.00, 260.00, 25, 5, 'Bodega Aceites', 'Distribuidora Castrol Bolivia');

-- 7. Insertar Servicios
INSERT INTO servicios (nombre_servicio, descripcion, tiempo_estimado, precio_base) VALUES 
('Cambio de pastillas de freno', 'Reemplazo de pastillas delanteras o traseras e inspección de discos de freno.', 45, 120.00),
('Cambio de aceite y filtro de motor', 'Drenado de aceite usado, instalación de nuevo filtro y carga de nuevo lubricante.', 30, 80.00),
('Diagnóstico computarizado completo', 'Escaneo general de sensores y módulos del auto con interpretación de códigos de falla.', 60, 150.00),
('Mantenimiento de suspensión general', 'Revisión y reemplazo de amortiguadores, bujes, muñones y terminales.', 120, 250.00);
