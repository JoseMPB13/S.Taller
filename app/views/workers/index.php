<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<div class="dashboard-header">
    <div>
        <h1>Gestión de Trabajadores (Mecánicos)</h1>
        <p style="color: var(--text-muted);">Administra la base de datos de los técnicos encargados de resolver las órdenes de trabajo.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/trabajadores/crear" class="btn btn-primary">
            Nuevo Trabajador
        </a>
    </div>
</div>

<!-- Alertas -->
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

<!-- Buscador -->
<div class="card" style="margin-bottom: 2rem; padding: 1.2rem;">
    <form action="<?php echo BASE_URL; ?>/trabajadores" method="GET" style="display: flex; gap: 1rem; align-items: center; margin: 0;">
        <div class="form-group" style="flex: 1; margin: 0;">
            <input class="form-control" type="text" name="search" id="search" placeholder="Buscar por nombre, apellido, documento o especialidad..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="margin: 0;">
            Buscar
        </button>
        <?php if (!empty($search)): ?>
            <a href="<?php echo BASE_URL; ?>/trabajadores" class="btn btn-secondary" style="margin: 0;">
                Limpiar
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabla -->
<div class="card">
    <h2 class="card-title">Listado de Personal Técnico</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Documento</th>
                    <th>Especialidades</th>
                    <th>Nivel</th>
                    <th>Contacto</th>
                    <th>Disponibilidad</th>
                    <th>Costo/Hora</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($workers)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                            No se encontraron trabajadores registrados en el sistema.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($workers as $w): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($w['nombres'] . ' ' . $w['apellidos']); ?></strong>
                            </td>
                            <td><code><?php echo htmlspecialchars($w['documento']); ?></code></td>
                            <td>
                                <?php echo htmlspecialchars($w['especialidades']); ?>
                            </td>
                            <td>
                                <span style="font-weight: 600; text-transform: uppercase; font-size: 0.85rem; color: var(--primary);">
                                    <?php echo htmlspecialchars($w['nivel']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($w['contacto'] ?? 'N/A'); ?>
                            </td>
                            <td>
                                <?php if ($w['disponibilidad'] === 'disponible'): ?>
                                    <span class="badge badge-success">Disponible</span>
                                <?php elseif ($w['disponibilidad'] === 'ocupado'): ?>
                                    <span class="badge badge-warning" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">Ocupado</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Ausente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo number_format($w['costo_hora'], 2, ',', '.'); ?> BOB</strong>
                            </td>
                            <td>
                                <?php if ($w['estado'] === 'activo'): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="<?php echo BASE_URL; ?>/trabajadores/editar/<?php echo $w['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                                    <a href="<?php echo BASE_URL; ?>/trabajadores/eliminar/<?php echo $w['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de desactivar y dar de baja (lógicamente) a este trabajador?')">Eliminar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
