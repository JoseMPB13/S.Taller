<?php include ROOT_PATH . '/app/views/layout/header.php'; ?>

<!-- Estilos específicos para impresión -->
<style>
    .comprobante-container {
        max-width: 800px;
        margin: 0 auto;
        background: #fff;
        color: #333;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .print-only {
        display: none;
    }

    @media print {
        /* Ocultar UI de navegación */
        .navbar, .btn, .btn-custom-download, .dashboard-header, .alert, footer, .sidebar {
            display: none !important;
        }
        
        body {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .comprobante-container {
            box-shadow: none;
            max-width: 100%;
            padding: 0;
            color: #000;
        }

        .print-only {
            display: block;
        }
    }

    .comp-header {
        display: flex;
        justify-content: space-between;
        border-bottom: 2px solid #333;
        padding-bottom: 1rem;
        margin-bottom: 2rem;
    }

    .comp-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        color: #000;
    }

    .comp-section {
        margin-bottom: 1.5rem;
    }
    
    .comp-section h4 {
        margin-top: 0;
        margin-bottom: 0.5rem;
        border-bottom: 1px solid #ccc;
        padding-bottom: 0.2rem;
        color: #555;
    }

    .comp-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1.5rem;
    }
    .comp-table th, .comp-table td {
        border: 1px solid #ddd;
        padding: 0.5rem;
        text-align: left;
    }
    .comp-table th {
        background-color: #f9f9f9;
        font-weight: 600;
        color: #333;
    }
    .comp-table .text-right {
        text-align: right;
    }

    .comp-totals {
        width: 50%;
        float: right;
    }

    .comp-signatures {
        margin-top: 5rem;
        display: flex;
        justify-content: space-around;
        text-align: center;
        clear: both;
    }
    .signature-line {
        border-top: 1px solid #000;
        width: 200px;
        padding-top: 0.5rem;
    }
    
    /* Botón de descarga premium */
    .btn-custom-download {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff !important;
        font-weight: 600;
        padding: 0.55rem 1.2rem;
        border-radius: 6px;
        text-decoration: none;
        box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2), 0 2px 4px -1px rgba(16, 185, 129, 0.1);
        transition: all 0.2s ease-in-out;
        border: none;
        cursor: pointer;
        font-family: inherit;
        font-size: 0.9rem;
    }
    .btn-custom-download:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-1px);
        box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3), 0 4px 6px -2px rgba(16, 185, 129, 0.1);
    }
    .btn-custom-download:active {
        transform: translateY(0);
    }
</style>

<div class="comprobante-actions">
    <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir Comprobante</button>
    <a href="<?php echo BASE_URL; ?>/ordenes/descargar_pdf/<?php echo $ot['id']; ?>" class="btn-custom-download">📄 Descargar PDF</a>
    <a href="<?php echo BASE_URL; ?>/ordenes/detalles/<?php echo $ot['id']; ?>" class="btn btn-secondary">Volver a la OT</a>
</div>

