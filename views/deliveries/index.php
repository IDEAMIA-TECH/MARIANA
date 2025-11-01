<?php
// Esta vista recibe $project, $deliveries, $totals, $materialsWithInventory del controlador
$user = getCurrentUser();
$projectId = $project['id'];
$canCreate = hasAnyRole([ROLE_ADMIN, ROLE_PM, ROLE_ALMACEN]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entregas - <?= h($project['nombre']) ?> - <?= h(APP_NAME) ?></title>
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
                        <h2><i class="bi bi-truck"></i> Registro de Entregas</h2>
                        <h4 class="text-muted"><?= h($project['nombre']) ?></h4>
                    </div>
                    <div>
                        <a href="<?= base_url('projects.php') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a Proyectos
                        </a>
                        <?php if ($canCreate): ?>
                            <a href="<?= base_url("deliveries.php?project_id=$projectId&action=create") ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Nueva Entrega
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen de Totales -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Total de Entregas</h5>
                        <h2 class="text-primary"><?= number_format($totals['total_entregas']) ?></h2>
                        <small class="text-muted">Registros</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Cantidad Total Entregada</h5>
                        <h2 class="text-success"><?= number_format($totals['total_cantidad_entregada'], 2) ?></h2>
                        <small class="text-muted">Unidades</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Entregas -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Historial de Entregas</h5>
            </div>
            <div class="card-body">
                <?php if (empty($deliveries)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No hay entregas registradas para este proyecto</p>
                        <?php if ($canCreate): ?>
                            <a href="<?= base_url("deliveries.php?project_id=$projectId&action=create") ?>" class="btn btn-primary">
                                Registrar Primera Entrega
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
                                    <th>Entregado A</th>
                                    <th>Entregado Por</th>
                                    <th>Comentarios</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deliveries as $delivery): ?>
                                    <tr>
                                        <td><?= formatDate($delivery['fecha_entrega'], 'd/m/Y') ?></td>
                                        <td>
                                            <strong><?= h($delivery['descripcion']) ?></strong><br>
                                            <small class="text-muted"><code><?= h($delivery['sku']) ?></code> | <?= h($delivery['unidad']) ?></small>
                                        </td>
                                        <td><strong><?= number_format($delivery['qty_entregada'], 2) ?> <?= h($delivery['unidad']) ?></strong></td>
                                        <td><?= h($delivery['entregado_a']) ?></td>
                                        <td><?= h($delivery['entregador_nombre'] ?? 'N/A') ?></td>
                                        <td><?= h($delivery['comentarios'] ?? '-') ?></td>
                                        <td class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailModal<?= $delivery['id'] ?>"
                                                    title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if (hasAnyRole([ROLE_ADMIN, ROLE_PM, ROLE_ALMACEN])): ?>
                                                <a href="<?= base_url("deliveries.php?project_id=$projectId&action=edit&id={$delivery['id']}") ?>" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal<?= $delivery['id'] ?>"
                                                        title="Borrar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Modal de Detalles -->
                                    <div class="modal fade" id="detailModal<?= $delivery['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-info text-white">
                                                    <h5 class="modal-title">Detalles de la Entrega</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="card h-100">
                                                                <div class="card-body">
                                                                    <h6 class="text-muted">Material</h6>
                                                                    <p class="mb-1"><strong><?= h($delivery['descripcion']) ?></strong></p>
                                                                    <small class="text-muted"><code><?= h($delivery['sku']) ?></code> | <?= h($delivery['unidad']) ?></small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card h-100">
                                                                <div class="card-body">
                                                                    <h6 class="text-muted">Entrega</h6>
                                                                    <p class="mb-1">Fecha: <strong><?= formatDate($delivery['fecha_entrega'], 'd/m/Y') ?></strong></p>
                                                                    <p class="mb-1">Cantidad: <strong><?= number_format((float)($delivery['qty_entregada'] ?? 0), 2) ?> <?= h($delivery['unidad']) ?></strong></p>
                                                                    <p class="mb-0">Entregado a: <strong><?= h($delivery['entregado_a']) ?></strong></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card h-100">
                                                                <div class="card-body">
                                                                    <h6 class="text-muted">Registro</h6>
                                                                    <p class="mb-1">Entregado por: <strong><?= h($delivery['entregador_nombre'] ?? 'N/A') ?></strong></p>
                                                                    <?php if (!empty($delivery['created_at'])): ?>
                                                                        <p class="mb-0">Creado: <strong><?= h($delivery['created_at']) ?></strong></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card h-100">
                                                                <div class="card-body">
                                                                    <h6 class="text-muted">Comentarios</h6>
                                                                    <p class="mb-0"><?= nl2br(h($delivery['comentarios'] ?? '-')) ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal de Borrar -->
                                    <?php if (hasAnyRole([ROLE_ADMIN, ROLE_PM, ROLE_ALMACEN])): ?>
                                    <div class="modal fade" id="deleteModal<?= $delivery['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="<?= base_url('deliveries.php') ?>">
                                                    <input type="hidden" name="_action" value="destroy">
                                                    <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                                    <input type="hidden" name="id" value="<?= $delivery['id'] ?>">
                                                    
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">⚠️ Borrar Entrega</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning">
                                                            <strong>Advertencia:</strong> Al borrar esta entrega se revertirán automáticamente:
                                                            <ul class="mb-0 mt-2">
                                                                <li>El inventario disponible aumentará</li>
                                                                <li>La cantidad entregada disminuirá</li>
                                                                <li>El registro será eliminado permanentemente</li>
                                                            </ul>
                                                        </div>
                                                        
                                                        <p><strong>Entrega a borrar:</strong></p>
                                                        <ul>
                                                            <li><strong>Material:</strong> <?= h($delivery['descripcion']) ?></li>
                                                            <li><strong>Cantidad:</strong> <?= number_format((float)($delivery['qty_entregada'] ?? 0), 2) ?> <?= h($delivery['unidad']) ?></li>
                                                            <li><strong>Entregado a:</strong> <?= h($delivery['entregado_a']) ?></li>
                                                            <li><strong>Fecha:</strong> <?= formatDate($delivery['fecha_entrega'], 'd/m/Y') ?></li>
                                                        </ul>
                                                        
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="confirmDelete<?= $delivery['id'] ?>" required>
                                                            <label class="form-check-label" for="confirmDelete<?= $delivery['id'] ?>">
                                                                Confirmo que quiero borrar esta entrega
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-danger">Confirmar Borrado</button>
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

