<?php
// Esta vista recibe $project, $requirements, $availableMaterials del controlador
$user = getCurrentUser();
$canEdit = hasAnyRole([ROLE_ADMIN, ROLE_PM]);
$projectId = $project['id'];

// Materiales ya agregados (para excluirlos del selector)
$materialesAgregados = array_column($requirements, 'material_id');
$materialesDisponibles = array_filter($availableMaterials, function($m) use ($materialesAgregados) {
    return !in_array($m['id'], $materialesAgregados);
});
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requerimientos - <?= h($project['nombre']) ?> - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .progress-bar-custom {
            height: 20px;
            font-size: 12px;
            line-height: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <div class="container mt-4">
        <?php 
        $flash = getFlashMessage();
        if ($flash): 
        ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= h($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Header del Proyecto -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="bi bi-list-ul"></i> Requerimientos del Proyecto</h2>
                        <h4 class="text-muted"><?= h($project['nombre']) ?></h4>
                        <?php if ($project['ubicacion']): ?>
                            <p class="text-muted mb-0"><i class="bi bi-geo-alt"></i> <?= h($project['ubicacion']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="<?= base_url('projects.php') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a Proyectos
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($canEdit && !empty($materialesDisponibles)): ?>
        <!-- Formulario para agregar materiales (múltiples a la vez) -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Agregar Materiales</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light" onclick="toggleView()" id="toggleBtn">
                        <i class="bi bi-list-check"></i> Modo Múltiple
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Modo Simple (Un material a la vez) -->
                <div id="single-mode">
                    <form method="POST" action="<?= base_url('requirements.php') ?>">
                        <input type="hidden" name="_action" value="store">
                        <input type="hidden" name="project_id" value="<?= $projectId ?>">
                        
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label for="material_id" class="form-label">Material <span class="text-danger">*</span></label>
                                <select class="form-select" id="material_id" name="material_id" required>
                                    <option value="">Seleccionar material...</option>
                                    <?php foreach ($materialesDisponibles as $material): ?>
                                        <option value="<?= $material['id'] ?>">
                                            <?= h($material['sku']) ?> - <?= h($material['descripcion']) ?> (<?= h($material['unidad']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="qty_requerida" class="form-label">Cantidad Requerida <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="qty_requerida" name="qty_requerida" 
                                       required min="0.01" step="0.01" placeholder="0.00">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="comentarios" class="form-label">Comentarios</label>
                                <input type="text" class="form-control" id="comentarios" name="comentarios" 
                                       placeholder="Opcional">
                            </div>
                            
                            <div class="col-md-1 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modo Múltiple (Varios materiales a la vez) -->
                <div id="multiple-mode" style="display: none;">
                    <form method="POST" action="<?= base_url('requirements.php') ?>" id="multipleForm">
                        <input type="hidden" name="_action" value="store">
                        <input type="hidden" name="project_id" value="<?= $projectId ?>">
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label">Selecciona materiales y especifica cantidades:</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll()">
                                        <i class="bi bi-check-all"></i> Seleccionar Todos
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                                        <i class="bi bi-x"></i> Deseleccionar
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" id="select-all-checkbox" onchange="toggleAllMaterials(this)">
                                            </th>
                                            <th>SKU</th>
                                            <th>Descripción</th>
                                            <th>Unidad</th>
                                            <th style="width: 200px;">Cantidad</th>
                                            <th style="width: 150px;">Categoría</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($materialesDisponibles as $idx => $material): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" 
                                                           class="material-checkbox" 
                                                           name="materials[<?= $idx ?>][material_id]" 
                                                           value="<?= $material['id'] ?>"
                                                           onchange="toggleMaterialRow(this)">
                                                </td>
                                                <td><code><?= h($material['sku']) ?></code></td>
                                                <td><?= h($material['descripcion']) ?></td>
                                                <td><small class="text-muted"><?= h($material['unidad']) ?></small></td>
                                                <td>
                                                    <input type="number" 
                                                           class="form-control form-control-sm qty-input" 
                                                           name="materials[<?= $idx ?>][qty]" 
                                                           min="0.01" 
                                                           step="0.01" 
                                                           placeholder="0.00"
                                                           disabled
                                                           style="display: none;">
                                                </td>
                                                <td><small class="text-muted"><?= h($material['categoria'] ?? 'General') ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3 d-flex justify-content-between">
                                <div>
                                    <span class="badge bg-info" id="selected-count">0 materiales seleccionados</span>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-secondary" onclick="toggleView()">
                                        <i class="bi bi-arrow-left"></i> Modo Simple
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="submit-multiple">
                                        <i class="bi bi-check-circle"></i> Agregar Seleccionados
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista de Requerimientos (BOM) -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> Lista de Materiales (BOM)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($requirements)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No hay materiales en los requerimientos de este proyecto</p>
                        <?php if ($canEdit && !empty($materialesDisponibles)): ?>
                            <p>Usa el formulario arriba para agregar materiales</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th>Requerido</th>
                                    <th>Comprado</th>
                                    <th>Disponible</th>
                                    <th>Entregado</th>
                                    <th>% Avance</th>
                                    <th>Costo Promedio</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requirements as $req): 
                                    $pct_entregado = $req['qty_requerida'] > 0 
                                        ? round(($req['qty_entregada'] / $req['qty_requerida']) * 100, 1) 
                                        : 0;
                                    
                                    $pct_disponible = $req['qty_requerida'] > 0 
                                        ? round(($req['qty_disponible'] / $req['qty_requerida']) * 100, 1) 
                                        : 0;
                                    
                                    $pct_faltante = max(0, 100 - $pct_entregado - $pct_disponible);
                                    
                                    // Color según estado
                                    if ($pct_entregado >= 100) {
                                        $color = 'success';
                                    } elseif ($pct_disponible > 0) {
                                        $color = 'warning';
                                    } elseif ($req['total_qty_comprada'] >= $req['qty_requerida']) {
                                        $color = 'info';
                                    } else {
                                        $color = 'danger';
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= h($req['descripcion']) ?></strong><br>
                                            <small class="text-muted">
                                                <code><?= h($req['sku']) ?></code> | 
                                                <?= h($req['unidad']) ?>
                                                <?php if ($req['categoria']): ?>
                                                    | <?= h($req['categoria']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td><?= number_format($req['qty_requerida'], 2) ?> <?= h($req['unidad']) ?></td>
                                        <td><?= number_format($req['total_qty_comprada'] ?? 0, 2) ?> <?= h($req['unidad']) ?></td>
                                        <td><?= number_format($req['qty_disponible'] ?? 0, 2) ?> <?= h($req['unidad']) ?></td>
                                        <td><?= number_format($req['qty_entregada'] ?? 0, 2) ?> <?= h($req['unidad']) ?></td>
                                        <td>
                                            <div class="progress progress-bar-custom mb-1">
                                                <div class="progress-bar bg-<?= $color ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= min(100, $pct_entregado) ?>%">
                                                    <?= $pct_entregado ?>%
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                Entregado: <?= $pct_entregado ?>% | 
                                                Disponible: <?= $pct_disponible ?>% | 
                                                Faltante: <?= $pct_faltante ?>%
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($req['costo_promedio_calc']): ?>
                                                <?= formatCurrency($req['costo_promedio_calc']) ?>
                                                <br><small class="text-muted">Total: <?= formatCurrency($req['total_costo'] ?? 0) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Sin costo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($canEdit): ?>
                                                <div class="btn-group" role="group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-warning" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editModal<?= $req['id'] ?>"
                                                            title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form method="POST" action="<?= base_url('requirements.php') ?>" 
                                                          style="display:inline;" 
                                                          onsubmit="return confirm('¿Eliminar este requerimiento?');">
                                                        <input type="hidden" name="_action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Modal de Edición -->
                                    <?php if ($canEdit): ?>
                                    <div class="modal fade" id="editModal<?= $req['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="<?= base_url('requirements.php') ?>">
                                                    <input type="hidden" name="_action" value="update">
                                                    <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Editar Requerimiento</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><strong><?= h($req['descripcion']) ?></strong></p>
                                                        
                                                        <div class="mb-3">
                                                            <label for="qty_<?= $req['id'] ?>" class="form-label">Cantidad Requerida</label>
                                                            <input type="number" class="form-control" 
                                                                   id="qty_<?= $req['id'] ?>" 
                                                                   name="qty_requerida" 
                                                                   required min="0.01" step="0.01"
                                                                   value="<?= $req['qty_requerida'] ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="comments_<?= $req['id'] ?>" class="form-label">Comentarios</label>
                                                            <textarea class="form-control" 
                                                                      id="comments_<?= $req['id'] ?>" 
                                                                      name="comentarios" 
                                                                      rows="3"><?= h($req['comentarios'] ?? '') ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Alternar entre modo simple y múltiple
        function toggleView() {
            const singleMode = document.getElementById('single-mode');
            const multipleMode = document.getElementById('multiple-mode');
            const toggleBtn = document.getElementById('toggleBtn');
            
            if (singleMode.style.display === 'none') {
                singleMode.style.display = 'block';
                multipleMode.style.display = 'none';
                toggleBtn.innerHTML = '<i class="bi bi-list-check"></i> Modo Múltiple';
            } else {
                singleMode.style.display = 'none';
                multipleMode.style.display = 'block';
                toggleBtn.innerHTML = '<i class="bi bi-plus"></i> Modo Simple';
            }
            updateSelectedCount();
        }

        // Habilitar/deshabilitar input de cantidad cuando se selecciona material
        function toggleMaterialRow(checkbox) {
            const row = checkbox.closest('tr');
            const qtyInput = row.querySelector('.qty-input');
            
            if (checkbox.checked) {
                qtyInput.style.display = 'block';
                qtyInput.disabled = false;
                qtyInput.focus();
            } else {
                qtyInput.style.display = 'none';
                qtyInput.disabled = true;
                qtyInput.value = '';
            }
            updateSelectedCount();
        }

        // Seleccionar/deseleccionar todos
        function toggleAllMaterials(checkbox) {
            const checkboxes = document.querySelectorAll('.material-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
                toggleMaterialRow(cb);
            });
        }

        function selectAll() {
            document.getElementById('select-all-checkbox').checked = true;
            toggleAllMaterials(document.getElementById('select-all-checkbox'));
        }

        function deselectAll() {
            document.getElementById('select-all-checkbox').checked = false;
            toggleAllMaterials(document.getElementById('select-all-checkbox'));
        }

        // Actualizar contador de seleccionados
        function updateSelectedCount() {
            const selected = document.querySelectorAll('.material-checkbox:checked').length;
            document.getElementById('selected-count').textContent = selected + ' material(es) seleccionado(s)';
        }

        // Validar formulario antes de enviar
        document.getElementById('multipleForm')?.addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.material-checkbox:checked');
            
            if (selected.length === 0) {
                e.preventDefault();
                alert('Por favor selecciona al menos un material');
                return false;
            }

            let hasQuantity = false;
            selected.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const qtyInput = row.querySelector('.qty-input');
                if (qtyInput && parseFloat(qtyInput.value) > 0) {
                    hasQuantity = true;
                }
            });

            if (!hasQuantity) {
                e.preventDefault();
                alert('Por favor especifica al menos una cantidad mayor a cero');
                return false;
            }
        });

        // Inicializar contador
        updateSelectedCount();
    </script>
</body>
</html>

