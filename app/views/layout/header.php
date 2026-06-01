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
                <li><a href="<?php echo BASE_URL; ?>/dashboard" class="<?php echo ($currentRoute === 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                <?php if (\App\Helpers\AuthHelper::isAdmin()): ?>
                    <li><a href="<?php echo BASE_URL; ?>/usuarios" class="<?php echo ($currentRoute === 'usuarios') ? 'active' : ''; ?>">Usuarios</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>/clientes" class="<?php echo ($currentRoute === 'clientes') ? 'active' : ''; ?>">Clientes</a></li>
                <li><a href="<?php echo BASE_URL; ?>/trabajadores" class="<?php echo ($currentRoute === 'trabajadores') ? 'active' : ''; ?>">Trabajadores</a></li>
                <li><a href="<?php echo BASE_URL; ?>/autos" class="<?php echo ($currentRoute === 'autos') ? 'active' : ''; ?>">Autos</a></li>
                <li><a href="<?php echo BASE_URL; ?>/ordenes" class="<?php echo ($currentRoute === 'ordenes') ? 'active' : ''; ?>">Ordenes (OT)</a></li>
                <li><a href="<?php echo BASE_URL; ?>/inventario" class="<?php echo ($currentRoute === 'inventario') ? 'active' : ''; ?>">Inventario</a></li>
                <li><a href="<?php echo BASE_URL; ?>/servicios" class="<?php echo ($currentRoute === 'servicios') ? 'active' : ''; ?>">Servicios</a></li>
            </ul>

            <?php if (\App\Helpers\AuthHelper::isLoggedIn()): ?>
                <div class="user-nav-profile">
                    <div class="user-nav-info">
                        <div class="user-nav-name">
                            <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>
                        </div>
                        <div class="user-nav-role">
                            <?php echo htmlspecialchars($_SESSION['user_rol']); ?>
                        </div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/logout" class="btn btn-logout btn-sm">
                        Cerrar Sesión
                    </a>
                </div>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <!-- Mensajes de Alerta Globales Sanitizados contra XSS (mitigación de vulnerabilidades) -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <?php echo nl2br(htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8')); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger" style="margin-bottom: 2rem;">
                <?php 
                $errors = explode('<br>', $_SESSION['error']);
                $escaped_errors = array_map(function($e) {
                    return htmlspecialchars($e, ENT_QUOTES, 'UTF-8');
                }, $errors);
                echo implode('<br>', $escaped_errors); 
                unset($_SESSION['error']); 
                ?>
            </div>
        <?php endif; ?>

