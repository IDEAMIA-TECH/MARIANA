<?php
declare(strict_types=1);

/**
 * Modelo de Entregas
 */
class Delivery
{
    /**
     * Obtener todas las entregas de un proyecto
     */
    public static function getByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT d.*, 
                    m.sku, m.descripcion, m.unidad,
                    u.nombre as entregador_nombre
             FROM deliveries d
             JOIN materials m ON m.id = d.material_id
             LEFT JOIN users u ON u.id = d.entregado_por
             WHERE d.project_id = ?
             ORDER BY d.fecha_entrega DESC, d.created_at DESC",
            [$projectId]
        );
    }

    /**
     * Buscar entrega por ID
     */
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT d.*, m.sku, m.descripcion, m.unidad, u.nombre as entregador_nombre
             FROM deliveries d
             JOIN materials m ON m.id = d.material_id
             LEFT JOIN users u ON u.id = d.entregado_por
             WHERE d.id = ?",
            [$id]
        );
    }

    /**
     * Obtener inventario disponible de un material en un proyecto
     */
    public static function getAvailableInventory(int $projectId, int $materialId): float
    {
        $result = Database::fetchOne(
            "SELECT qty_disponible FROM inventory 
             WHERE project_id = ? AND material_id = ?",
            [$projectId, $materialId]
        );
        
        return $result ? (float)$result['qty_disponible'] : 0.0;
    }

    /**
     * Registrar nueva entrega (con actualización de inventario)
     */
    public static function create(array $data): ?int
    {
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Validaciones
            if ($data['qty_entregada'] <= 0) {
                throw new Exception("La cantidad entregada debe ser mayor a cero");
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

            // Verificar inventario disponible
            $available = self::getAvailableInventory($data['project_id'], $data['material_id']);
            
            if ($available < $data['qty_entregada']) {
                $pdo->rollBack();
                throw new Exception("No hay suficiente inventario disponible. Disponible: $available");
            }

            // Insertar entrega
            $stmt = $pdo->prepare("
                INSERT INTO deliveries 
                (project_id, material_id, qty_entregada, entregado_a, entregado_por, fecha_entrega, comentarios)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['project_id'],
                $data['material_id'],
                $data['qty_entregada'],
                $data['entregado_a'],
                $data['entregado_por'],
                $data['fecha_entrega'],
                $data['comentarios'] ?? null
            ]);

            $deliveryId = (int)$pdo->lastInsertId();

            // Actualizar inventario: disminuir disponible, aumentar entregado
            $pdo->exec("
                UPDATE inventory
                SET qty_disponible = qty_disponible - {$data['qty_entregada']},
                    qty_entregada = qty_entregada + {$data['qty_entregada']},
                    last_update = NOW()
                WHERE project_id = {$data['project_id']} AND material_id = {$data['material_id']}
            ");

            // Verificar que no quede inventario negativo (por seguridad)
            $check = Database::fetchOne(
                "SELECT qty_disponible FROM inventory 
                 WHERE project_id = ? AND material_id = ?",
                [$data['project_id'], $data['material_id']]
            );

            if ($check && $check['qty_disponible'] < 0) {
                throw new Exception("Error: El inventario disponible quedó negativo. Revirtiendo operación.");
            }

            $pdo->commit();
            return $deliveryId;

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error creando entrega: " . $e->getMessage());
            throw $e; // Re-lanzar para que el controlador pueda mostrar el mensaje
        }
    }

    /**
     * Obtener totales de entregas por proyecto
     */
    public static function getTotals(int $projectId): array
    {
        $result = Database::fetchOne(
            "SELECT 
                COUNT(*) as total_entregas,
                COALESCE(SUM(qty_entregada), 0) as total_cantidad_entregada
             FROM deliveries
             WHERE project_id = ?",
            [$projectId]
        );
        
        return [
            'total_entregas' => (int)($result['total_entregas'] ?? 0),
            'total_cantidad_entregada' => (float)($result['total_cantidad_entregada'] ?? 0)
        ];
    }

    /**
     * Obtener entregas agrupadas por material
     */
    public static function getByMaterial(int $projectId, int $materialId): array
    {
        return Database::fetchAll(
            "SELECT d.*, u.nombre as entregador_nombre
             FROM deliveries d
             LEFT JOIN users u ON u.id = d.entregado_por
             WHERE d.project_id = ? AND d.material_id = ?
             ORDER BY d.fecha_entrega DESC",
            [$projectId, $materialId]
        );
    }

    /**
     * Obtener total entregado de un material en un proyecto
     */
    public static function getTotalDelivered(int $projectId, int $materialId): float
    {
        $result = Database::fetchOne(
            "SELECT SUM(qty_entregada) as total FROM deliveries 
             WHERE project_id = ? AND material_id = ?",
            [$projectId, $materialId]
        );
        
        return $result ? (float)$result['total'] : 0.0;
    }

    /**
     * Actualizar campos permitidos y recalcular inventario.
     * No permite cambiar el material.
     */
    public static function updateFields(int $id, array $fields, array $original): bool
    {
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $allowed = ['qty_entregada', 'entregado_a', 'fecha_entrega', 'comentarios'];
            $setParts = [];
            $params = [];
            foreach ($allowed as $k) {
                if (array_key_exists($k, $fields)) {
                    $setParts[] = "$k = ?";
                    $params[] = $fields[$k];
                }
            }
            if (empty($setParts)) {
                $pdo->rollBack();
                return false;
            }

            $params[] = $id;
            Database::query("UPDATE deliveries SET " . implode(', ', $setParts) . " WHERE id = ?", $params);

            // Recalcular inventario para este material en este proyecto
            $projectId = (int)$original['project_id'];
            $materialId = (int)$original['material_id'];

            // Obtener todas las entregas para recalcular
            $allDeliveries = Database::fetchAll(
                "SELECT qty_entregada FROM deliveries WHERE project_id = ? AND material_id = ?",
                [$projectId, $materialId]
            );

            $totalEntregada = 0.0;
            foreach ($allDeliveries as $d) {
                $totalEntregada += (float)$d['qty_entregada'];
            }

            // Obtener total comprada (de purchases)
            $totalComprada = Database::fetchOne(
                "SELECT COALESCE(SUM(qty_comprada), 0) as total FROM purchases WHERE project_id = ? AND material_id = ? AND cancelado = 0",
                [$projectId, $materialId]
            );
            $totalComp = (float)($totalComprada['total'] ?? 0);

            // Inventario disponible = comprado - entregado
            $disponible = max(0, $totalComp - $totalEntregada);

            // Actualizar inventory
            Database::query(
                "INSERT INTO inventory (project_id, material_id, qty_disponible, qty_entregada)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE qty_disponible = VALUES(qty_disponible), qty_entregada = VALUES(qty_entregada), last_update = NOW()",
                [$projectId, $materialId, $disponible, $totalEntregada]
            );

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error actualizando entrega: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Borrar entrega y revertir cambios en inventario
     */
    public static function delete(int $id): bool
    {
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $delivery = self::findById($id);
            if (!$delivery) {
                $pdo->rollBack();
                return false;
            }

            $projectId = (int)$delivery['project_id'];
            $materialId = (int)$delivery['material_id'];
            $qtyEntregada = (float)$delivery['qty_entregada'];

            // Borrar la entrega
            Database::query("DELETE FROM deliveries WHERE id = ?", [$id]);

            // Recalcular inventario
            $allDeliveries = Database::fetchAll(
                "SELECT qty_entregada FROM deliveries WHERE project_id = ? AND material_id = ?",
                [$projectId, $materialId]
            );

            $totalEntregada = 0.0;
            foreach ($allDeliveries as $d) {
                $totalEntregada += (float)$d['qty_entregada'];
            }

            // Obtener total comprada
            $totalComprada = Database::fetchOne(
                "SELECT COALESCE(SUM(qty_comprada), 0) as total FROM purchases WHERE project_id = ? AND material_id = ? AND cancelado = 0",
                [$projectId, $materialId]
            );
            $totalComp = (float)($totalComprada['total'] ?? 0);

            // Inventario disponible = comprado - entregado
            $disponible = max(0, $totalComp - $totalEntregada);

            // Actualizar inventory
            Database::query(
                "INSERT INTO inventory (project_id, material_id, qty_disponible, qty_entregada)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE qty_disponible = VALUES(qty_disponible), qty_entregada = VALUES(qty_entregada), last_update = NOW()",
                [$projectId, $materialId, $disponible, $totalEntregada]
            );

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error borrando entrega: ' . $e->getMessage());
            return false;
        }
    }
}

