<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<?php
$formData = $_SESSION['form_ot_data'] ?? [];
unset($_SESSION['form_ot_data']);

// Agrupar autos por cliente para el filtro dinámico con JS
$autosPorCliente = [];
foreach ($autos as $auto) {
    if ($auto['estado'] === 'activo') {
        $autosPorCliente[$auto['cliente_id']][] = [
            'id' => $auto['id'],
            'placa' => $auto['placa'],
            'marca' => $auto['marca'],
            'modelo' => $auto['modelo']
        ];
    }
}
$autosJson = json_encode($autosPorCliente);
?>

<div class="dashboard-header">
    <div>
        <h1>Nueva Orden de Trabajo (OT)</h1>
        <p style="color: var(--text-muted);">Inicie el proceso de recepción del vehículo, registre la falla y asigne un mecánico.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/ordenes" class="btn btn-secondary">
            Volver al listado
        </a>
    </div>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <h2 class="card-title">Datos de Recepción</h2>
    
    <form action="<?php echo BASE_URL; ?>/ordenes/guardar" method="POST">
        <!-- Token CSRF -->
        <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\AuthHelper::getCsrfToken(); ?>">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-group">
                <label class="form-label" for="cliente_id">Cliente / Propietario *</label>
                <select class="form-control" name="cliente_id" id="cliente_id" required onchange="actualizarAutos()">
                    <option value="">-- Seleccione el Cliente --</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?php echo $cliente['id']; ?>" <?php echo (($formData['cliente_id'] ?? '') == $cliente['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos'] . ' (' . $cliente['documento'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="auto_id">Vehículo a Ingresar *</label>
                <select class="form-control" name="auto_id" id="auto_id" required disabled>
                    <option value="">-- Seleccione primero al Cliente --</option>
                    <!-- Las opciones se generarán mediante JS -->
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-group">
                <label class="form-label" for="trabajador_id">Mecánico Asignado (Opcional por ahora)</label>
                <select class="form-control" name="trabajador_id" id="trabajador_id">
                    <option value="">-- Sin Asignar / Decidir Luego --</option>
                    <?php foreach ($trabajadores as $trabajador): ?>
                        <?php if ($trabajador['estado'] === 'activo' && $trabajador['disponibilidad'] !== 'ausente'): ?>
                            <option value="<?php echo $trabajador['id']; ?>" <?php echo (($formData['trabajador_id'] ?? '') == $trabajador['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($trabajador['nombres'] . ' ' . $trabajador['apellidos'] . ' - ' . $trabajador['nivel'] . ' (' . $trabajador['disponibilidad'] . ')'); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="prioridad">Nivel de Prioridad *</label>
                <select class="form-control" name="prioridad" id="prioridad" required>
                    <option value="baja" <?php echo (($formData['prioridad'] ?? '') === 'baja') ? 'selected' : ''; ?>>Baja (Mantenimiento Rutinario)</option>
                    <option value="media" <?php echo (($formData['prioridad'] ?? 'media') === 'media') ? 'selected' : ''; ?>>Media (Reparación General)</option>
                    <option value="alta" <?php echo (($formData['prioridad'] ?? '') === 'alta') ? 'selected' : ''; ?>>Alta (Urgente / Vehículo Parado)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="falla_reportada">Falla Reportada por el Cliente *</label>
            <textarea class="form-control" name="falla_reportada" id="falla_reportada" rows="4" placeholder="Describa los síntomas o el motivo del ingreso del vehículo..." required><?php echo htmlspecialchars($formData['falla_reportada'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="observaciones">Observaciones Visuales del Vehículo (Opcional)</label>
            <textarea class="form-control" name="observaciones" id="observaciones" rows="2" placeholder="Ej. Rayón en puerta derecha, no trae rueda de repuesto..."><?php echo htmlspecialchars($formData['observaciones'] ?? ''); ?></textarea>
        </div>

        <div style="display: flex; gap: 1.5rem; margin-top: 2.5rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center; padding: 0.8rem; font-size: 1rem;">
                Generar Orden de Trabajo
            </button>
            <a href="<?php echo BASE_URL; ?>/ordenes" class="btn btn-secondary" style="flex: 1; justify-content: center; padding: 0.8rem; font-size: 1rem;">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
    // Datos de los autos agrupados por ID de cliente, inyectados desde PHP
    const autosData = <?php echo $autosJson; ?>;
    
    function actualizarAutos() {
        const clienteSelect = document.getElementById('cliente_id');
        const autoSelect = document.getElementById('auto_id');
        const clienteId = clienteSelect.value;
        
        // Limpiar las opciones actuales
        autoSelect.innerHTML = '';
        
        if (!clienteId) {
            autoSelect.disabled = true;
            const option = document.createElement('option');
            option.value = '';
            option.text = '-- Seleccione primero al Cliente --';
            autoSelect.appendChild(option);
            return;
        }
        
        // Cargar autos del cliente
        const autosCliente = autosData[clienteId] || [];
        
        if (autosCliente.length === 0) {
            autoSelect.disabled = true;
            const option = document.createElement('option');
            option.value = '';
            option.text = '-- El cliente no tiene vehículos registrados --';
            autoSelect.appendChild(option);
        } else {
            autoSelect.disabled = false;
            const option = document.createElement('option');
            option.value = '';
            option.text = '-- Seleccione el Vehículo --';
            autoSelect.appendChild(option);
            
            autosCliente.forEach(auto => {
                const opt = document.createElement('option');
                opt.value = auto.id;
                opt.text = `${auto.placa} - ${auto.marca} ${auto.modelo}`;
                // Select si ya había uno en formData
                if (auto.id == '<?php echo $formData['auto_id'] ?? ''; ?>') {
                    opt.selected = true;
                }
                autoSelect.appendChild(opt);
            });
        }
    }
    
    // Ejecutar al cargar la página en caso de error de validación previo (re-populate)
    window.onload = function() {
        if (document.getElementById('cliente_id').value) {
            actualizarAutos();
        }
    };
</script>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
