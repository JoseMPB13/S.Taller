<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<div class="dashboard-header">
    <div>
        <h1>Gestión de Usuarios</h1>
        <p style="color: var(--text-muted);">Administra las cuentas de los usuarios del sistema y sus perfiles de acceso.</p>
    </div>
</div>

<!-- Mensajes de Estado (Alertas) -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php
// Cargar datos temporales de formulario para no perder el progreso si falla la validación
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>

<div class="grid-container">
    <!-- Listado de Usuarios Existentes -->
    <div class="card">
        <h2 class="card-title">Usuarios Registrados</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Documento</th>
                        <th>Correo</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                No hay usuarios registrados en el sistema.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['nombre']); ?></strong>
                                    <?php if (!empty($user['telefono'])): ?>
                                        <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($user['telefono']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['documento']); ?></td>
                                <td><?php echo htmlspecialchars($user['correo']); ?></td>
                                <td><code><?php echo htmlspecialchars($user['usuario']); ?></code></td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($user['rol_nombre']); ?></span>
                                </td>
                                <td>
                                    <?php if ($user['estado'] === 'activo'): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="<?php echo BASE_URL; ?>/usuarios/editar/<?php echo $user['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                                        <a href="<?php echo BASE_URL; ?>/usuarios/eliminar/<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de desactivar y dar de baja (lógicamente) a este usuario?')">Eliminar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Formulario para Registrar Nuevo Usuario -->
    <div class="card">
        <h2 class="card-title">Nuevo Usuario</h2>
        <form action="<?php echo BASE_URL; ?>/usuarios/guardar" method="POST">
            <!-- Token CSRF de Seguridad -->
            <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\AuthHelper::getCsrfToken(); ?>">
            <div class="form-group">
                <label class="form-label" for="rol_id">Rol de Usuario *</label>
                <select class="form-control" name="rol_id" id="rol_id" required>
                    <option value="">Seleccione un rol</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>" <?php echo (isset($formData['rol_id']) && $formData['rol_id'] == $role['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="nombre">Nombre Completo *</label>
                <input class="form-control" type="text" name="nombre" id="nombre" placeholder="Ej. Juan Pérez" value="<?php echo htmlspecialchars($formData['nombre'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="documento">Documento de Identidad *</label>
                <input class="form-control" type="text" name="documento" id="documento" placeholder="CI o DNI" value="<?php echo htmlspecialchars($formData['documento'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="correo">Correo Electrónico *</label>
                <input class="form-control" type="email" name="correo" id="correo" placeholder="juan@correo.com" value="<?php echo htmlspecialchars($formData['correo'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono de Contacto</label>
                <input class="form-control" type="text" name="telefono" id="telefono" placeholder="Ej. +591 71234567" value="<?php echo htmlspecialchars($formData['telefono'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="usuario">Nombre de Usuario (Login) *</label>
                <input class="form-control" type="text" name="usuario" id="usuario" placeholder="jperez" value="<?php echo htmlspecialchars($formData['usuario'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="contrasena">Contraseña *</label>
                <input class="form-control" type="password" name="contrasena" id="contrasena" placeholder="Contraseña segura" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirmar_contrasena">Confirmar Contraseña *</label>
                <input class="form-control" type="password" name="confirmar_contrasena" id="confirmar_contrasena" placeholder="Repita la contraseña" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">
                Registrar Usuario
            </button>
        </form>
    </div>
</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
