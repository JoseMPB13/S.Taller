<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<div class="dashboard-header">
    <div>
        <h1>Gestión de Clientes</h1>
        <p style="color: var(--text-muted);">Administra la base de datos de los propietarios de vehículos que ingresan al taller.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/clientes/crear" class="btn btn-primary">
            Nuevo Cliente
        </a>
    </div>
</div>

<!-- Alertas de Éxito o Error -->
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

<!-- Barra de Búsqueda -->
<div class="card" style="margin-bottom: 2rem; padding: 1.2rem;">
    <form action="<?php echo BASE_URL; ?>/clientes" method="GET" style="display: flex; gap: 1rem; align-items: center; margin: 0;">
        <div class="form-group" style="flex: 1; margin: 0;">
            <input class="form-control" type="text" name="search" id="search" placeholder="Buscar por nombres, apellidos o documento..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="margin: 0;">
            Buscar
        </button>
        <?php if (!empty($search)): ?>
            <a href="<?php echo BASE_URL; ?>/clientes" class="btn btn-secondary" style="margin: 0;">
                Limpiar
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabla de Resultados -->
<div class="card">
    <h2 class="card-title">Listado de Clientes</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Documento</th>
                    <th>Contacto</th>
                    <th>Dirección</th>
                    <th>Facturación</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                            No se encontraron clientes registrados en el sistema.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clients as $c): ?>
                        <?php 
                        // Deserializar datos de facturación
                        $facturacion = json_decode($c['datos_facturacion'] ?? '', true);
                        $nit = !empty($facturacion['nit']) ? $facturacion['nit'] : 'Sin NIT';
                        $razon = !empty($facturacion['razon_social']) ? $facturacion['razon_social'] : 'Sin Razón Social';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($c['nombres'] . ' ' . $c['apellidos']); ?></strong>
                                <?php if (!empty($c['observaciones'])): ?>
                                    <br><small style="color: var(--text-muted); font-style: italic;" title="<?php echo htmlspecialchars($c['observaciones']); ?>">
                                        Obs: <?php echo htmlspecialchars(mb_strimwidth($c['observaciones'], 0, 40, "...")); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo htmlspecialchars($c['documento']); ?></code></td>
                            <td>
                                <span>📞 <?php echo htmlspecialchars($c['telefono']); ?></span>
                                <?php if (!empty($c['correo'])): ?>
                                    <br><small style="color: var(--text-muted);">📧 <?php echo htmlspecialchars($c['correo']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($c['direccion'] ?? 'No especificada'); ?>
                            </td>
                            <td>
                                <small style="font-weight: 600;">NIT: <?php echo htmlspecialchars($nit); ?></small><br>
                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars($razon); ?></small>
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
                                    <a href="<?php echo BASE_URL; ?>/clientes/editar/<?php echo $c['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                                    <a href="<?php echo BASE_URL; ?>/clientes/eliminar/<?php echo $c['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de desactivar y dar de baja (lógicamente) a este cliente?')">Eliminar</a>
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
