<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<div class="dashboard-header">
    <div>
        <h1>Órdenes de Trabajo (OT)</h1>
        <p style="color: var(--text-muted);">Gestión de ingresos, diagnósticos y reparaciones de vehículos en el taller.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/ordenes/crear" class="btn btn-primary">
            Nueva Orden de Trabajo
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

<!-- Buscador y Filtros -->
<div class="card" style="margin-bottom: 2rem; padding: 1.2rem;">
    <form action="<?php echo BASE_URL; ?>/ordenes" method="GET" style="display: flex; gap: 1rem; align-items: center; margin: 0;">
        <div class="form-group" style="flex: 2; margin: 0;">
            <input class="form-control" type="text" name="search" placeholder="Buscar por Código OT, Cliente o Placa..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        </div>
        <div class="form-group" style="flex: 1; margin: 0;">
            <select class="form-control" name="estado">
                <option value="">Todos los Estados</option>
                <option value="pendiente" <?php echo (($_GET['estado'] ?? '') === 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                <option value="en_diagnostico" <?php echo (($_GET['estado'] ?? '') === 'en_diagnostico') ? 'selected' : ''; ?>>En Diagnóstico</option>
                <option value="presupuestado" <?php echo (($_GET['estado'] ?? '') === 'presupuestado') ? 'selected' : ''; ?>>Presupuestado</option>
                <option value="en_progreso" <?php echo (($_GET['estado'] ?? '') === 'en_progreso') ? 'selected' : ''; ?>>En Progreso</option>
                <option value="terminado" <?php echo (($_GET['estado'] ?? '') === 'terminado') ? 'selected' : ''; ?>>Terminado</option>
                <option value="entregado" <?php echo (($_GET['estado'] ?? '') === 'entregado') ? 'selected' : ''; ?>>Entregado</option>
                <option value="cerrado" <?php echo (($_GET['estado'] ?? '') === 'cerrado') ? 'selected' : ''; ?>>Cerrado</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="margin: 0;">
            Buscar
        </button>
        <?php if (!empty($_GET['search']) || !empty($_GET['estado'])): ?>
            <a href="<?php echo BASE_URL; ?>/ordenes" class="btn btn-secondary" style="margin: 0;">
                Limpiar
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabla -->
<div class="card">
    <h2 class="card-title">Listado de OTs Activas e Históricas</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Código OT</th>
                    <th>Fecha Ingreso</th>
                    <th>Cliente / Vehículo</th>
                    <th>Falla Reportada</th>
                    <th>Mecánico</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Acciones / Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ordenes)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                            No se encontraron órdenes de trabajo registradas.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ordenes as $ot): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--primary); letter-spacing: 0.5px;"><?php echo htmlspecialchars($ot['codigo']); ?></strong>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">
                                    <?php echo date('d/m/Y H:i', strtotime($ot['fecha_ingreso'])); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($ot['cliente_nombres'] . ' ' . $ot['cliente_apellidos']); ?></strong><br>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">🚗 <?php echo htmlspecialchars($ot['auto_marca'] . ' - ' . $ot['auto_placa']); ?></span>
                            </td>
                            <td>
                                <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($ot['falla_reportada']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-secondary" style="background: rgba(255, 255, 255, 0.08); font-size: 0.8rem;">
                                    👨‍🔧 <?php echo htmlspecialchars($ot['mecanico_asignado']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    $pColor = $ot['prioridad'] === 'alta' ? '#ef4444' : ($ot['prioridad'] === 'baja' ? '#10b981' : '#f59e0b'); 
                                ?>
                                <span style="color: <?php echo $pColor; ?>; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($ot['prioridad']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    $eClass = 'badge-secondary';
                                    if (in_array($ot['estado'], ['terminado', 'entregado', 'cerrado'])) $eClass = 'badge-success';
                                    if ($ot['estado'] === 'anulado') $eClass = 'badge-danger';
                                    if (in_array($ot['estado'], ['en_diagnostico', 'en_progreso'])) $eClass = 'badge-primary'; // Could be different color
                                ?>
                                <span class="badge <?php echo $eClass; ?>" style="text-transform: uppercase; font-size: 0.75rem;">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $ot['estado'])); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <form action="<?php echo BASE_URL; ?>/ordenes/cambiar_estado/<?php echo $ot['id']; ?>" method="POST" style="margin: 0; display: flex; gap: 0.5rem;">
                                        <select name="estado" class="form-control" style="padding: 0.2rem 0.5rem; font-size: 0.8rem; height: auto;" onchange="this.form.submit()">
                                            <option value="" disabled selected>Cambiar...</option>
                                            <option value="pendiente">Pendiente</option>
                                            <option value="en_diagnostico">En Diagnóstico</option>
                                            <option value="presupuestado">Presupuestado</option>
                                            <option value="en_progreso">En Progreso</option>
                                            <option value="terminado">Terminado</option>
                                            <option value="entregado">Entregado</option>
                                            <option value="cerrado">Cerrado</option>
                                            <option value="anulado">Anulado</option>
                                        </select>
                                    </form>
                                    <a href="<?php echo BASE_URL; ?>/ordenes/detalles/<?php echo $ot['id']; ?>" class="btn btn-secondary btn-sm" title="Ver Detalles Operativos">Ver</a>
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
