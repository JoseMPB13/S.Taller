<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<div class="dashboard-header">
    <div>
        <h1>Detalles de OT: <?php echo htmlspecialchars($ot['codigo']); ?></h1>
        <p style="color: var(--text-muted);">
            Propietario: <strong><?php echo htmlspecialchars($ot['cliente_nombres'] . ' ' . $ot['cliente_apellidos']); ?></strong> | 
            Vehículo: <strong><?php echo htmlspecialchars($ot['auto_marca'] . ' ' . $ot['auto_modelo'] . ' (' . $ot['auto_placa'] . ')'); ?></strong>
        </p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/ordenes" class="btn btn-secondary">
            Volver a Órdenes
        </a>
    </div>
</div>

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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Columna Principal: Repuestos y Servicios -->
    <div>
        <!-- PANEL: SERVICIOS -->
        <div class="card" style="margin-bottom: 2rem;">
            <h2 class="card-title">Servicios de Mano de Obra Aplicados</h2>
            
            <div class="table-responsive" style="margin-bottom: 1.5rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Tiempo Real</th>
                            <th>Precio Cobrado</th>
                            <th>Descuento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalServicios = 0;
                        if (empty($servicios_aplicados)): ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No hay servicios registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($servicios_aplicados as $sa): 
                                $sub = $sa['precio_aplicado'] - $sa['descuento_aplicado'];
                                $totalServicios += $sub;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sa['nombre_servicio']); ?></td>
                                    <td><?php echo $sa['tiempo_real'] ? htmlspecialchars($sa['tiempo_real']) . ' min' : '-'; ?></td>
                                    <td><?php echo number_format($sa['precio_aplicado'], 2, ',', '.'); ?> BOB</td>
                                    <td style="color: var(--danger);">- <?php echo number_format($sa['descuento_aplicado'], 2, ',', '.'); ?> BOB</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if ($totalServicios > 0): ?>
                    <tfoot>
                        <tr>
                            <th colspan="2" style="text-align: right;">Subtotal Servicios:</th>
                            <th colspan="2" style="color: var(--primary); font-size: 1.1rem;"><?php echo number_format($totalServicios, 2, ',', '.'); ?> BOB</th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Formulario para agregar servicio -->
            <form action="<?php echo BASE_URL; ?>/ordenes/agregar_servicio/<?php echo $ot['id']; ?>" method="POST" style="background: var(--bg-alt); padding: 1rem; border-radius: 6px;">
                <h4 style="margin-top: 0; margin-bottom: 1rem; font-size: 1rem;">+ Agregar Servicio</h4>
                <div style="display: flex; gap: 1rem; align-items: flex-end;">
                    <div class="form-group" style="flex: 2; margin: 0;">
                        <label class="form-label" style="font-size: 0.8rem;">Servicio del Catálogo</label>
                        <select class="form-control" name="servicio_id" required>
                            <option value="">Seleccione un servicio...</option>
                            <?php foreach ($servicios as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['nombre_servicio']); ?> (Base: <?php echo $s['precio_base']; ?> BOB)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1; margin: 0;">
                        <label class="form-label" style="font-size: 0.8rem;">Precio Acordado</label>
                        <input class="form-control" type="number" name="precio_aplicado" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin: 0;">Aplicar</button>
                </div>
            </form>
        </div>

        <!-- PANEL: REPUESTOS E INSUMOS -->
        <div class="card">
            <h2 class="card-title">Repuestos e Insumos Consumidos</h2>
            
            <div class="table-responsive" style="margin-bottom: 1.5rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Repuesto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalRepuestos = 0;
                        if (empty($repuestos_consumidos)): ?>
                            <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No hay repuestos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($repuestos_consumidos as $rp): 
                                $subRep = $rp['cantidad'] * $rp['precio_unitario'];
                                $totalRepuestos += $subRep;
                            ?>
                                <tr>
                                    <td><code style="font-size: 0.85rem;"><?php echo htmlspecialchars($rp['codigo_sku']); ?></code></td>
                                    <td><?php echo htmlspecialchars($rp['repuesto_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($rp['cantidad']); ?></td>
                                    <td><?php echo number_format($rp['precio_unitario'], 2, ',', '.'); ?> BOB</td>
                                    <td><strong><?php echo number_format($subRep, 2, ',', '.'); ?> BOB</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if ($totalRepuestos > 0): ?>
                    <tfoot>
                        <tr>
                            <th colspan="4" style="text-align: right;">Subtotal Repuestos:</th>
                            <th style="color: var(--primary); font-size: 1.1rem;"><?php echo number_format($totalRepuestos, 2, ',', '.'); ?> BOB</th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Formulario para agregar repuesto -->
            <form action="<?php echo BASE_URL; ?>/ordenes/agregar_repuesto/<?php echo $ot['id']; ?>" method="POST" style="background: var(--bg-alt); padding: 1rem; border-radius: 6px;">
                <h4 style="margin-top: 0; margin-bottom: 1rem; font-size: 1rem;">+ Descontar Repuesto del Inventario</h4>
                <div style="display: flex; gap: 1rem; align-items: flex-end;">
                    <div class="form-group" style="flex: 2; margin: 0;">
                        <label class="form-label" style="font-size: 0.8rem;">Insumo (Stock Disponible)</label>
                        <select class="form-control" name="item_id" required>
                            <option value="">Seleccione el repuesto...</option>
                            <?php foreach ($inventario as $inv): ?>
                                <?php if ($inv['stock'] > 0): ?>
                                <option value="<?php echo $inv['id']; ?>">
                                    <?php echo htmlspecialchars($inv['codigo_sku'] . ' - ' . $inv['nombre']); ?> (Stock: <?php echo $inv['stock']; ?>)
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1; margin: 0;">
                        <label class="form-label" style="font-size: 0.8rem;">Cantidad</label>
                        <input class="form-control" type="number" name="cantidad" min="1" value="1" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin: 0;">Descontar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Columna Lateral: Mecánicos, Estado y Resumen -->
    <div>
        <!-- PANEL: MECÁNICOS -->
        <div class="card" style="margin-bottom: 2rem;">
            <h2 class="card-title">Mecánicos Asignados</h2>
            <ul style="list-style: none; padding: 0; margin: 0 0 1.5rem 0;">
                <?php if (empty($mecanicos_asignados)): ?>
                    <li style="color: var(--text-muted); font-size: 0.9rem;">Sin mecánicos asignados.</li>
                <?php else: ?>
                    <?php foreach ($mecanicos_asignados as $ma): ?>
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1.2rem;">👨‍🔧</span>
                            <div>
                                <div style="font-weight: 600; font-size: 0.95rem;"><?php echo htmlspecialchars($ma['nombres'] . ' ' . $ma['apellidos']); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Asignado: <?php echo date('d/m/Y', strtotime($ma['fecha_asignacion'])); ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <form action="<?php echo BASE_URL; ?>/ordenes/agregar_mecanico/<?php echo $ot['id']; ?>" method="POST">
                <div class="form-group" style="margin-bottom: 0.5rem;">
                    <select class="form-control" name="trabajador_id" style="font-size: 0.85rem;" required>
                        <option value="">+ Añadir Mecánico...</option>
                        <?php foreach ($trabajadores as $tr): ?>
                            <?php if ($tr['estado'] === 'activo'): ?>
                                <option value="<?php echo $tr['id']; ?>"><?php echo htmlspecialchars($tr['nombres'] . ' ' . $tr['apellidos']); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;">Asignar</button>
            </form>
        </div>

        <!-- PANEL: RESUMEN Y ESTADO -->
        <div class="card">
            <h2 class="card-title">Resumen de OT</h2>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Estado Actual</label>
                <div style="font-size: 1.2rem; font-weight: 700; text-transform: uppercase; color: var(--primary);">
                    <?php echo htmlspecialchars(str_replace('_', ' ', $ot['estado'])); ?>
                </div>
            </div>

            <div style="background: rgba(16, 185, 129, 0.1); padding: 1.5rem; border-radius: 8px; text-align: center; border: 1px solid rgba(16, 185, 129, 0.2);">
                <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 600;">Costo Total Estimado</div>
                <div style="font-size: 2rem; font-weight: 800; color: #10b981;">
                    <?php echo number_format($totalServicios + $totalRepuestos, 2, ',', '.'); ?>
                </div>
                <div style="font-size: 0.9rem; color: var(--text-muted);">Bolivianos (BOB)</div>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
