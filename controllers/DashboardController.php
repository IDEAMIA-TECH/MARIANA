<?php
declare(strict_types=1);

/**
 * Controlador de Dashboard
 */
class DashboardController
{
    /**
     * Mostrar dashboard del proyecto
     */
    public static function index(): void
    {
        $projectId = (int)($_GET['id'] ?? $_GET['project_id'] ?? 0);
        
        if (!$projectId) {
            setFlashMessage('error', 'Proyecto no especificado');
            redirect(base_url('projects.php'));
            return;
        }

        $project = Project::findById($projectId);
        if (!$project) {
            setFlashMessage('error', 'Proyecto no encontrado');
            redirect(base_url('projects.php'));
            return;
        }

        // Verificar permisos
        $user = getCurrentUser();
        if (!hasRole(ROLE_ADMIN) && $project['created_by'] != $user['id'] && !hasRole(ROLE_PM)) {
            // Viewer puede ver dashboard de proyectos activos
            if (!hasRole(ROLE_VIEWER) || $project['estado'] !== 'active') {
                setFlashMessage('error', 'No tienes permisos para ver este proyecto');
                redirect(base_url('projects.php'));
                return;
            }
        }

        // Obtener todos los requerimientos con datos de inventario y costos
        $requirements = Database::fetchAll(
            "SELECT 
                pr.*,
                m.sku, m.descripcion, m.unidad, m.categoria,
                COALESCE(inv.qty_disponible, 0) as qty_disponible,
                COALESCE(inv.qty_entregada, 0) as qty_entregada,
                COALESCE(inv.qty_instalada, 0) as qty_instalada,
                COALESCE(stats.total_qty_comprada, 0) as total_comprada,
                COALESCE(stats.total_costo, 0) as total_costo,
                COALESCE(stats.costo_promedio_calc, 0) as costo_promedio,
                -- Última compra
                (SELECT costo_unitario FROM purchases 
                 WHERE project_id = pr.project_id AND material_id = pr.material_id 
                   AND cancelado = 0 
                 ORDER BY fecha_compra DESC LIMIT 1) as ultimo_costo,
                (SELECT proveedor FROM purchases 
                 WHERE project_id = pr.project_id AND material_id = pr.material_id 
                   AND cancelado = 0 
                 ORDER BY fecha_compra DESC LIMIT 1) as ultimo_proveedor,
                -- Porcentajes
                ROUND((COALESCE(inv.qty_entregada, 0) / pr.qty_requerida) * 100, 2) as pct_entregado,
                ROUND((COALESCE(inv.qty_disponible, 0) / pr.qty_requerida) * 100, 2) as pct_disponible,
                ROUND(((pr.qty_requerida - COALESCE(inv.qty_entregada, 0) - COALESCE(inv.qty_disponible, 0)) / pr.qty_requerida) * 100, 2) as pct_faltante
             FROM project_requirements pr
             JOIN materials m ON m.id = pr.material_id
             LEFT JOIN inventory inv ON inv.project_id = pr.project_id AND inv.material_id = pr.material_id
             LEFT JOIN material_cost_stats stats ON stats.project_id = pr.project_id AND stats.material_id = pr.material_id
             WHERE pr.project_id = ?
             ORDER BY m.descripcion ASC",
            [$projectId]
        );

        // Calcular KPIs globales
        $kpis = self::calculateKPIs($requirements);
        
        // Estadísticas de compras y entregas
        $purchasesStats = Purchase::getTotals($projectId);
        $deliveriesStats = Delivery::getTotals($projectId);
        
        // Totales por moneda para tarjetas (USD y MXN global)
        $rows = Database::fetchAll(
            "SELECT moneda, qty_comprada, costo_unitario, tipo_cambio, cancelado
             FROM purchases WHERE project_id = ?",
            [$projectId]
        );
        $totalUSD = 0.0;
        $totalGlobalMXN = 0.0;
        foreach ($rows as $r) {
            if (!empty($r['cancelado'])) { continue; }
            $sub = (float)$r['qty_comprada'] * (float)$r['costo_unitario'];
            if (($r['moneda'] ?? 'MXN') === 'USD') {
                $totalUSD += $sub;
                $tc = (float)($r['tipo_cambio'] ?? 0);
                if ($tc > 0) { $totalGlobalMXN += $sub * $tc; }
            } elseif (($r['moneda'] ?? 'MXN') === 'MXN') {
                $totalGlobalMXN += $sub;
            }
        }

        // Datos para gráficas
        $chartData = self::prepareChartData($requirements);

        require_once __DIR__ . '/../views/projects/dashboard.php';
    }

