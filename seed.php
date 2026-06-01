<?php
/**
 * Script de Semillero (Database Seeder) para S.Taller
 * Rellena las tablas principales con datos de prueba realistas y seguros.
 */

define('ROOT_PATH', __DIR__);

// Detectar entorno
$isCli = (php_sapi_name() === 'cli');

// Si es navegador, enviar cabecera HTML
if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<pre style='font-family: monospace; background: #0f172a; color: #f8fafc; padding: 20px; border-radius: 8px; line-height: 1.5;'>";
}

// Cargador nativo de archivos .env (basado en config.php)
if (file_exists(ROOT_PATH . '/.env')) {
    $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Variables de Conexión
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbName = $_ENV['DB_NAME'] ?? 'taller_mecanico';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$dbName;charset=$charset";
    
    // Opciones del controlador seguras
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES $charset";
    }
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "==================================================\n";
    echo "Conexión a la base de datos establecida con éxito.\n";
    echo "==================================================\n\n";

    // Desactivar temporalmente revisión de llaves foráneas para limpiar las tablas
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    echo "Limpiando tablas de base de datos...\n";
    $tables = [
        'auditoria_accesos',
        'ot_servicios',
        'ot_repuestos',
        'ot_mecanicos',
        'ordenes_trabajo',
        'trabajador_especialidades',
        'trabajadores',
        'autos',
        'clientes',
        'inventario',
        'servicios',
        'usuarios',
        'roles'
    ];
    
    foreach ($tables as $table) {
        $pdo->exec("DELETE FROM `$table`;");
        // Reiniciar contador de auto-incremento (si aplica)
        try {
            $pdo->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1;");
        } catch (\PDOException $e) {
            // Ignorar si la tabla no tiene columna AUTO_INCREMENT (como trabajador_especialidades)
        }
    }
    echo "Tablas limpiadas con éxito.\n\n";
    
    // Reactivar revisión de llaves foráneas
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 1. Insertar Roles
    echo "Insertando Roles...\n";
    $stmtRole = $pdo->prepare("INSERT INTO roles (id, nombre, descripcion) VALUES (?, ?, ?)");
    $stmtRole->execute([1, 'administrador', 'Acceso total al sistema de control y administración.']);
    $stmtRole->execute([2, 'operador', 'Acceso a registro de órdenes, autos y consulta de inventario.']);
    
    // 2. Insertar Usuarios Administrativos
    echo "Insertando Usuarios...\n";
    $stmtUser = $pdo->prepare("INSERT INTO usuarios (rol_id, nombre, documento, correo, telefono, usuario, contrasena) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // Credenciales solicitadas por el usuario:
    // Correo: admin@taller.com
    // Contraseña: Admin123!
    $adminPasswordHash = password_hash('Admin123!', PASSWORD_DEFAULT);
    $stmtUser->execute([
        1, 
        'Administrador del Sistema', 
        '10293847', 
        'admin@taller.com', 
        '+591 71234567', 
        'admin', 
        $adminPasswordHash
    ]);

    $operatorPasswordHash = password_hash('Operador123!', PASSWORD_DEFAULT);
    $stmtUser->execute([
        2, 
        'Juan Operador', 
        '87654321', 
        'operador@taller.com', 
        '+591 61234567', 
        'operador', 
        $operatorPasswordHash
    ]);

    // 3. Insertar Clientes
    echo "Insertando Clientes...\n";
    $stmtClient = $pdo->prepare("INSERT INTO clientes (id, nombres, apellidos, documento, telefono, correo, direccion, observaciones, datos_facturacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmtClient->execute([
        1,
        'José Miguel',
        'Perez Barba',
        '1234567',
        '78012345',
        'jose@example.com',
        'Equipetrol Calle 8 #45, Santa Cruz',
        'Cliente habitual de servicios de mantenimiento preventivo.',
        json_encode(['razon_social' => 'Jose Miguel Perez', 'nit_ci' => '1234567'])
    ]);

    $stmtClient->execute([
        2,
        'María Fernanda',
        'Gomez Suarez',
        '9876543',
        '62098765',
        'maria@example.com',
        'Av. Banzer Km 5, Condominio Sevilla',
        'Requiere siempre revisión completa de suspensión.',
        json_encode(['razon_social' => 'Maria Fernanda Gomez', 'nit_ci' => '9876543'])
    ]);

    // 4. Insertar Trabajadores (Mecánicos)
    echo "Insertando Trabajadores...\n";
    $stmtWorker = $pdo->prepare("INSERT INTO trabajadores (id, nombres, apellidos, documento, nivel, contacto, disponibilidad) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmtWorker->execute([1, 'Carlos', 'Técnico Frenos', '8877665', 'Semi-Senior', '77711122', 'disponible']);
    $stmtWorker->execute([2, 'Juan', 'Mecánico Principal', '5544332', 'Senior', '77733344', 'disponible']);
    $stmtWorker->execute([3, 'Luis', 'Electricista Automotriz', '4433221', 'Master', '77755566', 'disponible']);

    // 4.1 Especialidades
    echo "Asignando Especialidades a los Trabajadores...\n";
    $stmtSpec = $pdo->prepare("INSERT INTO trabajador_especialidades (trabajador_id, especialidad) VALUES (?, ?)");
    $stmtSpec->execute([1, 'Frenos']);
    $stmtSpec->execute([1, 'Suspensión']);
    $stmtSpec->execute([2, 'Motor']);
    $stmtSpec->execute([2, 'Transmisiones']);
    $stmtSpec->execute([2, 'Diagnóstico']);
    $stmtSpec->execute([3, 'Electricidad']);
    $stmtSpec->execute([3, 'Inyección Electrónica']);

    // 5. Insertar Autos
    echo "Insertando Autos...\n";
    $stmtCar = $pdo->prepare("INSERT INTO autos (cliente_id, placa, vin, marca, modelo, anio, color, kilometraje, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmtCar->execute([
        1,
        '4567XYZ',
        'Toyota Corolla Sedan 2018',
        'Toyota',
        'Corolla',
        2018,
        'Blanco',
        45000,
        'Auto en excelente estado, mantenimiento al día.'
    ]);

    $stmtCar->execute([
        2,
        '7890ABC',
        'Suzuki Grand Vitara SUV 2015',
        'Suzuki',
        'Grand Vitara',
        2015,
        'Gris Metálico',
        82000,
        'Presenta ruidos leves en suspensión trasera.'
    ]);

    // 6. Insertar Inventario (Repuestos)
    echo "Insertando Repuestos en Inventario...\n";
    $stmtInventory = $pdo->prepare("INSERT INTO inventario (codigo_sku, nombre, categoria, unidad, costo, precio, stock, stock_minimo, ubicacion, proveedor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmtInventory->execute([
        'REP-PAST-001',
        'Pastillas de Freno Delanteras Toyota',
        'Frenos',
        'juegos',
        120.00,
        180.00,
        15,
        3,
        'Estante A-12',
        'Impoza Repuestos'
    ]);

    $stmtInventory->execute([
        'REP-FILT-002',
        'Filtro de Aceite Original Toyota Corolla',
        'Motor',
        'unidades',
        35.00,
        55.00,
        40,
        5,
        'Estante B-03',
        'Autopartes El Sol'
    ]);

    $stmtInventory->execute([
        'REP-AMOR-003',
        'Amortiguador Delantero Suzuki Grand Vitara',
        'Suspensión',
        'unidades',
        280.00,
        420.00,
        8,
        2,
        'Rack C-02',
        'Impoza Repuestos'
    ]);

    $stmtInventory->execute([
        'REP-ACEI-004',
        'Aceite Sintético 5W30 Castrol 1 Galón',
        'Motor',
        'galones',
        180.00,
        260.00,
        25,
        5,
        'Bodega Aceites',
        'Distribuidora Castrol Bolivia'
    ]);

    // 7. Insertar Servicios
    echo "Insertando Servicios Base...\n";
    $stmtService = $pdo->prepare("INSERT INTO servicios (nombre_servicio, descripcion, tiempo_estimado, precio_base) VALUES (?, ?, ?, ?)");
    
    $stmtService->execute([
        'Cambio de pastillas de freno',
        'Reemplazo de pastillas delanteras o traseras e inspección de discos de freno.',
        45,
        120.00
    ]);

    $stmtService->execute([
        'Cambio de aceite y filtro de motor',
        'Drenado de aceite usado, instalación de nuevo filtro y carga de nuevo lubricante.',
        30,
        80.00
    ]);

    $stmtService->execute([
        'Diagnóstico computarizado completo',
        'Escaneo general de sensores y módulos del auto con interpretación de códigos de falla.',
        60,
        150.00
    ]);

    $stmtService->execute([
        'Mantenimiento de suspensión general',
        'Revisión y reemplazo de amortiguadores, bujes, muñones y terminales.',
        120,
        250.00
    ]);

    echo "\n==================================================\n";
    echo "Base de datos sembrada (seeded) exitosamente.\n";
    echo "==================================================\n\n";
    
    echo "DATOS DE ACCESO AL SISTEMA:\n";
    echo "---------------------------\n";
    echo "Rol: Administrador\n";
    echo "Correo: admin@taller.com\n";
    echo "Usuario: admin\n";
    echo "Contraseña: Admin123!\n\n";

    echo "Rol: Operador\n";
    echo "Correo: operador@taller.com\n";
    echo "Usuario: operador\n";
    echo "Contraseña: Operador123!\n";
    echo "==================================================\n";

} catch (\Exception $e) {
    echo "ERROR AL SEMBRAR LA BASE DE DATOS: " . $e->getMessage() . "\n";
}

if (!$isCli) {
    echo "</pre>";
}
