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
</head>
<body>
    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
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
                                <label for="material_id" class="form-label">Material <span class="text-danger">*</span></label>
                                <select class="form-select" id="material_id" name="material_id" required>
                                    <option value="">Seleccionar material...</option>
                                    <?php foreach ($materials as $material): ?>
                                        <option value="<?= $material['id'] ?>" 
                                                data-disponible="<?= $material['qty_disponible'] ?>"
                                                data-unidad="<?= h($material['unidad']) ?>">
                                            <?= h($material['sku']) ?> - <?= h($material['descripcion']) ?> 
                                            (Disponible: <?= number_format($material['qty_disponible'], 2) ?> <?= h($material['unidad']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Solo materiales con inventario disponible</small>
                            </div>

                            <div class="mb-3">
                                <label for="qty_disponible_show" class="form-label">Cantidad Disponible</label>
                                <input type="text" class="form-control" id="qty_disponible_show" 
                                       readonly style="background-color: #f8f9fa;">
                                <small class="form-text text-muted">Mostrado automáticamente según el material seleccionado</small>
                            </div>

                            <div class="mb-3">
                                <label for="qty_entregada" class="form-label">Cantidad a Entregar <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="qty_entregada" name="qty_entregada" 
                                       required min="0.01" step="0.01" placeholder="0.00">
                                <small class="form-text text-muted">No puede exceder la cantidad disponible</small>
                                <div id="qty-error" class="text-danger" style="display: none;"></div>
                            </div>

                            <div class="mb-3">
                                <label for="entregado_a" class="form-label">Entregado A <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="entregado_a" name="entregado_a" 
                                       required maxlength="120" 
                                       placeholder="Ej: Obra Zona A, Responsable de Obra, etc.">
                                <small class="form-text text-muted">Especifica a quién o dónde se entregó el material</small>
                            </div>

                            <div class="mb-3">
                                <label for="fecha_entrega" class="form-label">Fecha de Entrega <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" 
                                       required value="<?= date('Y-m-d') ?>"
                                       max="<?= date('Y-m-d') ?>">
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
                                    <i class="bi bi-check-circle"></i> Registrar Entrega
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
        const materialSelect = document.getElementById('material_id');
        const qtyDisponibleShow = document.getElementById('qty_disponible_show');
        const qtyEntregada = document.getElementById('qty_entregada');
        const qtyError = document.getElementById('qty-error');

        materialSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            if (selected.value) {
                const disponible = parseFloat(selected.getAttribute('data-disponible')) || 0;
                const unidad = selected.getAttribute('data-unidad') || '';
                
                qtyDisponibleShow.value = disponible.toFixed(2) + ' ' + unidad;
                qtyEntregada.setAttribute('max', disponible);
                qtyEntregada.value = '';
                qtyError.style.display = 'none';
            } else {
                qtyDisponibleShow.value = '';
                qtyEntregada.removeAttribute('max');
            }
        });

        qtyEntregada.addEventListener('input', function() {
            const max = parseFloat(this.getAttribute('max')) || 0;
            const value = parseFloat(this.value) || 0;
            
            if (value > max) {
                qtyError.textContent = `La cantidad no puede exceder ${max.toFixed(2)}`;
                qtyError.style.display = 'block';
                this.setCustomValidity(`Máximo: ${max.toFixed(2)}`);
            } else {
                qtyError.style.display = 'none';
                this.setCustomValidity('');
            }
        });

        document.getElementById('deliveryForm').addEventListener('submit', function(e) {
            const max = parseFloat(qtyEntregada.getAttribute('max')) || 0;
            const value = parseFloat(qtyEntregada.value) || 0;
            
            if (value > max) {
                e.preventDefault();
                alert(`La cantidad a entregar (${value.toFixed(2)}) no puede exceder la disponible (${max.toFixed(2)})`);
                return false;
            }
        });
    </script>
</body>
</html>

