<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<div class="dashboard-header">
    <div>
        <h1>Inventario de Repuestos y Consumibles</h1>
        <p style="color: var(--text-muted);">Administra el stock, costos, precios y ubicaciones físicas de los componentes mecánicos del taller.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/inventario/crear" class="btn btn-primary">
            Nuevo Artículo
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
    <form action="<?php echo BASE_URL; ?>/inventario" method="GET" style="display: flex; gap: 1rem; align-items: center; margin: 0;">
        <div class="form-group" style="flex: 1; margin: 0;">
            <input class="form-control" type="text" name="search" id="search" placeholder="Buscar por SKU, nombre, categoría o ubicación física..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="margin: 0;">
            Buscar
        </button>
        <?php if (!empty($search)): ?>
            <a href="<?php echo BASE_URL; ?>/inventario" class="btn btn-secondary" style="margin: 0;">
                Limpiar
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabla -->
<div class="card">
    <h2 class="card-title">Stock y Catálogo de Inventario</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Código SKU</th>
                    <th>Artículo / Nombre</th>
                    <th>Categoría</th>
                    <th>Costo</th>
                    <th>Precio Venta</th>
                    <th style="text-align: center;">Stock Actual</th>
                    <th style="text-align: center;">Mínimo</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                            No se encontraron artículos registrados en el inventario.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $i): ?>
                        <?php 
                        $isCritical = ($i['stock'] <= $i['stock_minimo']);
                        $stockClass = $isCritical ? 'color: #ef4444; font-weight: 700;' : 'color: #10b981; font-weight: 600;';
                        ?>
                        <tr style="<?php echo $isCritical ? 'background: rgba(239, 68, 68, 0.03);' : ''; ?>">
                            <td>
                                <code style="font-size: 1rem; letter-spacing: 0.5px;"><?php echo htmlspecialchars(strtoupper($i['codigo_sku'])); ?></code>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($i['nombre']); ?></strong>
                                <?php if ($isCritical && $i['estado'] === 'activo'): ?>
                                    <br><span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; font-weight: 600; display: inline-block; margin-top: 4px;">
                                        ⚠️ STOCK CRÍTICO - REABASTECER
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary" style="background: rgba(255, 255, 255, 0.08); font-size: 0.8rem;"><?php echo htmlspecialchars($i['categoria']); ?></span>
                            </td>
                            <td><?php echo number_format($i['costo'], 2, ',', '.'); ?> BOB</td>
                            <td><strong><?php echo number_format($i['precio'], 2, ',', '.'); ?> BOB</strong></td>
                            <td style="text-align: center; <?php echo $stockClass; ?>">
                                <?php echo htmlspecialchars($i['stock']); ?> <small style="font-size: 0.8rem; font-weight: normal; color: var(--text-muted);"><?php echo htmlspecialchars($i['unidad']); ?></small>
                            </td>
                            <td style="text-align: center; color: var(--text-muted);">
                                <?php echo htmlspecialchars($i['stock_minimo']); ?>
                            </td>
                            <td>
                                <span>📍 <?php echo htmlspecialchars($i['ubicacion'] ?? 'No especificada'); ?></span>
                                <?php if (!empty($i['proveedor'])): ?>
                                    <br><small style="color: var(--text-muted);">Prov: <?php echo htmlspecialchars($i['proveedor']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($i['estado'] === 'activo'): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="<?php echo BASE_URL; ?>/inventario/editar/<?php echo $i['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                                    <form action="<?php echo BASE_URL; ?>/inventario/eliminar/<?php echo $i['id']; ?>" method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de desactivar y dar de baja (lógicamente) a este artículo?')">
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
