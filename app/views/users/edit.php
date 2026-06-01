<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<div class="dashboard-header">
    <div>
        <h1>Editar Usuario</h1>
        <p style="color: var(--text-muted);">Modifique la información del perfil del usuario y su estado de acceso.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/usuarios" class="btn btn-secondary">
            Volver al listado
        </a>
    </div>
</div>

<!-- Mensajes de Error en Edición -->
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 650px; margin: 0 auto;">
    <h2 class="card-title">Editar Cuenta: <strong><?php echo htmlspecialchars($user['usuario']); ?></strong></h2>
    <form action="<?php echo BASE_URL; ?>/usuarios/actualizar/<?php echo $user['id']; ?>" method="POST">
        <!-- Token CSRF de Seguridad -->
        <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\AuthHelper::getCsrfToken(); ?>">
        
        <div class="form-group">
            <label class="form-label" for="rol_id">Rol de Usuario *</label>
            <select class="form-control" name="rol_id" id="rol_id" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>" <?php echo ($user['rol_id'] == $role['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($role['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="nombre">Nombre Completo *</label>
            <input class="form-control" type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="documento">Documento de Identidad *</label>
            <input class="form-control" type="text" name="documento" id="documento" value="<?php echo htmlspecialchars($user['documento']); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="correo">Correo Electrónico *</label>
            <input class="form-control" type="email" name="correo" id="correo" value="<?php echo htmlspecialchars($user['correo']); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="telefono">Teléfono de Contacto</label>
            <input class="form-control" type="text" name="telefono" id="telefono" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="usuario">Nombre de Usuario (Login) *</label>
            <input class="form-control" type="text" name="usuario" id="usuario" value="<?php echo htmlspecialchars($user['usuario']); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="estado">Estado del Usuario *</label>
            <select class="form-control" name="estado" id="estado" required>
                <option value="activo" <?php echo ($user['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                <option value="inactivo" <?php echo ($user['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
            </select>
        </div>

        <!-- Sección de Cambio de Contraseña -->
        <div style="margin: 2rem 0 1rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: var(--primary);">Actualizar Contraseña (Opcional)</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;">
                Deje estos campos vacíos si desea conservar la contraseña actual del usuario.
            </p>
        </div>

        <div class="form-group">
            <label class="form-label" for="contrasena">Nueva Contraseña</label>
            <input class="form-control" type="password" name="contrasena" id="contrasena" placeholder="Solo si desea cambiarla">
        </div>

        <div class="form-group">
            <label class="form-label" for="confirmar_contrasena">Confirmar Nueva Contraseña</label>
            <input class="form-control" type="password" name="confirmar_contrasena" id="confirmar_contrasena" placeholder="Repita la nueva contraseña">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Guardar Cambios
            </button>
            <a href="<?php echo BASE_URL; ?>/usuarios" class="btn btn-secondary">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
