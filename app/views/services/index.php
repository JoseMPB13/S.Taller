<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<div class="dashboard-header">
    <div>
        <h1>Catálogo de Servicios y Mano de Obra</h1>
        <p style="color: var(--text-muted);">Administra la tarifa base, descripción y tiempos estimados de los servicios de mantenimiento técnico.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/servicios/crear" class="btn btn-primary">
            Nuevo Servicio
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
    <form action="<?php echo BASE_URL; ?>/servicios" method="GET" style="display: flex; gap: 1rem; align-items: center; margin: 0;">
        <div class="form-group" style="flex: 1; margin: 0;">
            <input class="form-control" type="text" name="search" id="search" placeholder="Buscar por nombre de servicio o descripción..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="margin: 0;">
            Buscar
        </button>
        <?php if (!empty($search)): ?>
            <a href="<?php echo BASE_URL; ?>/servicios" class="btn btn-secondary" style="margin: 0;">
                Limpiar
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabla -->
<div class="card">
    <h2 class="card-title">Listado de Servicios Ofrecidos</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Servicio / Nombre</th>
                    <th>Descripción</th>
                    <th>Tiempo Estimado</th>
                    <th>Precio Base</th>
                    <th>Impuestos / Descuentos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                            No se encontraron servicios registrados en el catálogo.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($services as $s): ?>
                        <?php 
                        $taxesData = json_decode($s['impuestos_descuentos'] ?? '{}', true);
                        $iva = $taxesData['impuesto_iva'] ?? 0.0;
                        $desc = $taxesData['descuento_max'] ?? 0.0;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($s['nombre_servicio']); ?></strong>
                            </td>
                            <td>
                                <span style="font-size: 0.9rem; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($s['descripcion'] ?: 'Sin descripción'); ?>
                                </span>
                            </td>
                            <td>
                                <code style="font-size: 1rem; color: var(--primary);"><?php echo htmlspecialchars($s['tiempo_estimado']); ?> min</code>
                                <br><small style="color: var(--text-muted);">Approx. <?php echo number_format($s['tiempo_estimado'] / 60, 1); ?> horas</small>
                            </td>
                            <td>
                                <strong><?php echo number_format($s['precio_base'], 2, ',', '.'); ?> BOB</strong>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem;">IVA: <code><?php echo $iva; ?>%</code></span>
                                <br><span style="font-size: 0.85rem; color: var(--text-muted);">Desc. Máx: <code><?php echo $desc; ?>%</code></span>
                            </td>
                            <td>
                                <?php if ($s['estado'] === 'activo'): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="<?php echo BASE_URL; ?>/servicios/editar/<?php echo $s['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                                    <form action="<?php echo BASE_URL; ?>/servicios/eliminar/<?php echo $s['id']; ?>" method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de desactivar y dar de baja (lógicamente) a este servicio?')">
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
