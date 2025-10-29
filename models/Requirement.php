<?php
declare(strict_types=1);

/**
 * Modelo de Requerimientos de Proyecto
 */
class Requirement
{
    /**
     * Obtener todos los requerimientos de un proyecto
     */
    public static function getByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT pr.*, 
                    m.sku, m.descripcion, m.unidad, m.categoria,
                    inv.qty_disponible, inv.qty_entregada,
                    stats.total_qty_comprada, stats.total_costo, stats.costo_promedio_calc
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
     * Buscar requerimiento por ID
     */
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT pr.*, m.sku, m.descripcion, m.unidad
             FROM project_requirements pr
             JOIN materials m ON m.id = pr.material_id
             WHERE pr.id = ?",
            [$id]
        );
    }

    /**
     * Verificar si existe requerimiento para proyecto + material
     */
    public static function exists(int $projectId, int $materialId): bool
    {
        $result = Database::fetchOne(
            "SELECT id FROM project_requirements 
             WHERE project_id = ? AND material_id = ?",
            [$projectId, $materialId]
        );
        return $result !== null;
    }

    /**
     * Buscar requerimiento por proyecto y material
     */
    public static function findByProjectAndMaterial(int $projectId, int $materialId): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM project_requirements 
             WHERE project_id = ? AND material_id = ?",
            [$projectId, $materialId]
        );
    }

    /**
     * Crear requerimiento (con inicialización de inventory y cost_stats)
     */
    public static function create(array $data): ?int
    {
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Verificar que no exista ya
            if (self::exists($data['project_id'], $data['material_id'])) {
                $pdo->rollBack();
                return null;
            }

            // Validar cantidad
            if ($data['qty_requerida'] <= 0) {
                $pdo->rollBack();
                return null;
            }

            // Insertar requerimiento
            $stmt = $pdo->prepare("
                INSERT INTO project_requirements (project_id, material_id, qty_requerida, comentarios)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['project_id'],
                $data['material_id'],
                $data['qty_requerida'],
                $data['comentarios'] ?? null
            ]);

            $requirementId = $pdo->lastInsertId();

            // Crear registro inicial en inventory
            $pdo->exec("
                INSERT INTO inventory (project_id, material_id, qty_disponible, qty_entregada)
                VALUES ({$data['project_id']}, {$data['material_id']}, 0, 0)
                ON DUPLICATE KEY UPDATE project_id = project_id
            ");

            // Crear registro inicial en material_cost_stats
            $pdo->exec("
                INSERT INTO material_cost_stats (project_id, material_id, total_qty_comprada, total_costo, costo_promedio_calc)
                VALUES ({$data['project_id']}, {$data['material_id']}, 0, 0, 0)
                ON DUPLICATE KEY UPDATE project_id = project_id
            ");

            $pdo->commit();
            return (int)$requirementId;

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error creando requerimiento: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar requerimiento
     */
    public static function update(int $id, array $data): bool
    {
        try {
            Database::query(
                "UPDATE project_requirements 
                 SET qty_requerida = ?, comentarios = ?
                 WHERE id = ?",
                [
                    $data['qty_requerida'],
                    $data['comentarios'] ?? null,
                    $id
                ]
            );
            return true;
        } catch (Exception $e) {
            error_log("Error actualizando requerimiento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar requerimiento (solo si no tiene compras ni entregas)
     */
    public static function delete(int $id): bool
    {
        try {
            $requirement = self::findById($id);
            if (!$requirement) {
                return false;
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Verificar si tiene compras
            $purchases = Database::fetchOne(
                "SELECT COUNT(*) as total FROM purchases 
                 WHERE project_id = ? AND material_id = ? AND cancelado = 0",
                [$requirement['project_id'], $requirement['material_id']]
            );

            if ($purchases && $purchases['total'] > 0) {
                $pdo->rollBack();
                return false;
            }

            // Verificar si tiene entregas
            $deliveries = Database::fetchOne(
                "SELECT COUNT(*) as total FROM deliveries 
                 WHERE project_id = ? AND material_id = ?",
                [$requirement['project_id'], $requirement['material_id']]
            );

            if ($deliveries && $deliveries['total'] > 0) {
                $pdo->rollBack();
                return false;
            }

            // Eliminar requerimiento (se eliminarán en cascada: inventory, cost_stats)
            Database::query("DELETE FROM project_requirements WHERE id = ?", [$id]);

            $pdo->commit();
            return true;

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error eliminando requerimiento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de requerimiento
     */
    public static function getStats(int $projectId, int $materialId): array
    {
        $stats = Database::fetchOne(
            "SELECT 
                pr.qty_requerida,
                COALESCE(inv.qty_disponible, 0) as qty_disponible,
                COALESCE(inv.qty_entregada, 0) as qty_entregada,
                COALESCE(stats.total_qty_comprada, 0) as total_comprada,
                COALESCE(stats.total_costo, 0) as total_costo,
                COALESCE(stats.costo_promedio_calc, 0) as costo_promedio,
                ROUND((COALESCE(inv.qty_entregada, 0) / pr.qty_requerida) * 100, 2) as pct_entregado,
                ROUND((COALESCE(inv.qty_disponible, 0) / pr.qty_requerida) * 100, 2) as pct_disponible,
                ROUND(((pr.qty_requerida - COALESCE(inv.qty_entregada, 0) - COALESCE(inv.qty_disponible, 0)) / pr.qty_requerida) * 100, 2) as pct_faltante
             FROM project_requirements pr
             LEFT JOIN inventory inv ON inv.project_id = pr.project_id AND inv.material_id = pr.material_id
             LEFT JOIN material_cost_stats stats ON stats.project_id = pr.project_id AND stats.material_id = pr.material_id
             WHERE pr.project_id = ? AND pr.material_id = ?",
            [$projectId, $materialId]
        );

        return $stats ?: [];
    }
}

