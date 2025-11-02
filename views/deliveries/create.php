<?php
// Esta vista recibe $project, $materials del controlador
$projectId = $project['id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Entrega - <?= h($project['nombre']) ?> - <?= h(APP_NAME) ?></title>
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
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="bi bi-truck"></i> Registrar Nueva Entrega</h4>
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
                            <strong>Nota:</strong> Solo se muestran materiales con inventario disponible. Al registrar la entrega se actualizará automáticamente el inventario.
                        </div>

                        <form method="POST" action="<?= base_url('deliveries.php') ?>" id="deliveryForm">
                            <input type="hidden" name="_action" value="store">
                            <input type="hidden" name="project_id" value="<?= $projectId ?>">

                            <div class="mb-3">
                                <label class="form-label">Productos a entregar <span class="text-danger">*</span></label>
                                <div id="items-container">
                                    <div class="delivery-item row g-2 mb-2" data-index="0">
                                        <div class="col-md-5">
                                            <label class="form-label">Material</label>
                                            <select class="form-select material-select" name="material_id[]" required>
                                                <option value="">Buscar por código o nombre...</option>
                                                <?php foreach ($materials as $material): ?>
                                                    <option value="<?= $material['id'] ?>" 
                                                            data-disponible="<?= $material['qty_disponible'] ?>"
                                                            data-unidad="<?= h($material['unidad']) ?>"
                                                            data-sku="<?= h($material['sku']) ?>">
                                                        <?= h($material['sku']) ?> - <?= h($material['descripcion']) ?> 
                                                        (Disponible: <?= number_format($material['qty_disponible'], 2) ?> <?= h($material['unidad']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted disponible-hint">Selecciona un material</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Disponible</label>
                                            <input type="text" class="form-control disponible-show" readonly style="background-color: #f8f9fa;">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Cantidad a Entregar</label>
                                            <input type="number" class="form-control qty-input" name="qty_entregada[]" required min="0.01" step="0.0001" placeholder="0.00">
                                            <div class="qty-error text-danger" style="display: none; font-size: 0.875rem;"></div>
                                        </div>
                                        <div class="col-md-1 d-grid">
                                            <button type="button" class="btn btn-outline-danger remove-item" title="Eliminar fila">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-success btn-sm" id="add-item">
                                    <i class="bi bi-plus-circle"></i> Agregar producto
                                </button>
                                <small class="form-text text-muted d-block mt-1">Solo materiales con inventario disponible</small>
                            </div>

                            <div class="mb-3">
                                <label for="entregado_a" class="form-label">Entregado A <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="entregado_a" name="entregado_a" 
                                       required maxlength="120" 
                                       placeholder="Ej: Obra Zona A, Responsable de Obra, etc.">
                                <small class="form-text text-muted">Especifica a quién o dónde se entregó el material</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fecha_entrega" class="form-label">Fecha de Entrega <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" 
                                           required value="<?= date('Y-m-d') ?>"
                                           max="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="comentarios" class="form-label">Comentarios</label>
                                <textarea class="form-control" id="comentarios" name="comentarios" 
                                          rows="3" placeholder="Observaciones adicionales (opcional)"></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?= base_url("deliveries.php?project_id=$projectId") ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Registrar Entrega(s)
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
        function updateDisponibleHint(row) {
            const select = row.querySelector('.material-select');
            const hint = row.querySelector('.disponible-hint');
            const show = row.querySelector('.disponible-show');
            const qtyInput = row.querySelector('.qty-input');
            const qtyError = row.querySelector('.qty-error');
            
            if (select && window.jQuery) {
                const selected = jQuery(select).find(':selected');
                if (selected.val()) {
                    const disponible = parseFloat(selected.attr('data-disponible')) || 0;
                    const unidad = selected.attr('data-unidad') || '';
                    const sku = selected.attr('data-sku') || '';
                    
                    hint.textContent = sku ? `SKU: ${sku} | Disponible: ${disponible.toFixed(2)} ${unidad}` : `Disponible: ${disponible.toFixed(2)} ${unidad}`;
                    show.value = disponible.toFixed(2) + ' ' + unidad;
                    qtyInput.setAttribute('max', disponible);
                    qtyInput.setAttribute('data-disponible', disponible);
                    qtyError.style.display = 'none';
                } else {
                    hint.textContent = 'Selecciona un material';
                    show.value = '';
                    qtyInput.removeAttribute('max');
                    qtyInput.removeAttribute('data-disponible');
                }
            }
        }

        function validateQty(row) {
            const qtyInput = row.querySelector('.qty-input');
            const qtyError = row.querySelector('.qty-error');
            const max = parseFloat(qtyInput.getAttribute('max')) || 0;
            const value = parseFloat(qtyInput.value) || 0;
            
            if (value > max && max > 0) {
                qtyError.textContent = `Máximo: ${max.toFixed(2)}`;
                qtyError.style.display = 'block';
                qtyInput.setCustomValidity(`Máximo: ${max.toFixed(2)}`);
            } else {
                qtyError.style.display = 'none';
                qtyInput.setCustomValidity('');
            }
        }

        function wireRowEvents(row) {
            const select = row.querySelector('.material-select');
            const qtyInput = row.querySelector('.qty-input');
            
            // Inicializar Select2
            if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                const $select = jQuery(select);
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                $select.select2({
                    placeholder: 'Buscar por código o nombre...',
                    width: '100%'
                }).on('change', () => { updateDisponibleHint(row); });
            }
            
            qtyInput.addEventListener('input', () => validateQty(row));
            
            row.querySelector('.remove-item').addEventListener('click', () => {
                const container = document.getElementById('items-container');
                if (container.querySelectorAll('.delivery-item').length > 1) {
                    // Destruir Select2 antes de remover
                    if (window.jQuery && jQuery(select).data('select2')) {
                        jQuery(select).select2('destroy');
                    }
                    row.remove();
                }
            });
            
            updateDisponibleHint(row);
        }

        document.getElementById('add-item').addEventListener('click', () => {
            const container = document.getElementById('items-container');
            const template = container.querySelector('.delivery-item');
            const clone = template.cloneNode(true);
            
            // Reset inputs
            clone.querySelector('.material-select').selectedIndex = 0;
            clone.querySelector('.disponible-show').value = '';
            clone.querySelector('.qty-input').value = '';
            clone.querySelector('.qty-input').removeAttribute('max');
            clone.querySelector('.disponible-hint').textContent = 'Selecciona un material';
            clone.querySelector('.qty-error').style.display = 'none';
            
            wireRowEvents(clone);
            container.appendChild(clone);
        });

        // Validación del formulario
        document.getElementById('deliveryForm').addEventListener('submit', function(e) {
            const rows = document.querySelectorAll('#items-container .delivery-item');
            let hasError = false;
            
            rows.forEach(row => {
                const qtyInput = row.querySelector('.qty-input');
                const max = parseFloat(qtyInput.getAttribute('max')) || 0;
                const value = parseFloat(qtyInput.value) || 0;
                
                if (value > max && max > 0) {
                    hasError = true;
                    validateQty(row);
                }
            });
            
            if (hasError) {
                e.preventDefault();
                alert('Algunas cantidades exceden el inventario disponible. Revisa los errores en rojo.');
                return false;
            }
        });

        // Inicializar primera fila
        wireRowEvents(document.querySelector('#items-container .delivery-item'));
    </script>
</body>
</html>
