<?php
// Esta vista recibe $project, $materials del controlador
$projectId = $project['id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Compra - <?= h($project['nombre']) ?> - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-cart-plus"></i> Registrar Nueva Compra</h4>
                        <small>Proyecto: <?= h($project['nombre']) ?></small>
                    </div>
                    <div class="card-body">
                        <?php 
                        $flash = getFlashMessage();
                        if ($flash): 
                        ?>
                            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                                <?= h($flash['message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Nota:</strong> Al registrar la compra se actualizará automáticamente:
                            <ul class="mb-0 mt-2">
                                <li>El inventario disponible del material</li>
                                <li>El costo promedio del material en este proyecto</li>
                                <li>El total invertido</li>
                            </ul>
                        </div>

                        <form method="POST" action="<?= base_url('purchases.php') ?>" id="purchaseForm">
                            <input type="hidden" name="_action" value="store">
                            <input type="hidden" name="project_id" value="<?= $projectId ?>">

                            <div class="mb-3">
                                <label for="material_id" class="form-label">Material <span class="text-danger">*</span></label>
                                <select class="form-select" id="material_id" name="material_id" required>
                                    <option value="">Seleccionar material...</option>
                                    <?php foreach ($materials as $material): ?>
                                        <option value="<?= $material['id'] ?>" data-unidad="<?= h($material['unidad']) ?>">
                                            <?= h($material['sku']) ?> - <?= h($material['descripcion']) ?> (<?= h($material['unidad']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Solo se muestran materiales en los requerimientos del proyecto</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="qty_comprada" class="form-label">Cantidad Comprada <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="qty_comprada" name="qty_comprada" 
                                           required min="0.01" step="0.01" placeholder="0.00">
                                    <small class="form-text text-muted" id="unidad-hint">Unidad del material</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="costo_unitario" class="form-label">Costo Unitario <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-select" id="moneda" name="moneda" style="max-width: 100px;">
                                            <option value="MXN" selected>MXN</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                        </select>
                                        <input type="number" class="form-control" id="costo_unitario" name="costo_unitario" 
                                               required min="0" step="0.01" placeholder="0.00">
                                    </div>
                                    <small class="form-text text-muted">Precio por unidad</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="proveedor" class="form-label">Proveedor</label>
                                <input type="text" class="form-control" id="proveedor" name="proveedor" 
                                       maxlength="150" placeholder="Nombre del proveedor">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="numero_factura" class="form-label">Número de Factura</label>
                                    <input type="text" class="form-control" id="numero_factura" name="numero_factura" 
                                           maxlength="50" placeholder="Opcional">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="fecha_compra" class="form-label">Fecha de Compra <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="fecha_compra" name="fecha_compra" 
                                           required value="<?= date('Y-m-d') ?>"
                                           max="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6>Resumen:</h6>
                                    <p class="mb-1"><strong>Cantidad:</strong> <span id="summary-qty">0</span></p>
                                    <p class="mb-1"><strong>Costo Unitario:</strong> <span id="summary-unit">$0.00</span></p>
                                    <p class="mb-0"><strong>Total:</strong> <span id="summary-total" class="text-primary fs-5">$0.00</span></p>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?= base_url("purchases.php?project_id=$projectId") ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Registrar Compra
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Actualizar unidad cuando se selecciona material
        document.getElementById('material_id').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const unidad = selected.getAttribute('data-unidad') || '';
            document.getElementById('unidad-hint').textContent = unidad || 'Unidad del material';
            calculateTotal();
        });

        // Calcular total automáticamente
        function calculateTotal() {
            const qty = parseFloat(document.getElementById('qty_comprada').value) || 0;
            const costo = parseFloat(document.getElementById('costo_unitario').value) || 0;
            const moneda = document.getElementById('moneda').value;
            const total = qty * costo;

            document.getElementById('summary-qty').textContent = qty.toFixed(2);
            
            const symbols = { 'MXN': '$', 'USD': 'US$', 'EUR': '€' };
            const symbol = symbols[moneda] || '$';
            
            document.getElementById('summary-unit').textContent = symbol + costo.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('summary-total').textContent = symbol + total.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        document.getElementById('qty_comprada').addEventListener('input', calculateTotal);
        document.getElementById('costo_unitario').addEventListener('input', calculateTotal);
        document.getElementById('moneda').addEventListener('change', calculateTotal);
    </script>
</body>
</html>

