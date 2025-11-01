<?php
// Esta vista recibe $project, $delivery del controlador
$projectId = $project['id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Entrega - <?= h($project['nombre']) ?> - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Entrega</h4>
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

                        <div class="mb-3">
                            <strong>Material:</strong> <?= h($delivery['descripcion']) ?>
                            <br><small class="text-muted"><code><?= h($delivery['sku']) ?></code> | <?= h($delivery['unidad']) ?></small>
                        </div>

                        <form method="POST" action="<?= base_url('deliveries.php') ?>">
                            <input type="hidden" name="_action" value="update">
                            <input type="hidden" name="project_id" value="<?= $projectId ?>">
                            <input type="hidden" name="id" value="<?= (int)$delivery['id'] ?>">

                            <div class="mb-3">
                                <label for="qty_entregada" class="form-label">Cantidad Entregada <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="qty_entregada" id="qty_entregada" required min="0.01" step="0.0001" value="<?= (float)$delivery['qty_entregada'] ?>">
                                <small class="form-text text-muted">Unidad: <?= h($delivery['unidad']) ?></small>
                            </div>

                            <div class="mb-3">
                                <label for="entregado_a" class="form-label">Entregado A <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="entregado_a" id="entregado_a" required maxlength="120" value="<?= h($delivery['entregado_a']) ?>" placeholder="Ej: Obra Zona A, Responsable de Obra, etc.">
                            </div>

                            <div class="mb-3">
                                <label for="fecha_entrega" class="form-label">Fecha de Entrega <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha_entrega" id="fecha_entrega" required value="<?= h($delivery['fecha_entrega']) ?>" max="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="comentarios" class="form-label">Comentarios</label>
                                <textarea class="form-control" name="comentarios" id="comentarios" rows="3" placeholder="Observaciones adicionales (opcional)"><?= h($delivery['comentarios'] ?? '') ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?= base_url("deliveries.php?project_id=$projectId") ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-check-circle"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

