<?php
declare(strict_types=1);

/**
 * Modelo de Compras
 */
class Purchase
{
    /**
     * Obtener todas las compras de un proyecto
     */
    public static function getByProject(int $projectId, bool $includeCancelled = false): array
    {
        $where = $includeCancelled ? "" : "AND p.cancelado = 0";
        
        return Database::fetchAll(
            "SELECT p.*, 
                    m.sku, m.descripcion, m.unidad,
                    u.nombre as comprador_nombre,
                    (p.qty_comprada * p.costo_unitario) as total
             FROM purchases p
             JOIN materials m ON m.id = p.material_id
             LEFT JOIN users u ON u.id = p.comprado_por
             WHERE p.project_id = ? $where
             ORDER BY p.fecha_compra DESC, p.created_at DESC",
            [$projectId]
        );
    }

    /**
     * Buscar compra por ID
     */
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT p.*, m.sku, m.descripcion, m.unidad, u.nombre as comprador_nombre
             FROM purchases p
             JOIN materials m ON m.id = p.material_id
             LEFT JOIN users u ON u.id = p.comprado_por
             WHERE p.id = ?",
            [$id]
        );
    }

    /**
     * Registrar nueva compra (con actualización de inventario y costos)
     */
    public static function create(array $data): ?int
    {
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Validaciones
            if ($data['qty_comprada'] <= 0 || $data['costo_unitario'] < 0) {
                throw new Exception("Cantidad y costo deben ser válidos");
            }

            // Verificar que el requerimiento existe
            $requirement = Database::fetchOne(
                "SELECT id FROM project_requirements 
                 WHERE project_id = ? AND material_id = ?",
                [$data['project_id'], $data['material_id']]
            );

            if (!$requirement) {
                throw new Exception("El material debe estar en los requerimientos del proyecto");
            }

            // Insertar compra
            $stmt = $pdo->prepare("
                INSERT INTO purchases 
                (project_id, material_id, qty_comprada, costo_unitario, moneda, tipo_cambio, proveedor, numero_factura, comprado_por, fecha_compra)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['project_id'],
                $data['material_id'],
                $data['qty_comprada'],
                $data['costo_unitario'],
                $data['moneda'] ?? 'MXN',
                $data['tipo_cambio'] ?? null,
                $data['proveedor'] ?? null,
                $data['numero_factura'] ?? null,
                $data['comprado_por'],
                $data['fecha_compra']
            ]);

            $purchaseId = (int)$pdo->lastInsertId();
            $totalCosto = $data['qty_comprada'] * $data['costo_unitario'];

            // Actualizar inventario: aumentar disponible
            $pdo->exec("
                INSERT INTO inventory (project_id, material_id, qty_disponible, qty_entregada)
                VALUES ({$data['project_id']}, {$data['material_id']}, {$data['qty_comprada']}, 0)
                ON DUPLICATE KEY UPDATE 
                    qty_disponible = qty_disponible + {$data['qty_comprada']},
                    last_update = NOW()
            ");

            // Actualizar material_cost_stats
            // Primero obtener valores actuales
            $current = Database::fetchOne(
                "SELECT total_qty_comprada, total_costo 
                 FROM material_cost_stats 
                 WHERE project_id = ? AND material_id = ?",
                [$data['project_id'], $data['material_id']]
            );

            if ($current) {
                $newTotalQty = $current['total_qty_comprada'] + $data['qty_comprada'];
                $newTotalCosto = $current['total_costo'] + $totalCosto;
                $newAverage = $newTotalQty > 0 ? ($newTotalCosto / $newTotalQty) : 0;

                $pdo->exec("
                    UPDATE material_cost_stats
                    SET total_qty_comprada = $newTotalQty,
                        total_costo = $newTotalCosto,
                        costo_promedio_calc = $newAverage,
                        last_update = NOW()
                    WHERE project_id = {$data['project_id']} AND material_id = {$data['material_id']}
                ");
            } else {
                // Crear registro si no existe
                $pdo->exec("
                    INSERT INTO material_cost_stats 
                    (project_id, material_id, total_qty_comprada, total_costo, costo_promedio_calc)
                    VALUES ({$data['project_id']}, {$data['material_id']}, {$data['qty_comprada']}, $totalCosto, {$data['costo_unitario']})
                ");
            }

            $pdo->commit();
            return $purchaseId;

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error creando compra: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancelar compra (revertir cambios)
     */
    public static function cancel(int $id, int $userId, string $motivo = ''): bool
    {
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $purchase = self::findById($id);
            if (!$purchase || $purchase['cancelado']) {
                $pdo->rollBack();
                return false;
            }

            // Marcar como cancelada
            Database::query(
                "UPDATE purchases 
                 SET cancelado = 1, motivo_cancelacion = ?, cancelado_por = ?, fecha_cancelacion = NOW()
                 WHERE id = ?",
                [$motivo, $userId, $id]
            );

            // Revertir inventario: disminuir disponible
            $pdo->exec("
                UPDATE inventory
                SET qty_disponible = GREATEST(0, qty_disponible - {$purchase['qty_comprada']}),
                    last_update = NOW()
                WHERE project_id = {$purchase['project_id']} AND material_id = {$purchase['material_id']}
            ");

            // Recalcular costos (excluyendo la compra cancelada)
            $remaining = Database::fetchAll(
                "SELECT qty_comprada, costo_unitario 
                 FROM purchases 
                 WHERE project_id = ? AND material_id = ? AND cancelado = 0",
                [$purchase['project_id'], $purchase['material_id']]
            );

            $newTotalQty = 0;
            $newTotalCosto = 0;
            
            foreach ($remaining as $p) {
                $newTotalQty += $p['qty_comprada'];
                $newTotalCosto += ($p['qty_comprada'] * $p['costo_unitario']);
            }

            $newAverage = $newTotalQty > 0 ? ($newTotalCosto / $newTotalQty) : 0;

            $pdo->exec("
                UPDATE material_cost_stats
                SET total_qty_comprada = $newTotalQty,
                    total_costo = $newTotalCosto,
                    costo_promedio_calc = $newAverage,
                    last_update = NOW()
                WHERE project_id = {$purchase['project_id']} AND material_id = {$purchase['material_id']}
            ");

            $pdo->commit();
            return true;

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error cancelando compra: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener última compra de un material en un proyecto
     */
    public static function getLastPurchase(int $projectId, int $materialId): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM purchases 
             WHERE project_id = ? AND material_id = ? AND cancelado = 0
             ORDER BY fecha_compra DESC, created_at DESC
             LIMIT 1",
            [$projectId, $materialId]
        );
    }

    /**
     * Obtener totales de compras por proyecto
     */
    public static function getTotals(int $projectId): array
    {
        $result = Database::fetchOne(
            "SELECT 
                COUNT(*) as total_compras,
                COALESCE(SUM(qty_comprada), 0) as total_cantidad,
                COALESCE(SUM(qty_comprada * costo_unitario), 0) as total_invertido
             FROM purchases
             WHERE project_id = ? AND cancelado = 0",
            [$projectId]
        );
        
        return [
            'total_compras' => (int)($result['total_compras'] ?? 0),
            'total_cantidad' => (float)($result['total_cantidad'] ?? 0),
            'total_invertido' => (float)($result['total_invertido'] ?? 0)
        ];
    }
}