<div class="comprobante-container">
    <div class="comp-header">
        <div>
            <h1 class="comp-title">S.TALLER</h1>
            <div><strong>Servicios Mecánicos Integrales</strong></div>
            <div>Av. Principal #123, Santa Cruz, Bolivia</div>
            <div>Tel: +591 71234567 | NIT: 1029384756</div>
        </div>
        <div style="text-align: right;">
            <h2 style="margin:0; color:#444;">COMPROBANTE DE LIQUIDACIÓN</h2>
            <div style="font-size: 1.2rem; font-weight: bold; margin-top: 0.5rem;"><?php echo htmlspecialchars($ot['codigo']); ?></div>
            <div><strong>Fecha Ingreso:</strong> <?php echo date('d/m/Y', strtotime($ot['fecha_ingreso'])); ?></div>
            <div><strong>Fecha Emisión:</strong> <?php echo date('d/m/Y'); ?></div>
            <div><strong>Estado:</strong> <span style="text-transform: uppercase;"><?php echo htmlspecialchars($ot['estado']); ?></span></div>
        </div>
    </div>

    <div class="comp-section comp-info-grid">
        <div style="flex: 1;">
            <h4>Datos del Cliente</h4>
            <div><strong>Nombre:</strong> <?php echo htmlspecialchars($ot['cliente_nombres'] . ' ' . $ot['cliente_apellidos']); ?></div>
            <div><strong>Documento (CI/NIT):</strong> <?php echo htmlspecialchars($ot['cliente_documento'] ?? 'S/D'); ?></div>
        </div>
        <div style="flex: 1;">
            <h4>Datos del Vehículo</h4>
            <div><strong>Vehículo:</strong> <?php echo htmlspecialchars($ot['auto_marca'] . ' ' . $ot['auto_modelo']); ?></div>
            <div><strong>Placa:</strong> <?php echo htmlspecialchars($ot['auto_placa']); ?></div>
        </div>
    </div>

    <div class="comp-section">
        <h4>1. Repuestos e Insumos</h4>
        <div class="table-responsive">
            <table class="comp-table">
            <thead>
                <tr>
                    <th>Cant.</th>
                    <th>Descripción</th>
                    <th class="text-right">Precio Unit. (BOB)</th>
                    <th class="text-right">Subtotal (BOB)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($repuestos)): ?>
                    <tr><td colspan="4" style="text-align: center;">No se registraron repuestos.</td></tr>
                <?php else: ?>
                    <?php foreach ($repuestos as $rp): ?>
                        <tr>
                            <td><?php echo $rp['cantidad']; ?></td>
                            <td><?php echo htmlspecialchars($rp['codigo_sku'] . ' - ' . $rp['repuesto_nombre']); ?></td>
                            <td class="text-right"><?php echo number_format($rp['precio_unitario'], 2, ',', '.'); ?></td>
                            <td class="text-right"><?php echo number_format($rp['cantidad'] * $rp['precio_unitario'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="comp-section">
        <h4>2. Servicios y Mano de Obra</h4>
        <div class="table-responsive">
            <table class="comp-table">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th class="text-right">Precio (BOB)</th>
                        <th class="text-right">Descuento (BOB)</th>
                        <th class="text-right">Subtotal (BOB)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($servicios)): ?>
                        <tr><td colspan="4" style="text-align: center;">No se registraron servicios.</td></tr>
                    <?php else: ?>
                        <?php foreach ($servicios as $sv): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sv['nombre_servicio']); ?></td>
                                <td class="text-right"><?php echo number_format($sv['precio_aplicado'], 2, ',', '.'); ?></td>
                                <td class="text-right"><?php echo number_format($sv['descuento_aplicado'], 2, ',', '.'); ?></td>
                                <td class="text-right"><?php echo number_format($sv['precio_aplicado'] - $sv['descuento_aplicado'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="comp-totals-responsive">
        <table class="comp-table" style="border: none;">
            <tr>
                <td style="border: none; text-align: right; font-weight: bold;">SUBTOTAL:</td>
                <td style="border: none; text-align: right; width: 120px;"><?php echo number_format($subtotal, 2, ',', '.'); ?> BOB</td>
            </tr>
            <tr>
                <td style="border: none; text-align: right; font-weight: bold;">MANO DE OBRA (MANUAL):</td>
                <td style="border: none; text-align: right;"><?php echo number_format($costo_mano_obra, 2, ',', '.'); ?> BOB</td>
            </tr>
            <tr>
                <td style="border: none; text-align: right; font-size: 0.85rem; color: #666;">(Subtotal Neto Sin IVA):</td>
                <td style="border: none; text-align: right; font-size: 0.85rem; color: #666;"><?php echo number_format($subtotal_sin_iva, 2, ',', '.'); ?> BOB</td>
            </tr>
            <tr>
                <td style="border: none; text-align: right; font-size: 0.85rem; color: #666;">(IVA 13% Incluido):</td>
                <td style="border: none; text-align: right; font-size: 0.85rem; color: #666;"><?php echo number_format($monto_iva, 2, ',', '.'); ?> BOB</td>
            </tr>
            <tr>
                <td style="border-top: 2px solid #333; border-bottom: none; border-left: none; border-right: none; text-align: right; font-weight: bold; font-size: 1.2rem;">TOTAL A PAGAR:</td>
                <td style="border-top: 2px solid #333; border-bottom: none; border-left: none; border-right: none; text-align: right; font-weight: bold; font-size: 1.2rem;"><?php echo number_format($total_general, 2, ',', '.'); ?> BOB</td>
            </tr>
        </table>
    </div>

    <div class="comp-signatures-responsive">
        <div>
            <div class="signature-line">Conformidad del Cliente</div>
            <div style="font-size: 0.8rem; margin-top: 0.2rem;"><?php echo htmlspecialchars($ot['cliente_documento'] ?? ''); ?></div>
        </div>
        <div>
            <div class="signature-line">Autorizado Taller</div>
            <div style="font-size: 0.8rem; margin-top: 0.2rem;">Firma y Sello</div>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 3rem; font-size: 0.8rem; color: #777;">
        Gracias por su preferencia. Documento de control interno.
    </div>
</div>

<?php include ROOT_PATH . '/app/views/layout/footer.php'; ?>
