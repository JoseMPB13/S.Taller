<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<?php
$formData = $_SESSION['form_car_data'] ?? [];
unset($_SESSION['form_car_data']);

$isEdit = ($car !== null);
$titulo = $isEdit ? 'Editar Vehículo' : 'Nuevo Vehículo';
$subtitulo = $isEdit ? 'Modifique los detalles técnicos y el propietario del vehículo.' : 'Registre la información básica, chasis y kilometraje del vehículo.';
$actionUrl = $isEdit ? BASE_URL . '/autos/actualizar/' . $car['id'] : BASE_URL . '/autos/guardar';

// Cargar valores para rellenar campos
$clienteIdVal = $isEdit ? $car['cliente_id'] : ($formData['cliente_id'] ?? 0);
$placaVal = $isEdit ? $car['placa'] : ($formData['placa'] ?? '');
$vinVal = $isEdit ? $car['vin'] : ($formData['vin'] ?? '');
$marcaVal = $isEdit ? $car['marca'] : ($formData['marca'] ?? '');
$modeloVal = $isEdit ? $car['modelo'] : ($formData['modelo'] ?? '');
$anioVal = $isEdit ? $car['anio'] : ($formData['anio'] ?? date('Y'));
$colorVal = $isEdit ? $car['color'] : ($formData['color'] ?? '');
$kilometrajeVal = $isEdit ? $car['kilometraje'] : ($formData['kilometraje'] ?? 0);
$observacionesVal = $isEdit ? $car['observaciones'] : ($formData['observaciones'] ?? '');
?>

<div class="dashboard-header">
    <div>
        <h1><?php echo $titulo; ?></h1>
        <p style="color: var(--text-muted);"><?php echo $subtitulo; ?></p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/autos" class="btn btn-secondary">
            Volver al listado
        </a>
    </div>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h2 class="card-title">Detalles del Vehículo</h2>
    
    <form action="<?php echo $actionUrl; ?>" method="POST">
        <!-- Token CSRF -->
        <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\AuthHelper::getCsrfToken(); ?>">

        <!-- Selector de Cliente (Propietario) -->
        <div class="form-group">
            <label class="form-label" for="cliente_id">Propietario (Cliente vinculado) *</label>
            <select class="form-control" name="cliente_id" id="cliente_id" required>
                <option value="">-- Seleccionar Propietario --</option>
                <?php foreach ($clients as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ((int)$clienteIdVal === (int)$c['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['nombres'] . ' ' . $c['apellidos'] . ' (' . $c['documento'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="placa">Placa *</label>
                <input class="form-control" type="text" name="placa" id="placa" placeholder="Ej. 1234ABC" style="text-transform: uppercase;" value="<?php echo htmlspecialchars($placaVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="vin">Número de Chasis / VIN</label>
                <input class="form-control" type="text" name="vin" id="vin" placeholder="Ej. 9FTZR..." style="text-transform: uppercase;" value="<?php echo htmlspecialchars($vinVal); ?>">
            </div>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="marca">Marca *</label>
                <input class="form-control" type="text" name="marca" id="marca" placeholder="Ej. Toyota" value="<?php echo htmlspecialchars($marcaVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="modelo">Modelo *</label>
                <input class="form-control" type="text" name="modelo" id="modelo" placeholder="Ej. Corolla" value="<?php echo htmlspecialchars($modeloVal); ?>" required>
            </div>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="anio">Año *</label>
                <input class="form-control" type="number" name="anio" id="anio" min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo htmlspecialchars($anioVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="color">Color</label>
                <input class="form-control" type="text" name="color" id="color" placeholder="Ej. Blanco" value="<?php echo htmlspecialchars($colorVal); ?>">
            </div>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="kilometraje">Kilometraje actual *</label>
                <input class="form-control" type="number" name="kilometraje" id="kilometraje" min="0" value="<?php echo htmlspecialchars($kilometrajeVal); ?>" required>
            </div>

            <?php if ($isEdit): ?>
                <div class="form-group">
                    <label class="form-label" for="estado">Estado *</label>
                    <select class="form-control" name="estado" id="estado" required>
                        <option value="activo" <?php echo ($car['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo ($car['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="observaciones">Observaciones adicionales</label>
            <textarea class="form-control" name="observaciones" id="observaciones" rows="3" placeholder="Detalles mecánicos específicos, golpes, rayones preexistentes..."><?php echo htmlspecialchars($observacionesVal); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Guardar Vehículo
            </button>
            <a href="<?php echo BASE_URL; ?>/autos" class="btn btn-secondary">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
