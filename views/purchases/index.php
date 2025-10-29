<?php
// Esta vista recibe $project, $purchases, $totals, $materials del controlador
$user = getCurrentUser();
$projectId = $project['id'];
$canCreate = hasAnyRole([ROLE_ADMIN, ROLE_PM]);
$isAdmin = hasRole(ROLE_ADMIN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras - <?= h($project['nombre']) ?> - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
                        <h2><i class="bi bi-cart-plus"></i> Registro de Compras</h2>
                        <h4 class="text-muted"><?= h($project['nombre']) ?></h4>
                    </div>
                    <div>
                        <a href="<?= base_url('projects.php') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a Proyectos
                        </a>
                        <?php if ($canCreate): ?>
                            <a href="<?= base_url("purchases.php?project_id=$projectId&action=create") ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Nueva Compra
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen de Totales -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Total de Compras</h5>
                        <h2 class="text-primary"><?= number_format($totals['total_compras']) ?></h2>
                        <small class="text-muted">Registros activos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Cantidad Total</h5>
                        <h2 class="text-success"><?= number_format($totals['total_cantidad'], 2) ?></h2>
                        <small class="text-muted">Unidades compradas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Total Invertido</h5>
                        <h2 class="text-info"><?= formatCurrency($totals['total_invertido']) ?></h2>
                        <small class="text-muted">Monto total</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Compras -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Historial de Compras</h5>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="showCancelled" 
                           onchange="window.location.href='<?= base_url("purchases.php?project_id=$projectId") ?>&cancelled=' + (this.checked ? '1' : '0')"
                           <?= isset($_GET['cancelled']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="showCancelled">
                        Mostrar canceladas
                    </label>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($purchases)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No hay compras registradas para este proyecto</p>
                        <?php if ($canCreate): ?>
                            <a href="<?= base_url("purchases.php?project_id=$projectId&action=create") ?>" class="btn btn-primary">
                                Registrar Primera Compra
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Material</th>
                                    <th>Cantidad</th>
                                    <th>Costo Unitario</th>
                                    <th>Total</th>
                                    <th>Proveedor</th>
                                    <th>Factura</th>
                                    <th>Comprado por</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr class="<?= $purchase['cancelado'] ? 'table-secondary' : '' ?>">
                                        <td><?= formatDate($purchase['fecha_compra'], 'd/m/Y') ?></td>
                                        <td>
                                            <strong><?= h($purchase['descripcion']) ?></strong><br>
                                            <small class="text-muted"><code><?= h($purchase['sku']) ?></code> | <?= h($purchase['unidad']) ?></small>
                                        </td>
                                        <td><?= number_format($purchase['qty_comprada'], 2) ?> <?= h($purchase['unidad']) ?></td>
                                        <td><?= formatCurrency($purchase['costo_unitario'], $purchase['moneda']) ?></td>
                                        <td><strong><?= formatCurrency($purchase['total'], $purchase['moneda']) ?></strong></td>
                                        <td><?= h($purchase['proveedor'] ?? '-') ?></td>
                                        <td><?= h($purchase['numero_factura'] ?? '-') ?></td>
                                        <td><?= h($purchase['comprador_nombre'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php if ($purchase['cancelado']): ?>
                                                <span class="badge bg-danger">Cancelada</span>
                                                <?php if ($purchase['motivo_cancelacion']): ?>
                                                    <br><small class="text-muted"><?= h(substr($purchase['motivo_cancelacion'], 0, 30)) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-success">Activa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isAdmin && !$purchase['cancelado']): ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#cancelModal<?= $purchase['id'] ?>"
                                                        title="Cancelar">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Modal de Cancelación -->
                                    <?php if ($isAdmin && !$purchase['cancelado']): ?>
                                    <div class="modal fade" id="cancelModal<?= $purchase['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="<?= base_url('purchases.php') ?>">
                                                    <input type="hidden" name="_action" value="cancel">
                                                    <input type="hidden" name="id" value="<?= $purchase['id'] ?>">
                                                    
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">⚠️ Cancelar Compra</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning">
                                                            <strong>Advertencia:</strong> Al cancelar esta compra se revertirán automáticamente:
                                                            <ul class="mb-0 mt-2">
                                                                <li>El inventario disponible disminuirá</li>
                                                                <li>Los costos promedio se recalcularán</li>
                                                                <li>El registro quedará marcado como cancelado</li>
                                                            </ul>
                                                        </div>
                                                        
                                                        <p><strong>Compra a cancelar:</strong></p>
                                                        <ul>
                                                            <li><strong>Material:</strong> <?= h($purchase['descripcion']) ?></li>
                                                            <li><strong>Cantidad:</strong> <?= number_format($purchase['qty_comprada'], 2) ?> <?= h($purchase['unidad']) ?></li>
                                                            <li><strong>Total:</strong> <?= formatCurrency($purchase['total'], $purchase['moneda']) ?></li>
                                                            <li><strong>Fecha:</strong> <?= formatDate($purchase['fecha_compra'], 'd/m/Y') ?></li>
                                                        </ul>
                                                        
                                                        <div class="mb-3">
                                                            <label for="motivo_<?= $purchase['id'] ?>" class="form-label">Motivo de cancelación</label>
                                                            <textarea class="form-control" 
                                                                      id="motivo_<?= $purchase['id'] ?>" 
                                                                      name="motivo_cancelacion" 
                                                                      rows="3" 
                                                                      placeholder="Especifica el motivo de la cancelación..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No Cancelar</button>
                                                        <button type="submit" class="btn btn-danger">Confirmar Cancelación</button>
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
</body>
</html>

