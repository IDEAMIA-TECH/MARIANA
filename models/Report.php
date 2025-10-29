<?php
declare(strict_types=1);

/**
 * Modelo de Reportes
 */
class Report
{
    /**
     * Obtener reporte completo de costos por material
     */
    public static function getCostReport(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT 
                m.sku,
                m.descripcion AS material,
                m.unidad,
                pr.qty_requerida,
                COALESCE(stats.total_qty_comprada, 0) AS total_qty_comprada,
                COALESCE(stats.total_costo, 0) AS total_costo,
                COALESCE(stats.costo_promedio_calc, 0) AS costo_promedio_unitario,
                COALESCE(inv.qty_entregada, 0) AS cantidad_entregada,
                COALESCE(inv.qty_disponible, 0) AS cantidad_disponible,
                -- Última compra
                (SELECT costo_unitario FROM purchases 
                 WHERE project_id = pr.project_id AND material_id = pr.material_id 
                   AND cancelado = 0 
                 ORDER BY fecha_compra DESC LIMIT 1) AS ultimo_costo,
                (SELECT proveedor FROM purchases 
                 WHERE project_id = pr.project_id AND material_id = pr.material_id 
                   AND cancelado = 0 
                 ORDER BY fecha_compra DESC LIMIT 1) AS ultimo_proveedor,
                (SELECT fecha_compra FROM purchases 
                 WHERE project_id = pr.project_id AND material_id = pr.material_id 
                   AND cancelado = 0 
                 ORDER BY fecha_compra DESC LIMIT 1) AS ultima_fecha_compra,
                -- Porcentajes
                ROUND((COALESCE(inv.qty_entregada, 0) / pr.qty_requerida) * 100, 2) AS pct_entregado,
                ROUND((COALESCE(inv.qty_disponible, 0) / pr.qty_requerida) * 100, 2) AS pct_disponible,
                ROUND(((pr.qty_requerida - COALESCE(inv.qty_entregada, 0) - COALESCE(inv.qty_disponible, 0)) / pr.qty_requerida) * 100, 2) AS pct_faltante
             FROM project_requirements pr
             JOIN materials m ON m.id = pr.material_id
             LEFT JOIN inventory inv ON inv.project_id = pr.project_id AND inv.material_id = pr.material_id
             LEFT JOIN material_cost_stats stats ON stats.project_id = pr.project_id AND stats.material_id = pr.material_id
             WHERE pr.project_id = ?
             ORDER BY m.descripcion ASC",
            [$projectId]
        );
    }

    /**
     * Obtener resumen ejecutivo del proyecto
     */
    public static function getExecutiveSummary(int $projectId): array
    {
        $project = Project::findById($projectId);
        if (!$project) {
            return [];
        }

        // Totales generales
        $totals = Database::fetchOne(
            "SELECT 
                COUNT(DISTINCT pr.id) AS total_materiales,
                SUM(pr.qty_requerida) AS total_requerido,
                SUM(COALESCE(inv.qty_disponible, 0)) AS total_disponible,
                SUM(COALESCE(inv.qty_entregada, 0)) AS total_entregado,
                SUM(COALESCE(stats.total_qty_comprada, 0)) AS total_comprado,
                SUM(COALESCE(stats.total_costo, 0)) AS total_invertido,
                AVG(COALESCE(stats.costo_promedio_calc, 0)) AS costo_promedio_general
             FROM project_requirements pr
             LEFT JOIN inventory inv ON inv.project_id = pr.project_id AND inv.material_id = pr.material_id
             LEFT JOIN material_cost_stats stats ON stats.project_id = pr.project_id AND stats.material_id = pr.material_id
             WHERE pr.project_id = ?",
            [$projectId]
        );

        // Materiales faltantes
        $faltantes = Database::fetchAll(
            "SELECT m.descripcion, 
                    (pr.qty_requerida - COALESCE(inv.qty_entregada, 0) - COALESCE(inv.qty_disponible, 0)) AS faltante
             FROM project_requirements pr
             JOIN materials m ON m.id = pr.material_id
             LEFT JOIN inventory inv ON inv.project_id = pr.project_id AND inv.material_id = pr.material_id
             WHERE pr.project_id = ?
               AND (pr.qty_requerida - COALESCE(inv.qty_entregada, 0) - COALESCE(inv.qty_disponible, 0)) > 0
             ORDER BY faltante DESC",
            [$projectId]
        );

        // Compras recientes
        $comprasRecientes = Database::fetchAll(
            "SELECT p.*, m.descripcion, m.sku
             FROM purchases p
             JOIN materials m ON m.id = p.material_id
             WHERE p.project_id = ? AND p.cancelado = 0
             ORDER BY p.fecha_compra DESC
             LIMIT 10",
            [$projectId]
        );

        // Entregas recientes
        $entregasRecientes = Database::fetchAll(
            "SELECT d.*, m.descripcion, m.sku
             FROM deliveries d
             JOIN materials m ON m.id = d.material_id
             WHERE d.project_id = ?
             ORDER BY d.fecha_entrega DESC
             LIMIT 10",
            [$projectId]
        );

        return [
            'project' => $project,
            'totals' => $totals ?: [],
            'faltantes' => $faltantes,
            'compras_recientes' => $comprasRecientes,
            'entregas_recientes' => $entregasRecientes
        ];
    }

    /**
     * Obtener historial completo de compras
     */
    public static function getPurchasesHistory(int $projectId): array
    {
        return Purchase::getByProject($projectId, true); // Incluye canceladas
    }

    /**
     * Obtener historial completo de entregas
     */
    public static function getDeliveriesHistory(int $projectId): array
    {
        return Delivery::getByProject($projectId);
    }
}

