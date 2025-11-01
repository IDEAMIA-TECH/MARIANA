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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container .select2-selection--single { height: 38px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    </style>
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

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="card border-primary h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-2">Total compras en USD</h6>
                                        <p class="display-6 mb-0 text-primary" id="top-total-usd">US$0.00</p>
                                        <small class="text-muted">Suma de todos los ítems capturados en USD</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-success h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-2">Total global en MXN</h6>
                                        <p class="display-6 mb-0 text-success" id="top-total-mxn">$0.00</p>
                                        <small class="text-muted">Incluye MXN + USD convertidos con su tipo de cambio</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="<?= base_url('purchases.php') ?>" id="purchaseForm">
                            <input type="hidden" name="_action" value="store">
                            <input type="hidden" name="project_id" value="<?= $projectId ?>">

                            <div class="mb-3">
                                <label class="form-label">Productos de la compra <span class="text-danger">*</span></label>
                                <div id="items-container">
                                    <div class="purchase-item row g-2 mb-2" data-index="0">
                                        <div class="col-md-5">
                                            <label class="form-label">Material</label>
                                            <select class="form-select material-select" name="material_id[]" required>
                                                <option value="">Seleccionar material...</option>
                                                <?php foreach ($materials as $material): ?>
                                                    <option value="<?= $material['id'] ?>" data-unidad="<?= h($material['unidad']) ?>">
                                                        <?= h($material['sku']) ?> - <?= h($material['descripcion']) ?> (<?= h($material['unidad']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted unidad-hint">Unidad del material</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Cantidad</label>
                                            <input type="number" class="form-control qty-input" name="qty_comprada[]" required min="0.01" step="0.01" placeholder="0.00">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Costo Unitario</label>
                                            <div class="input-group">
                                                <select class="form-select moneda-select" name="moneda[]" style="max-width: 100px;">
                                                    <option value="MXN" selected>MXN</option>
                                                    <option value="USD">USD</option>
                                                    <option value="EUR">EUR</option>
                                                </select>
                                                <input type="number" class="form-control costo-input" name="costo_unitario[]" required min="0" step="0.0001" placeholder="0.00">
                                            </div>
                                            <div class="mt-1 d-none tc-wrapper">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">TC</span>
                                                    <input type="number" class="form-control tc-input" name="tipo_cambio[]" min="0.0001" step="0.0001" placeholder="Tipo de cambio (MXN/USD)">
                                                </div>
                                                <small class="form-text text-muted">Se usa para convertir USD a MXN en el resumen</small>
                                            </div>
                                            <small class="form-text text-muted">Precio por unidad</small>
                                        </div>
                                        <div class="col-md-1 d-grid">
                                            <button type="button" class="btn btn-outline-danger remove-item" title="Eliminar fila">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-item">
                                    <i class="bi bi-plus-circle"></i> Agregar producto
                                </button>
                                <small class="form-text text-muted d-block mt-1">Solo se muestran materiales en los requerimientos del proyecto</small>
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
                                    <p class="mb-1"><strong>Items:</strong> <span id="summary-items">1</span></p>
                                    <p class="mb-0"><strong>Total estimado (por moneda):</strong></p>
                                    <ul id="summary-totals" class="mb-0"></ul>
                                    <hr>
                                    <p class="mb-1"><strong>Total USD (solo ítems en USD):</strong> <span id="summary-total-usd">US$0.00</span></p>
                                    <p class="mb-0"><strong>Total MXN (USD convertidos con TC):</strong> <span id="summary-total-mxn">$0.00</span></p>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function updateUnidadHint(row) {
            const select = row.querySelector('.material-select');
            const hint = row.querySelector('.unidad-hint');
            const selected = select.options[select.selectedIndex];
            const unidad = selected ? (selected.getAttribute('data-unidad') || '') : '';
            hint.textContent = unidad || 'Unidad del material';
        }

        function recalcSummary() {
            const rows = document.querySelectorAll('#items-container .purchase-item');
            const totalsByCurrency = {};
            let totalUSD = 0; // solo ítems en USD
            let totalMXN = 0; // incluye MXN + USD convertidos con TC
            rows.forEach(row => {
                const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
                const costo = parseFloat(row.querySelector('.costo-input').value) || 0;
                const moneda = row.querySelector('.moneda-select').value || 'MXN';
                const subtotal = qty * costo;
                totalsByCurrency[moneda] = (totalsByCurrency[moneda] || 0) + subtotal;

                if (moneda === 'USD') {
                    const tcInput = row.querySelector('.tc-input');
                    const tc = tcInput && parseFloat(tcInput.value) > 0 ? parseFloat(tcInput.value) : 0;
                    totalUSD += subtotal;
                    if (tc > 0) {
                        totalMXN += subtotal * tc;
                    }
                } else if (moneda === 'MXN') {
                    totalMXN += subtotal;
                }
            });

            document.getElementById('summary-items').textContent = rows.length.toString();
            const list = document.getElementById('summary-totals');
            list.innerHTML = '';
            const symbols = { 'MXN': '$', 'USD': 'US$', 'EUR': '€' };
            Object.keys(totalsByCurrency).forEach(moneda => {
                const li = document.createElement('li');
                const symbol = symbols[moneda] || '$';
                li.textContent = moneda + ': ' + symbol + totalsByCurrency[moneda].toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                list.appendChild(li);
            });

            document.getElementById('summary-total-usd').textContent = 'US$' + totalUSD.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('summary-total-mxn').textContent = '$' + totalMXN.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            // actualizar tarjetas superiores
            const topUsd = document.getElementById('top-total-usd');
            const topMxn = document.getElementById('top-total-mxn');
            if (topUsd) topUsd.textContent = 'US$' + totalUSD.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (topMxn) topMxn.textContent = '$' + totalMXN.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function wireRowEvents(row) {
            row.querySelector('.material-select').addEventListener('change', () => { updateUnidadHint(row); });
            row.querySelector('.qty-input').addEventListener('input', recalcSummary);
            row.querySelector('.costo-input').addEventListener('input', recalcSummary);
            row.querySelector('.moneda-select').addEventListener('change', () => {
                const moneda = row.querySelector('.moneda-select').value;
                const tcWrap = row.querySelector('.tc-wrapper');
                if (moneda === 'USD') {
                    tcWrap.classList.remove('d-none');
                } else {
                    tcWrap.classList.add('d-none');
                }
                recalcSummary();
            });
            const tc = row.querySelector('.tc-input');
            if (tc) tc.addEventListener('input', recalcSummary);
            row.querySelector('.remove-item').addEventListener('click', () => {
                const container = document.getElementById('items-container');
                if (container.querySelectorAll('.purchase-item').length > 1) {
                    row.remove();
                    recalcSummary();
                }
            });
            updateUnidadHint(row);
            // activar select2 en el select del material
            if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                const $select = jQuery(row).find('.material-select');
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                $select.select2({ placeholder: 'Buscar material...', width: '100%' });
            }
        }

        document.getElementById('add-item').addEventListener('click', () => {
            const container = document.getElementById('items-container');
            const template = container.querySelector('.purchase-item');
            const clone = template.cloneNode(true);
            // reset inputs
            clone.querySelector('.material-select').selectedIndex = 0;
            clone.querySelector('.qty-input').value = '';
            clone.querySelector('.costo-input').value = '';
            clone.querySelector('.moneda-select').value = 'MXN';
            const tcWrap = clone.querySelector('.tc-wrapper');
            const tcInput = clone.querySelector('.tc-input');
            if (tcWrap) tcWrap.classList.add('d-none');
            if (tcInput) tcInput.value = '';
            wireRowEvents(clone);
            container.appendChild(clone);
            recalcSummary();
        });

        // Inicializar primera fila
        wireRowEvents(document.querySelector('#items-container .purchase-item'));
        recalcSummary();
    </script>
</body>
</html>

