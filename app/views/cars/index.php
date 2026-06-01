<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<div class="dashboard-header">
    <div>
        <h1>Gestión de Vehículos (Autos)</h1>
        <p style="color: var(--text-muted);">Administra los vehículos registrados de los clientes y sus especificaciones técnicas.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/autos/crear" class="btn btn-primary">
            Nuevo Vehículo
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
    <form action="<?php echo BASE_URL; ?>/autos" method="GET" style="display: flex; gap: 1rem; align-items: center; margin: 0;">
        <div class="form-group" style="flex: 1; margin: 0;">
            <input class="form-control" type="text" name="search" id="search" placeholder="Buscar por placa, marca, modelo o nombre/documento del propietario..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="margin: 0;">
            Buscar
        </button>
        <?php if (!empty($search)): ?>
            <a href="<?php echo BASE_URL; ?>/autos" class="btn btn-secondary" style="margin: 0;">
                Limpiar
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabla -->
<div class="card">
    <h2 class="card-title">Listado de Vehículos</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Placa</th>
                    <th>Especificaciones</th>
                    <th>Color y Año</th>
                    <th>Kilometraje</th>
                    <th>Propietario</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cars)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                            No se encontraron vehículos registrados en el sistema.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cars as $c): ?>
                        <tr>
                            <td>
                                <strong style="font-size: 1.1rem; letter-spacing: 1px; color: var(--primary);">
                                    <?php echo htmlspecialchars(strtoupper($c['placa'])); ?>
                                </strong>
                                <?php if (!empty($c['vin'])): ?>
                                    <br><small style="color: var(--text-muted); font-family: monospace;">VIN: <?php echo htmlspecialchars(strtoupper($c['vin'])); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($c['marca'] . ' - ' . $c['modelo']); ?></strong>
                                <?php if (!empty($c['observaciones'])): ?>
                                    <br><small style="color: var(--text-muted); font-style: italic;">
                                        Nota: <?php echo htmlspecialchars(mb_strimwidth($c['observaciones'], 0, 40, "...")); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span><?php echo htmlspecialchars($c['color'] ?? 'No especificado'); ?></span>
                                <br><small style="color: var(--text-muted);">Año: <?php echo htmlspecialchars($c['anio']); ?></small>
                            </td>
                            <td>
                                <code><?php echo number_format($c['kilometraje'], 0, ',', '.'); ?> Km</code>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($c['propietario_nombre']); ?></strong>
                                <br><small style="color: var(--text-muted);">Doc: <?php echo htmlspecialchars($c['propietario_documento']); ?></small>
                            </td>
                            <td>
                                <?php if ($c['estado'] === 'activo'): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="<?php echo BASE_URL; ?>/autos/editar/<?php echo $c['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                                    <form action="<?php echo BASE_URL; ?>/autos/eliminar/<?php echo $c['id']; ?>" method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de desactivar y dar de baja (lógicamente) a este vehículo?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\AuthHelper::generateCsrf(); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
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
