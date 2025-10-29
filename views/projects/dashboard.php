<?php
// Esta vista recibe $project, $requirements, $kpis, $purchasesStats, $deliveriesStats, $chartData
$projectId = $project['id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= h($project['nombre']) ?> - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .kpi-card {
            transition: transform 0.2s;
        }
        .kpi-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <div class="container-fluid mt-4">
        <?php 
        $flash = getFlashMessage();
        if ($flash): 
        ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= h($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="bi bi-graph-up"></i> Dashboard del Proyecto</h1>
                        <h3 class="text-muted"><?= h($project['nombre']) ?></h3>
                        <?php if ($project['ubicacion']): ?>
                            <p class="text-muted mb-0"><i class="bi bi-geo-alt"></i> <?= h($project['ubicacion']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="<?= base_url('projects.php') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs Globales -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card kpi-card border-primary">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle text-primary" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-primary"><?= number_format($kpis['pct_avance_fisico'], 1) ?>%</h4>
                        <p class="text-muted mb-0">Avance Físico</p>
                        <small class="text-muted"><?= number_format($kpis['total_entregado'], 2) ?> / <?= number_format($kpis['total_requerido'], 2) ?></small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card kpi-card border-success">
                    <div class="card-body text-center">
                        <i class="bi bi-cart-check text-success" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-success"><?= number_format($kpis['pct_comprado'], 1) ?>%</h4>
                        <p class="text-muted mb-0">Comprado</p>
                        <small class="text-muted"><?= number_format($kpis['total_comprado'], 2) ?> de <?= number_format($kpis['total_requerido'], 2) ?></small>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card kpi-card border-info">
                    <div class="card-body text-center">
                        <i class="bi bi-wallet2 text-info" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-info"><?= formatCurrency($kpis['total_invertido']) ?></h4>
                        <p class="text-muted mb-0">Total Invertido</p>
                        <small class="text-muted"><?= $purchasesStats['total_compras'] ?> compras</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card kpi-card border-warning">
                    <div class="card-body text-center">
                        <i class="bi bi-box-seam text-warning" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-warning"><?= number_format($kpis['total_disponible'], 2) ?></h4>
                        <p class="text-muted mb-0">En Almacén</p>
                        <small class="text-muted">Listo para entregar</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas de Materiales -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <i class="bi bi-check2-circle text-success" style="font-size: 2rem;"></i>
                        <h3 class="text-success"><?= $kpis['materiales_completos'] ?></h3>
                        <p class="text-muted mb-0">Materiales Completos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <i class="bi bi-hourglass-split text-warning" style="font-size: 2rem;"></i>
                        <h3 class="text-warning"><?= $kpis['materiales_parciales'] ?></h3>
                        <p class="text-muted mb-0">En Proceso</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                        <h3 class="text-danger"><?= $kpis['materiales_faltantes'] ?></h3>
                        <p class="text-muted mb-0">Faltantes</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficas -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Avance por Material</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="avanceChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Distribución de Costos</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="costosChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla Resumen -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-table"></i> Resumen de Materiales</h5>
            </div>
            <div class="card-body">
                <?php if (empty($requirements)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">Este proyecto no tiene materiales en sus requerimientos</p>
                        <a href="<?= base_url("requirements.php?project_id=$projectId") ?>" class="btn btn-primary">
                            Agregar Materiales
                        </a>
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
                                    <th>% Entregado</th>
                                    <th>% En Almacén</th>
                                    <th>% Faltante</th>
                                    <th>Costo Promedio</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requirements as $req): 
                                    $pctEntregado = (float)$req['pct_entregado'];
                                    $pctDisponible = (float)$req['pct_disponible'];
                                    $pctFaltante = max(0, (float)$req['pct_faltante']);
                                    
                                    // Determinar color según estado
                                    if ($pctEntregado >= 100) {
                                        $estadoColor = 'success';
                                        $estadoTexto = 'Completo';
                                    } elseif ($pctDisponible > 0) {
                                        $estadoColor = 'warning';
                                        $estadoTexto = 'En Almacén';
                                    } elseif ((float)$req['total_comprada'] >= (float)$req['qty_requerida']) {
                                        $estadoColor = 'info';
                                        $estadoTexto = 'Comprado';
                                    } else {
                                        $estadoColor = 'danger';
                                        $estadoTexto = 'Faltante';
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
                                        <td><?= number_format($req['total_comprada'], 2) ?> <?= h($req['unidad']) ?></td>
                                        <td><?= number_format($req['qty_disponible'], 2) ?> <?= h($req['unidad']) ?></td>
                                        <td><strong><?= number_format($req['qty_entregada'], 2) ?> <?= h($req['unidad']) ?></strong></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-<?= $pctEntregado >= 100 ? 'success' : 'primary' ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= min(100, $pctEntregado) ?>%">
                                                    <?= number_format($pctEntregado, 1) ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning"><?= number_format($pctDisponible, 1) ?>%</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $pctFaltante > 0 ? 'danger' : 'success' ?>">
                                                <?= number_format($pctFaltante, 1) ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($req['costo_promedio'] > 0): ?>
                                                <?= formatCurrency($req['costo_promedio']) ?><br>
                                                <small class="text-muted">
                                                    Último: <?= $req['ultimo_costo'] ? formatCurrency($req['ultimo_costo']) : '-' ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">Sin costo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= formatCurrency($req['total_costo']) ?></strong>
                                            <?php if ($req['ultimo_proveedor']): ?>
                                                <br><small class="text-muted"><?= h($req['ultimo_proveedor']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $estadoColor ?> status-badge"><?= $estadoTexto ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th>TOTALES</th>
                                    <th><?= number_format($kpis['total_requerido'], 2) ?></th>
                                    <th><?= number_format($kpis['total_comprado'], 2) ?></th>
                                    <th><?= number_format($kpis['total_disponible'], 2) ?></th>
                                    <th><strong><?= number_format($kpis['total_entregado'], 2) ?></strong></th>
                                    <th colspan="3"></th>
                                    <th></th>
                                    <th><strong><?= formatCurrency($kpis['total_invertido']) ?></strong></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Datos para gráficas
        const chartData = {
            labels: <?= json_encode($chartData['labels']) ?>,
            entregado: <?= json_encode($chartData['entregado']) ?>,
            disponible: <?= json_encode($chartData['disponible']) ?>,
            faltante: <?= json_encode($chartData['faltante']) ?>,
            costos: <?= json_encode($chartData['costos']) ?>,
            nombres: <?= json_encode($chartData['nombres']) ?>
        };

        // Gráfica de Avance (Barras agrupadas)
        const ctxAvance = document.getElementById('avanceChart').getContext('2d');
        new Chart(ctxAvance, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: '% Entregado',
                        data: chartData.entregado,
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    },
                    {
                        label: '% En Almacén',
                        data: chartData.disponible,
                        backgroundColor: 'rgba(255, 193, 7, 0.8)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1
                    },
                    {
                        label: '% Faltante',
                        data: chartData.faltante,
                        backgroundColor: 'rgba(220, 53, 69, 0.8)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        });

        // Gráfica de Costos (Pastel)
        const ctxCostos = document.getElementById('costosChart').getContext('2d');
        const colors = [
            'rgba(54, 162, 235, 0.8)', 'rgba(255, 99, 132, 0.8)', 'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)', 'rgba(153, 102, 255, 0.8)', 'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)', 'rgba(83, 102, 255, 0.8)', 'rgba(255, 99, 255, 0.8)'
        ];
        
        new Chart(ctxCostos, {
            type: 'doughnut',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Costo',
                    data: chartData.costos,
                    backgroundColor: colors.slice(0, chartData.costos.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': $' + context.parsed.toLocaleString('es-MX', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

