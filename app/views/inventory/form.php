<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<?php
$formData = $_SESSION['form_inventory_data'] ?? [];
unset($_SESSION['form_inventory_data']);

$isEdit = ($item !== null);
$titulo = $isEdit ? 'Editar Artículo de Inventario' : 'Nuevo Artículo';
$subtitulo = $isEdit ? 'Modifique los costos, stock y ubicación del repuesto.' : 'Registre la información básica, SKU único, stock inicial y límites de abastecimiento.';
$actionUrl = $isEdit ? BASE_URL . '/inventario/actualizar/' . $item['id'] : BASE_URL . '/inventario/guardar';

// Cargar valores para rellenar campos
$skuVal = $isEdit ? $item['codigo_sku'] : ($formData['codigo_sku'] ?? '');
$nombreVal = $isEdit ? $item['nombre'] : ($formData['nombre'] ?? '');
$categoriaVal = $isEdit ? $item['categoria'] : ($formData['categoria'] ?? '');
$unidadVal = $isEdit ? $item['unidad'] : ($formData['unidad'] ?? 'unidades');
$costoVal = $isEdit ? $item['costo'] : ($formData['costo'] ?? '0.00');
$precioVal = $isEdit ? $item['precio'] : ($formData['precio'] ?? '0.00');
$stockVal = $isEdit ? $item['stock'] : ($formData['stock'] ?? '0');
$stockMinimoVal = $isEdit ? $item['stock_minimo'] : ($formData['stock_minimo'] ?? '5');
$ubicacionVal = $isEdit ? $item['ubicacion'] : ($formData['ubicacion'] ?? '');
$proveedorVal = $isEdit ? $item['proveedor'] : ($formData['proveedor'] ?? '');
?>

<div class="dashboard-header">
    <div>
        <h1><?php echo $titulo; ?></h1>
        <p style="color: var(--text-muted);"><?php echo $subtitulo; ?></p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/inventario" class="btn btn-secondary">
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
    <h2 class="card-title">Datos del Artículo</h2>
    
    <form action="<?php echo $actionUrl; ?>" method="POST">
        <!-- Token CSRF -->
        <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\AuthHelper::getCsrfToken(); ?>">

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="codigo_sku">Código SKU (Único) *</label>
                <input class="form-control" type="text" name="codigo_sku" id="codigo_sku" placeholder="Ej. FIL-103" style="text-transform: uppercase;" value="<?php echo htmlspecialchars($skuVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="nombre">Nombre / Repuesto *</label>
                <input class="form-control" type="text" name="nombre" id="nombre" placeholder="Ej. Filtro de Aceite Sintético" value="<?php echo htmlspecialchars($nombreVal); ?>" required>
            </div>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="categoria">Categoría *</label>
                <input class="form-control" type="text" name="categoria" id="categoria" placeholder="Ej. Filtros, Frenos, Lubricantes" value="<?php echo htmlspecialchars($categoriaVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="unidad">Unidad de Medida *</label>
                <select class="form-control" name="unidad" id="unidad" required>
                    <option value="unidades" <?php echo ($unidadVal === 'unidades') ? 'selected' : ''; ?>>Unidades</option>
                    <option value="litros" <?php echo ($unidadVal === 'litros') ? 'selected' : ''; ?>>Litros</option>
                    <option value="galon" <?php echo ($unidadVal === 'galon') ? 'selected' : ''; ?>>Galón</option>
                    <option value="juego" <?php echo ($unidadVal === 'juego') ? 'selected' : ''; ?>>Juego / Set</option>
                    <option value="metros" <?php echo ($unidadVal === 'metros') ? 'selected' : ''; ?>>Metros</option>
                </select>
            </div>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="costo">Costo Unitario (BOB) *</label>
                <input class="form-control" type="number" name="costo" id="costo" min="0" step="0.01" value="<?php echo htmlspecialchars($costoVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="precio">Precio de Venta (BOB) *</label>
                <input class="form-control" type="number" name="precio" id="precio" min="0" step="0.01" value="<?php echo htmlspecialchars($precioVal); ?>" required>
            </div>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="stock">Stock Actual *</label>
                <input class="form-control" type="number" name="stock" id="stock" min="0" value="<?php echo htmlspecialchars($stockVal); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="stock_minimo">Stock Mínimo (Alerta) *</label>
                <input class="form-control" type="number" name="stock_minimo" id="stock_minimo" min="0" value="<?php echo htmlspecialchars($stockMinimoVal); ?>" required>
            </div>
        </div>

        <div class="grid-2col">
            <div class="form-group">
                <label class="form-label" for="ubicacion">Ubicación Física</label>
                <input class="form-control" type="text" name="ubicacion" id="ubicacion" placeholder="Ej. Estante A - Sección 3" value="<?php echo htmlspecialchars($ubicacionVal); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="proveedor">Proveedor Referencial</label>
                <input class="form-control" type="text" name="proveedor" id="proveedor" placeholder="Ej. Importadora CarMax" value="<?php echo htmlspecialchars($proveedorVal); ?>">
            </div>
        </div>

        <?php if ($isEdit): ?>
            <div class="form-group">
                <label class="form-label" for="estado">Estado *</label>
                <select class="form-control" name="estado" id="estado" required>
                    <option value="activo" <?php echo ($item['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo ($item['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 1.5rem; margin-top: 2.5rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center; padding: 0.8rem; font-size: 1rem;">
                Guardar Artículo
            </button>
            <a href="<?php echo BASE_URL; ?>/inventario" class="btn btn-secondary" style="flex: 1; justify-content: center; padding: 0.8rem; font-size: 1rem;">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
