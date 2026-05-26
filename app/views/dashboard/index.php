<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<div class="dashboard-header" style="margin-bottom: 2rem;">
    <div>
        <h1>Dashboard Principal</h1>
        <p style="color: var(--text-muted);">Bienvenido de nuevo. Aquí tienes un resumen del estado del taller.</p>
    </div>
</div>

<!-- Tarjetas de Métricas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
    
    <!-- OT Activas -->
    <div class="card" style="display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem; border-left: 4px solid var(--primary);">
        <div style="font-size: 3rem; opacity: 0.8;">🛠️</div>
        <div>
            <div style="font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Órdenes Activas</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: var(--text-color);"><?php echo $total_ots_activas; ?></div>
        </div>
    </div>

    <!-- Clientes Registrados -->
    <div class="card" style="display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem; border-left: 4px solid #3b82f6;">
        <div style="font-size: 3rem; opacity: 0.8;">👥</div>
        <div>
            <div style="font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Total Clientes</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: var(--text-color);"><?php echo $total_clientes; ?></div>
        </div>
    </div>

    <!-- Ingresos Recaudados -->
    <div class="card" style="display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem; border-left: 4px solid #10b981;">
        <div style="font-size: 3rem; opacity: 0.8;">💰</div>
        <div>
            <div style="font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Ingresos Totales (BOB)</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: #10b981;"><?php echo number_format($total_recaudado, 2, ',', '.'); ?></div>
        </div>
    </div>
</div>

<!-- Tablas Inferiores -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    
    <!-- Últimas Órdenes de Trabajo -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 class="card-title" style="margin: 0;">Últimas Órdenes de Trabajo</h2>
            <a href="<?php echo BASE_URL; ?>/ordenes" class="btn btn-secondary btn-sm">Ver Todas</a>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ots_recientes)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No hay OTs recientes.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ots_recientes as $ot): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ot['codigo']); ?></strong><br><small style="color:var(--text-muted);"><?php echo date('d/m/Y', strtotime($ot['fecha_ingreso'])); ?></small></td>
                                <td><?php echo htmlspecialchars($ot['cliente_nombres'] . ' ' . $ot['cliente_apellidos']); ?></td>
                                <td><?php echo htmlspecialchars($ot['auto_placa']); ?></td>
                                <td>
                                    <?php
                                    $estadoClass = match($ot['estado']) {
                                        'pendiente' => 'badge bg-warning text-dark',
                                        'en_progreso' => 'badge bg-primary text-white',
                                        'terminado' => 'badge bg-info text-dark',
                                        'cerrado' => 'badge bg-success text-white',
                                        'anulado' => 'badge bg-danger text-white',
                                        default => 'badge bg-secondary text-white'
                                    };
                                    ?>
                                    <span class="<?php echo $estadoClass; ?>" style="padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                                        <?php echo ucfirst(str_replace('_', ' ', $ot['estado'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/ordenes/detalles/<?php echo $ot['id']; ?>" class="btn btn-secondary btn-sm" title="Ver Detalles">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alertas Críticas (Inventario Bajo) -->
    <div class="card" style="border: 1px solid rgba(239, 68, 68, 0.3);">
        <h2 class="card-title" style="color: var(--danger); display: flex; align-items: center; gap: 0.5rem;">
            ⚠️ Alertas de Inventario
        </h2>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">Repuestos por debajo del stock mínimo (Requieren compra urgente).</p>
        
        <?php if (empty($alertas_inventario)): ?>
            <div style="text-align: center; padding: 2rem 1rem; color: #10b981; background: rgba(16, 185, 129, 0.1); border-radius: 6px;">
                <strong>✅ Inventario Saludable</strong><br>
                <small>Ningún artículo está por debajo de su límite.</small>
            </div>
        <?php else: ?>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach ($alertas_inventario as $inv): ?>
                    <li style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(239, 68, 68, 0.05); margin-bottom: 0.5rem; border-radius: 4px;">
                        <div>
                            <div style="font-weight: 600; font-size: 0.95rem;"><?php echo htmlspecialchars($inv['codigo_sku'] . ' - ' . $inv['nombre']); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Mínimo requerido: <?php echo $inv['stock_minimo']; ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.2rem; font-weight: 800; color: var(--danger);"><?php echo $inv['stock']; ?></div>
                            <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--danger);">En Stock</div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div style="margin-top: 1rem; text-align: center;">
                <a href="<?php echo BASE_URL; ?>/inventario" class="btn btn-secondary btn-sm" style="width: 100%;">Gestionar Inventario</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
