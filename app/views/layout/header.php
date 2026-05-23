<?php
// Determinar la ruta actual de forma segura para la clase activa de los enlaces del navbar
$urlParam = $_GET['url'] ?? '';
$urlParts = explode('/', rtrim($urlParam, '/'));
$currentRoute = strtolower($urlParts[0] ?? 'usuarios');
if (empty($currentRoute)) {
    $currentRoute = 'usuarios';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.Taller - Sistema de Gestión de Taller</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="<?php echo BASE_URL; ?>/usuarios" class="logo">
                S.<span>Taller</span>
            </a>
            <ul class="nav-links">
                <?php if (\App\Helpers\AuthHelper::isAdmin()): ?>
                    <li><a href="<?php echo BASE_URL; ?>/usuarios" class="<?php echo ($currentRoute === 'usuarios') ? 'active' : ''; ?>">Usuarios</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>/clientes" class="<?php echo ($currentRoute === 'clientes') ? 'active' : ''; ?>">Clientes</a></li>
                <li><a href="<?php echo BASE_URL; ?>/trabajadores" class="<?php echo ($currentRoute === 'trabajadores') ? 'active' : ''; ?>">Trabajadores</a></li>
                <li><a href="<?php echo BASE_URL; ?>/autos" class="<?php echo ($currentRoute === 'autos') ? 'active' : ''; ?>">Autos</a></li>
                <li><a href="#" style="opacity: 0.5; pointer-events: none;">Ordenes (OT)</a></li>
                <li><a href="#" style="opacity: 0.5; pointer-events: none;">Inventario</a></li>
                <li><a href="#" style="opacity: 0.5; pointer-events: none;">Servicios</a></li>
            </ul>

            <?php if (\App\Helpers\AuthHelper::isLoggedIn()): ?>
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <div style="text-align: right;">
                        <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-main);">
                            <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--primary); font-weight: 500; text-transform: uppercase;">
                            <?php echo htmlspecialchars($_SESSION['user_rol']); ?>
                        </div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/logout" class="btn btn-secondary btn-sm" style="border-color: var(--danger); color: #f87171; font-weight: 600;">
                        Cerrar Sesión
                    </a>
                </div>
            <?php endif; ?>
        </nav>
    </header>
    <main>
