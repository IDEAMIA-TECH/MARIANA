<?php
// Esta vista recibe $project, $purchase del controlador
$projectId = $project['id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Compra - <?= h($project['nombre']) ?> - <?= h(APP_NAME) ?></title>
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
                        <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Compra</h4>
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
                            <strong>Material:</strong> <?= h($purchase['descripcion']) ?>
                            <br><small class="text-muted"><code><?= h($purchase['sku']) ?></code> | <?= h($purchase['unidad']) ?></small>
                        </div>

                        <form method="POST" action="<?= base_url('purchases.php') ?>">
                            <input type="hidden" name="_action" value="update">
                            <input type="hidden" name="project_id" value="<?= $projectId ?>">
                            <input type="hidden" name="id" value="<?= (int)$purchase['id'] ?>">

                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" class="form-control" name="qty_comprada" required min="0.01" step="0.01" value="<?= (float)$purchase['qty_comprada'] ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Moneda y Costo Unitario</label>
                                    <div class="input-group">
                                        <select class="form-select" name="moneda" id="moneda">
                                            <option value="MXN" <?= $purchase['moneda']==='MXN'?'selected':'' ?>>MXN</option>
                                            <option value="USD" <?= $purchase['moneda']==='USD'?'selected':'' ?>>USD</option>
                                            <option value="EUR" <?= $purchase['moneda']==='EUR'?'selected':'' ?>>EUR</option>
                                        </select>
                                        <input type="number" class="form-control" name="costo_unitario" required min="0" step="0.01" value="<?= (float)$purchase['costo_unitario'] ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tipo de Cambio</label>
                                    <input type="number" class="form-control" name="tipo_cambio" id="tipo_cambio" min="0.0001" step="0.0001" placeholder="MXN/USD" value="<?= isset($purchase['tipo_cambio']) ? (float)$purchase['tipo_cambio'] : '' ?>">
                                    <small class="text-muted">Solo aplica cuando la moneda es USD</small>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Proveedor</label>
                                    <input type="text" class="form-control" name="proveedor" maxlength="150" value="<?= h($purchase['proveedor'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Número de Factura</label>
                                    <input type="text" class="form-control" name="numero_factura" maxlength="50" value="<?= h($purchase['numero_factura'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Compra</label>
                                    <input type="date" class="form-control" name="fecha_compra" required value="<?= h($purchase['fecha_compra']) ?>" max="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="<?= base_url("purchases.php?project_id=$projectId") ?>" class="btn btn-secondary">
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
    <script>
        function toggleTC() {
            const moneda = document.getElementById('moneda').value;
            const tc = document.getElementById('tipo_cambio');
            if (moneda === 'USD') {
                tc.removeAttribute('disabled');
            } else {
                tc.value = '';
                tc.setAttribute('disabled', 'disabled');
            }
        }
        document.getElementById('moneda').addEventListener('change', toggleTC);
        toggleTC();
    </script>
</body>
</html>


