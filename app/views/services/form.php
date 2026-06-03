<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<?php
$formData = $_SESSION['form_service_data'] ?? [];
unset($_SESSION['form_service_data']);

$isEdit = ($service !== null);
$titulo = $isEdit ? 'Editar Servicio' : 'Nuevo Servicio';
$subtitulo = $isEdit ? 'Modifique las tarifas y tiempos estimados del servicio.' : 'Registre la información básica y precio base del nuevo servicio técnico.';
$actionUrl = $isEdit ? BASE_URL . '/servicios/actualizar/' . $service['id'] : BASE_URL . '/servicios/guardar';

// Cargar valores para rellenar campos
$nombreVal = $isEdit ? $service['nombre_servicio'] : ($formData['nombre_servicio'] ?? '');
$descripcionVal = $isEdit ? $service['descripcion'] : ($formData['descripcion'] ?? '');
$tiempoVal = $isEdit ? $service['tiempo_estimado'] : ($formData['tiempo_estimado'] ?? '60');
$precioBaseVal = $isEdit ? $service['precio_base'] : ($formData['precio_base'] ?? '0.00');


?>

<div class="dashboard-header">
    <div>
        <h1><?php echo $titulo; ?></h1>
        <p style="color: var(--text-muted);"><?php echo $subtitulo; ?></p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/servicios" class="btn btn-secondary">
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
    <h2 class="card-title">Datos del Servicio</h2>
    
    <form action="<?php echo $actionUrl; ?>" method="POST">
        <!-- Token CSRF -->
        <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\AuthHelper::getCsrfToken(); ?>">

        <div class="form-group">
            <label class="form-label" for="nombre_servicio">Nombre del Servicio *</label>
            <input class="form-control" type="text" name="nombre_servicio" id="nombre_servicio" placeholder="Ej. Alineación y Balanceo Computarizado" value="<?php echo htmlspecialchars($nombreVal); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="descripcion">Descripción / Alcance</label>
            <textarea class="form-control" name="descripcion" id="descripcion" rows="3" placeholder="Detalle qué incluye el servicio técnico (piezas de diagnóstico, limpieza)..."><?php echo htmlspecialchars($descripcionVal); ?></textarea>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="tiempo_estimado">Tiempo Estimado (minutos) *</label>
                <input class="form-control" type="number" name="tiempo_estimado" id="tiempo_estimado" min="1" value="<?php echo htmlspecialchars($tiempoVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="precio_base">Precio de Mano de Obra Base (BOB) *</label>
                <input class="form-control" type="number" name="precio_base" id="precio_base" min="0" step="0.01" value="<?php echo htmlspecialchars($precioBaseVal); ?>" required>
            </div>
        </div>



        <?php if ($isEdit): ?>
            <div class="form-group">
                <label class="form-label" for="estado">Estado *</label>
                <select class="form-control" name="estado" id="estado" required>
                    <option value="activo" <?php echo ($service['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo ($service['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Guardar Servicio
            </button>
            <a href="<?php echo BASE_URL; ?>/servicios" class="btn btn-secondary">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