    /**
     * Calcular KPIs globales del proyecto
     */
    private static function calculateKPIs(array $requirements): array
    {
        $totalRequerido = 0;
        $totalComprado = 0;
        $totalDisponible = 0;
        $totalEntregado = 0;
        $totalInstalado = 0;
        $totalInvertido = 0;
        $materialesCompletos = 0;
        $materialesParciales = 0;
        $materialesFaltantes = 0;

        foreach ($requirements as $req) {
            $totalRequerido += (float)$req['qty_requerida'];
            $totalComprado += (float)$req['total_comprada'];
            $totalDisponible += (float)$req['qty_disponible'];
            $totalEntregado += (float)$req['qty_entregada'];
            $totalInstalado += (float)($req['qty_instalada'] ?? 0);
            $totalInvertido += (float)$req['total_costo'];

            $pctEntregado = (float)$req['pct_entregado'];
            if ($pctEntregado >= 100) {
                $materialesCompletos++;
            } elseif ($pctEntregado > 0 || (float)$req['qty_disponible'] > 0) {
                $materialesParciales++;
            } else {
                $materialesFaltantes++;
            }
        }

        $pctAvanceFisico = $totalRequerido > 0 
            ? round(($totalEntregado / $totalRequerido) * 100, 1) 
            : 0;

        $pctComprado = $totalRequerido > 0 
            ? round(($totalComprado / $totalRequerido) * 100, 1) 
            : 0;

        return [
            'total_requerido' => $totalRequerido,
            'total_comprado' => $totalComprado,
            'total_disponible' => $totalDisponible,
            'total_entregado' => $totalEntregado,
            'total_instalado' => $totalInstalado,
            'total_invertido' => $totalInvertido,
            'pct_avance_fisico' => $pctAvanceFisico,
            'pct_comprado' => $pctComprado,
            'materiales_completos' => $materialesCompletos,
            'materiales_parciales' => $materialesParciales,
            'materiales_faltantes' => $materialesFaltantes,
            'total_materiales' => count($requirements)
        ];
    }

    /**
     * Preparar datos para gráficas
     */
    private static function prepareChartData(array $requirements): array
    {
        $labels = [];
        $entregado = [];
        $disponible = [];
        $faltante = [];
        $costos = [];

        foreach ($requirements as $req) {
            $labels[] = substr($req['descripcion'], 0, 30);
            $entregado[] = (float)$req['pct_entregado'];
            $disponible[] = (float)$req['pct_disponible'];
            $faltante[] = max(0, (float)$req['pct_faltante']);
            $costos[] = (float)$req['total_costo'];
        }

        return [
            'labels' => $labels,
            'entregado' => $entregado,
            'disponible' => $disponible,
            'faltante' => $faltante,
            'costos' => $costos,
            'nombres' => array_column($requirements, 'descripcion')
        ];
    }

    /**
     * API: Obtener datos para gráficas (AJAX)
     */
    public static function getChartData(): void
    {
        header('Content-Type: application/json');
        
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) {
            echo json_encode(['error' => 'Project ID required']);
            exit;
        }

        $requirements = Database::fetchAll(
            "SELECT 
                m.descripcion,
                COALESCE(inv.qty_entregada, 0) / pr.qty_requerida * 100 as pct_entregado,
                COALESCE(inv.qty_disponible, 0) / pr.qty_requerida * 100 as pct_disponible,
                COALESCE(stats.total_costo, 0) as total_costo
             FROM project_requirements pr
             JOIN materials m ON m.id = pr.material_id
             LEFT JOIN inventory inv ON inv.project_id = pr.project_id AND inv.material_id = pr.material_id
             LEFT JOIN material_cost_stats stats ON stats.project_id = pr.project_id AND stats.material_id = pr.material_id
             WHERE pr.project_id = ?",
            [$projectId]
        );

        $labels = [];
        $entregado = [];
        $disponible = [];
        $faltante = [];
        $costos = [];

        foreach ($requirements as $req) {
            $labels[] = substr($req['descripcion'], 0, 30);
            $entregado[] = round((float)$req['pct_entregado'], 1);
            $disponible[] = round((float)$req['pct_disponible'], 1);
            $faltante[] = max(0, round(100 - (float)$req['pct_entregado'] - (float)$req['pct_disponible'], 1));
            $costos[] = (float)$req['total_costo'];
        }

        echo json_encode([
            'labels' => $labels,
            'entregado' => $entregado,
            'disponible' => $disponible,
            'faltante' => $faltante,
            'costos' => $costos
        ]);
        exit;
    }
}

