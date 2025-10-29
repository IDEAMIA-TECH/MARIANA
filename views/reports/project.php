<?php
// Esta vista recibe $project, $summary, $costReport del controlador
$projectId = $project['id'];
$canExport = hasAnyRole([ROLE_ADMIN, ROLE_PM]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte - <?= h($project['nombre']) ?> - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print { display: none; }
            .card { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <div class="container-fluid mt-4">
        <!-- Header con botones de exportación -->
        <div class="card mb-4 no-print">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="bi bi-file-earmark-text"></i> Reporte del Proyecto</h1>
                        <h3 class="text-muted"><?= h($project['nombre']) ?></h3>
                    </div>
                    <div>
                        <a href="<?= base_url('projects.php') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                        <?php if ($canExport): ?>
                            <a href="<?= base_url("reports.php?project_id=$projectId&action=excel") ?>" class="btn btn-success">
                                <i class="bi bi-file-excel"></i> Exportar Excel
                            </a>
                            <a href="<?= base_url("reports.php?project_id=$projectId&action=pdf") ?>" class="btn btn-danger">
                                <i class="bi bi-file-pdf"></i> Exportar PDF
                            </a>
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="bi bi-printer"></i> Imprimir
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del Proyecto -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-info-circle"></i> Información del Proyecto</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Nombre:</strong> <?= h($project['nombre']) ?></p>
                        <p><strong>Ubicación:</strong> <?= h($project['ubicacion'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Estado:</strong> 
                            <?php
                            $estados = [
                                'planning' => ['text' => 'Planificación', 'color' => 'secondary'],
                                'active' => ['text' => 'Activo', 'color' => 'success'],
                                'on_hold' => ['text' => 'En Pausa', 'color' => 'warning'],
                                'completed' => ['text' => 'Completado', 'color' => 'info']
                            ];
                            $estado = $estados[$project['estado']] ?? ['text' => $project['estado'], 'color' => 'secondary'];
                            ?>
                            <span class="badge bg-<?= $estado['color'] ?>"><?= $estado['text'] ?></span>
                        </p>
                        <p><strong>Fecha Inicio:</strong> <?= $project['fecha_inicio'] ? formatDate($project['fecha_inicio'], 'd/m/Y') : 'N/A' ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Fecha Fin:</strong> <?= $project['fecha_fin'] ? formatDate($project['fecha_fin'], 'd/m/Y') : 'N/A' ?></p>
                        <p><strong>Fecha del Reporte:</strong> <?= date('d/m/Y H:i') ?></p>
                    </div>
                </div>
                <?php if ($project['descripcion']): ?>
                    <hr>
                    <p><strong>Descripción:</strong></p>
                    <p><?= nl2br(h($project['descripcion'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resumen Ejecutivo -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="bi bi-graph-up"></i> Resumen Ejecutivo</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-primary"><?= number_format($summary['totals']['total_materiales'] ?? 0) ?></h3>
                            <p class="mb-0"><strong>Materiales</strong></p>
                            <small class="text-muted">En requerimientos</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-success"><?= number_format($summary['totals']['total_comprado'] ?? 0, 2) ?></h3>
                            <p class="mb-0"><strong>Total Comprado</strong></p>
                            <small class="text-muted">Unidades</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-warning"><?= number_format($summary['totals']['total_entregado'] ?? 0, 2) ?></h3>
                            <p class="mb-0"><strong>Total Entregado</strong></p>
                            <small class="text-muted">Unidades</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-danger"><?= formatCurrency($summary['totals']['total_invertido'] ?? 0) ?></h3>
                            <p class="mb-0"><strong>Total Invertido</strong></p>
                            <small class="text-muted">Monto financiero</small>
                        </div>
                    </div>
                </div>

                <?php if (!empty($summary['faltantes'])): ?>
                    <hr>
                    <h5>Materiales Faltantes por Comprar</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th>Cantidad Faltante</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary['faltantes'] as $faltante): ?>
                                    <tr>
                                        <td><?= h($faltante['descripcion']) ?></td>
                                        <td><strong><?= number_format($faltante['faltante'], 2) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla Detallada de Costos -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h4 class="mb-0"><i class="bi bi-table"></i> Reporte de Costos por Material</h4>
            </div>
            <div class="card-body">
                <?php if (empty($costReport)): ?>
                    <p class="text-muted">No hay materiales en los requerimientos de este proyecto.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>SKU</th>
                                    <th>Material</th>
                                    <th>Unidad</th>
                                    <th>Requerido</th>
                                    <th>Comprado</th>
                                    <th>Disponible</th>
                                    <th>Entregado</th>
                                    <th>% Entregado</th>
                                    <th>% Disponible</th>
                                    <th>% Faltante</th>
                                    <th>Costo Promedio</th>
                                    <th>Último Costo</th>
                                    <th>Último Proveedor</th>
                                    <th>Total Invertido</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($costReport as $item): ?>
                                    <tr>
                                        <td><code><?= h($item['sku']) ?></code></td>
                                        <td><?= h($item['material']) ?></td>
                                        <td><?= h($item['unidad']) ?></td>
                                        <td><?= number_format($item['qty_requerida'], 2) ?></td>
                                        <td><?= number_format($item['total_qty_comprada'], 2) ?></td>
                                        <td><?= number_format($item['cantidad_disponible'], 2) ?></td>
                                        <td><strong><?= number_format($item['cantidad_entregada'], 2) ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?= $item['pct_entregado'] >= 100 ? 'success' : 'primary' ?>">
                                                <?= number_format($item['pct_entregado'], 1) ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning"><?= number_format($item['pct_disponible'], 1) ?>%</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $item['pct_faltante'] > 0 ? 'danger' : 'success' ?>">
                                                <?= number_format($item['pct_faltante'], 1) ?>%
                                            </span>
                                        </td>
                                        <td><?= $item['costo_promedio_unitario'] > 0 ? formatCurrency($item['costo_promedio_unitario']) : '-' ?></td>
                                        <td><?= $item['ultimo_costo'] ? formatCurrency($item['ultimo_costo']) : '-' ?></td>
                                        <td><?= h($item['ultimo_proveedor'] ?? '-') ?></td>
                                        <td><strong><?= formatCurrency($item['total_costo']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-active">
                                <tr>
                                    <th colspan="3">TOTALES</th>
                                    <th><?= number_format(array_sum(array_column($costReport, 'qty_requerida')), 2) ?></th>
                                    <th><?= number_format(array_sum(array_column($costReport, 'total_qty_comprada')), 2) ?></th>
                                    <th><?= number_format(array_sum(array_column($costReport, 'cantidad_disponible')), 2) ?></th>
                                    <th><?= number_format(array_sum(array_column($costReport, 'cantidad_entregada')), 2) ?></th>
                                    <th colspan="4"></th>
                                    <th colspan="2"></th>
                                    <th><strong><?= formatCurrency(array_sum(array_column($costReport, 'total_costo'))) ?></strong></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historial de Compras (Últimas 10) -->
        <?php if (!empty($summary['compras_recientes'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-cart-check"></i> Compras Recientes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Material</th>
                                <th>Cantidad</th>
                                <th>Costo Unit.</th>
                                <th>Total</th>
                                <th>Proveedor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary['compras_recientes'] as $compra): ?>
                                <tr>
                                    <td><?= formatDate($compra['fecha_compra'], 'd/m/Y') ?></td>
                                    <td><?= h($compra['descripcion']) ?></td>
                                    <td><?= number_format($compra['qty_comprada'], 2) ?></td>
                                    <td><?= formatCurrency($compra['costo_unitario'], $compra['moneda']) ?></td>
                                    <td><?= formatCurrency($compra['qty_comprada'] * $compra['costo_unitario'], $compra['moneda']) ?></td>
                                    <td><?= h($compra['proveedor'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historial de Entregas (Últimas 10) -->
        <?php if (!empty($summary['entregas_recientes'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-truck"></i> Entregas Recientes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Material</th>
                                <th>Cantidad</th>
                                <th>Entregado A</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary['entregas_recientes'] as $entrega): ?>
                                <tr>
                                    <td><?= formatDate($entrega['fecha_entrega'], 'd/m/Y') ?></td>
                                    <td><?= h($entrega['descripcion']) ?></td>
                                    <td><?= number_format($entrega['qty_entregada'], 2) ?></td>
                                    <td><?= h($entrega['entregado_a']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

