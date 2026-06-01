<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - S.Taller</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
    <style>
        /* Estilos específicos para centrar el login en pantalla completa */
        body.login-body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--bg-main);
            padding: 1.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.4);
        }
        .login-header-logo {
            text-align: center;
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            margin-bottom: 2rem;
        }
        .login-header-logo span {
            color: var(--primary);
        }
    </style>
</head>
<body class="login-body">
    <div class="card login-card">
        <div class="login-header-logo">
            S.<span>Taller</span>
        </div>
        <h2 class="card-title" style="text-align: center; border-bottom: none; padding-bottom: 0; margin-bottom: 1.5rem;">
            Control de Acceso
        </h2>
        
        <!-- Mensajes de Error y Éxito Sanitizados contra XSS -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
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

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo nl2br(htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8')); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php
        $formValue = $_SESSION['form_login_value'] ?? '';
        unset($_SESSION['form_login_value']);
        ?>

        <form action="<?php echo BASE_URL; ?>/login" method="POST">
            <!-- Token de protección contra ataques CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\AuthHelper::getCsrfToken(); ?>">

            <div class="form-group">
                <label class="form-label" for="login">Nombre de Usuario o Correo</label>
                <input class="form-control" type="text" name="login" id="login" placeholder="Ej. adminprueba o admin@prueba.com" value="<?php echo htmlspecialchars($formValue); ?>" required autofocus>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label" for="contrasena">Contraseña</label>
                <input class="form-control" type="password" name="contrasena" id="contrasena" placeholder="Ingrese su contraseña" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.8rem; font-size: 1.05rem;">
                Ingresar al Sistema
            </button>
        </form>
    </div>
</body>
</html>
