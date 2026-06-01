<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<?php
// Buffer temporal de formulario por si falla la validación
$formData = $_SESSION['form_client_data'] ?? [];
unset($_SESSION['form_client_data']);

$isEdit = ($client !== null);
$titulo = $isEdit ? 'Editar Cliente' : 'Nuevo Cliente';
$subtitulo = $isEdit ? 'Modifique la información de contacto y facturación del cliente.' : 'Registre la información básica, datos de contacto y facturación del nuevo cliente.';
$actionUrl = $isEdit ? BASE_URL . '/clientes/actualizar/' . $client['id'] : BASE_URL . '/clientes/guardar';

// Cargar valores para rellenar campos
$nombresVal = $isEdit ? $client['nombres'] : ($formData['nombres'] ?? '');
$apellidosVal = $isEdit ? $client['apellidos'] : ($formData['apellidos'] ?? '');
$documentoVal = $isEdit ? $client['documento'] : ($formData['documento'] ?? '');
$telefonoVal = $isEdit ? $client['telefono'] : ($formData['telefono'] ?? '');
$correoVal = $isEdit ? $client['correo'] : ($formData['correo'] ?? '');
$direccionVal = $isEdit ? $client['direccion'] : ($formData['direccion'] ?? '');
$observacionesVal = $isEdit ? $client['observaciones'] : ($formData['observaciones'] ?? '');

// Procesar datos de facturación
$nitVal = '';
$razonVal = '';
if ($isEdit) {
    $fact = json_decode($client['datos_facturacion'] ?? '', true);
    $nitVal = $fact['nit'] ?? '';
    $razonVal = $fact['razon_social'] ?? '';
} else {
    $nitVal = $formData['nit_facturacion'] ?? '';
    $razonVal = $formData['razon_social_facturacion'] ?? '';
}
?>

<div class="dashboard-header">
    <div>
        <h1><?php echo $titulo; ?></h1>
        <p style="color: var(--text-muted);"><?php echo $subtitulo; ?></p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/clientes" class="btn btn-secondary">
            Volver al listado
        </a>
    </div>
</div>

<!-- Alertas de error específicas del formulario -->
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h2 class="card-title"><?php echo $isEdit ? 'Modificar Registro' : 'Datos Personales'; ?></h2>
    
    <form action="<?php echo $actionUrl; ?>" method="POST">
        <!-- Token de seguridad CSRF -->
        <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\AuthHelper::getCsrfToken(); ?>">

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="nombres">Nombres *</label>
                <input class="form-control" type="text" name="nombres" id="nombres" placeholder="Ej. Carlos" value="<?php echo htmlspecialchars($nombresVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="apellidos">Apellidos *</label>
                <input class="form-control" type="text" name="apellidos" id="apellidos" placeholder="Ej. Mendoza Roca" value="<?php echo htmlspecialchars($apellidosVal); ?>" required>
            </div>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="documento">Documento de Identidad (CI / DNI / NIT) *</label>
                <input class="form-control" type="text" name="documento" id="documento" placeholder="Ej. 6543210" value="<?php echo htmlspecialchars($documentoVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono de Contacto *</label>
                <input class="form-control" type="text" name="telefono" id="telefono" placeholder="Ej. +591 76543210" value="<?php echo htmlspecialchars($telefonoVal); ?>" required>
            </div>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="correo">Correo Electrónico</label>
                <input class="form-control" type="email" name="correo" id="correo" placeholder="correo@ejemplo.com" value="<?php echo htmlspecialchars($correoVal); ?>">
            </div>

            <?php if ($isEdit): ?>
                <div class="form-group">
                    <label class="form-label" for="estado">Estado *</label>
                    <select class="form-control" name="estado" id="estado" required>
                        <option value="activo" <?php echo ($client['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo ($client['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label class="form-label" for="direccion">Dirección de Domicilio</label>
                    <input class="form-control" type="text" name="direccion" id="direccion" placeholder="Ej. Av. Las Américas Nro. 450" value="<?php echo htmlspecialchars($direccionVal); ?>">
                </div>
            <?php endif; ?>
        </div>

        <?php if ($isEdit): ?>
            <div class="form-group">
                <label class="form-label" for="direccion">Dirección de Domicilio</label>
                <input class="form-control" type="text" name="direccion" id="direccion" placeholder="Ej. Av. Las Américas Nro. 450" value="<?php echo htmlspecialchars($direccionVal); ?>">
            </div>
        <?php endif; ?>

        <!-- Sección de Datos de Facturación -->
        <div style="margin: 2rem 0 1rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
            <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem; color: var(--primary);">Datos de Facturación</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.2rem;">
                Defina la información para la emisión de facturas o notas de venta de este cliente.
            </p>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="nit_facturacion">NIT / C.I. de Facturación</label>
                <input class="form-control" type="text" name="nit_facturacion" id="nit_facturacion" placeholder="Ej. 1205632025" value="<?php echo htmlspecialchars($nitVal); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="razon_social_facturacion">Razón Social</label>
                <input class="form-control" type="text" name="razon_social_facturacion" id="razon_social_facturacion" placeholder="Ej. Carlos Mendoza R." value="<?php echo htmlspecialchars($razonVal); ?>">
            </div>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
            <label class="form-label" for="observaciones">Observaciones / Detalles Especiales</label>
            <textarea class="form-control" name="observaciones" id="observaciones" rows="3" placeholder="Información adicional del cliente (preferencias, restricciones, etc.)"><?php echo htmlspecialchars($observacionesVal); ?></textarea>
        </div>

        <div style="display: flex; gap: 1.5rem; margin-top: 2.5rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center; padding: 0.8rem; font-size: 1rem;">
                Guardar Cliente
            </button>
            <a href="<?php echo BASE_URL; ?>/clientes" class="btn btn-secondary" style="flex: 1; justify-content: center; padding: 0.8rem; font-size: 1rem;">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
