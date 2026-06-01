<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<?php
$formData = $_SESSION['form_worker_data'] ?? [];
unset($_SESSION['form_worker_data']);

$isEdit = ($worker !== null);
$titulo = $isEdit ? 'Editar Trabajador' : 'Nuevo Trabajador';
$subtitulo = $isEdit ? 'Modifique la información técnica y de contacto del trabajador.' : 'Registre la información básica y nivel técnico del nuevo mecánico.';
$actionUrl = $isEdit ? BASE_URL . '/trabajadores/actualizar/' . $worker['id'] : BASE_URL . '/trabajadores/guardar';

// Cargar valores para rellenar campos
$nombresVal = $isEdit ? $worker['nombres'] : ($formData['nombres'] ?? '');
$apellidosVal = $isEdit ? $worker['apellidos'] : ($formData['apellidos'] ?? '');
$documentoVal = $isEdit ? $worker['documento'] : ($formData['documento'] ?? '');
$especialidadesVal = $isEdit ? $worker['especialidades'] : ($formData['especialidades'] ?? '');
$nivelVal = $isEdit ? $worker['nivel'] : ($formData['nivel'] ?? 'Junior');
$contactoVal = $isEdit ? $worker['contacto'] : ($formData['contacto'] ?? '');
$disponibilidadVal = $isEdit ? $worker['disponibilidad'] : ($formData['disponibilidad'] ?? 'disponible');
?>

<div class="dashboard-header">
    <div>
        <h1><?php echo $titulo; ?></h1>
        <p style="color: var(--text-muted);"><?php echo $subtitulo; ?></p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/trabajadores" class="btn btn-secondary">
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
    <h2 class="card-title">Datos del Trabajador</h2>
    
    <form action="<?php echo $actionUrl; ?>" method="POST">
        <!-- Token CSRF -->
        <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\AuthHelper::getCsrfToken(); ?>">

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="nombres">Nombres *</label>
                <input class="form-control" type="text" name="nombres" id="nombres" placeholder="Ej. Juan" value="<?php echo htmlspecialchars($nombresVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="apellidos">Apellidos *</label>
                <input class="form-control" type="text" name="apellidos" id="apellidos" placeholder="Ej. Pérez Gómez" value="<?php echo htmlspecialchars($apellidosVal); ?>" required>
            </div>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="documento">Documento de Identidad (CI / DNI) *</label>
                <input class="form-control" type="text" name="documento" id="documento" placeholder="Ej. 10203040" value="<?php echo htmlspecialchars($documentoVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="contacto">Contacto (Teléfono o Correo)</label>
                <input class="form-control" type="text" name="contacto" id="contacto" placeholder="Ej. +591 76543210 o juan@taller.com" value="<?php echo htmlspecialchars($contactoVal); ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="especialidades">Especialidades (separadas por comas) *</label>
            <input class="form-control" type="text" name="especialidades" id="especialidades" placeholder="Ej. Mecánica de motores, Diagnóstico electrónico, Frenos ABS" value="<?php echo htmlspecialchars($especialidadesVal); ?>" required>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="nivel">Nivel Técnico *</label>
                <select class="form-control" name="nivel" id="nivel" required>
                    <option value="Junior" <?php echo ($nivelVal === 'Junior') ? 'selected' : ''; ?>>Junior</option>
                    <option value="Semi-Senior" <?php echo ($nivelVal === 'Semi-Senior') ? 'selected' : ''; ?>>Semi-Senior</option>
                    <option value="Senior" <?php echo ($nivelVal === 'Senior') ? 'selected' : ''; ?>>Senior</option>
                    <option value="Master" <?php echo ($nivelVal === 'Master') ? 'selected' : ''; ?>>Master</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="disponibilidad">Disponibilidad Inicial *</label>
                <select class="form-control" name="disponibilidad" id="disponibilidad" required>
                    <option value="disponible" <?php echo ($disponibilidadVal === 'disponible') ? 'selected' : ''; ?>>Disponible</option>
                    <option value="ocupado" <?php echo ($disponibilidadVal === 'ocupado') ? 'selected' : ''; ?>>Ocupado</option>
                    <option value="ausente" <?php echo ($disponibilidadVal === 'ausente') ? 'selected' : ''; ?>>Ausente</option>
                </select>
            </div>
        </div>

        <?php if ($isEdit): ?>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label class="form-label" for="estado">Estado *</label>
                <select class="form-control" name="estado" id="estado" required>
                    <option value="activo" <?php echo ($worker['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo ($worker['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 1.5rem; margin-top: 2.5rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center; padding: 0.8rem; font-size: 1rem;">
                Guardar Trabajador
            </button>
            <a href="<?php echo BASE_URL; ?>/trabajadores" class="btn btn-secondary" style="flex: 1; justify-content: center; padding: 0.8rem; font-size: 1rem;">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
