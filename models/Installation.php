<?php
declare(strict_types=1);

/**
 * Modelo de Instalaciones
 */
class Installation
{
    /**
     * Obtener todas las instalaciones de un proyecto
     */
    public static function getByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT i.*, 
                    m.sku, m.descripcion, m.unidad,
                    u.nombre as instalador_nombre
             FROM installations i
             JOIN materials m ON m.id = i.material_id
             LEFT JOIN users u ON u.id = i.instalado_por
             WHERE i.project_id = ?
             ORDER BY i.fecha_instalacion DESC, i.created_at DESC",
            [$projectId]
        );
    }

    /**
     * Obtener cantidad instalada de un material en un proyecto
     */
    public static function getInstalledQty(int $projectId, int $materialId): float
    {
        $result = Database::fetchOne(
            "SELECT qty_instalada FROM inventory 
             WHERE project_id = ? AND material_id = ?",
            [$projectId, $materialId]
        );
        
        return $result ? (float)$result['qty_instalada'] : 0.0;
    }

    /**
     * Registrar nueva instalación (actualiza inventory)
     */
    public static function create(array $data): ?int
    {
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Validar que haya cantidad entregada disponible para instalar
            if ($data['qty_instalada'] <= 0) {
                throw new Exception("La cantidad instalada debe ser mayor a cero");
            }

            // Obtener cantidad entregada disponible
            $inventory = Database::fetchOne(
                "SELECT qty_entregada, qty_instalada FROM inventory 
                 WHERE project_id = ? AND material_id = ?",
                [$data['project_id'], $data['material_id']]
            );

            if (!$inventory) {
                throw new Exception("No existe inventario para este material en el proyecto");
            }

            $qtyEntregada = (float)$inventory['qty_entregada'];
            $qtyYaInstalada = (float)$inventory['qty_instalada'];
            $disponibleParaInstalar = $qtyEntregada - $qtyYaInstalada;

            if ($data['qty_instalada'] > $disponibleParaInstalar) {
                throw new Exception("La cantidad a instalar ({$data['qty_instalada']}) excede la disponible ({$disponibleParaInstalar})");
            }

            // Insertar registro en installations
            $stmt = $pdo->prepare("
                INSERT INTO installations 
                (project_id, material_id, qty_instalada, instalado_por, fecha_instalacion, ubicacion, comentarios)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['project_id'],
                $data['material_id'],
                $data['qty_instalada'],
                $data['instalado_por'],
                $data['fecha_instalacion'],
                $data['ubicacion'] ?? null,
                $data['comentarios'] ?? null
            ]);

            $installationId = (int)$pdo->lastInsertId();

            // Actualizar inventory: incrementar qty_instalada
            Database::query(
                "UPDATE inventory 
                 SET qty_instalada = qty_instalada + {$data['qty_instalada']}
                 WHERE project_id = ? AND material_id = ?",
                [$data['project_id'], $data['material_id']]
            );

            $pdo->commit();
            return $installationId;
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error creando instalación: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener totales de instalaciones por proyecto
     */
    public static function getTotals(int $projectId): array
    {
        $result = Database::fetchOne(
            "SELECT 
                COUNT(*) as total_instalaciones,
                COALESCE(SUM(qty_instalada), 0) as total_cantidad_instalada
             FROM installations
             WHERE project_id = ?",
            [$projectId]
        );
        
        return [
            'total_instalaciones' => (int)($result['total_instalaciones'] ?? 0),
            'total_cantidad_instalada' => (float)($result['total_cantidad_instalada'] ?? 0)
        ];
    }

    /**
     * Buscar instalación por ID
     */
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT i.*, m.sku, m.descripcion, m.unidad, u.nombre as instalador_nombre
             FROM installations i
             JOIN materials m ON m.id = i.material_id
             LEFT JOIN users u ON u.id = i.instalado_por
             WHERE i.id = ?",
            [$id]
        );
    }

    /**
     * Obtener instalaciones por material
     */
    public static function getByMaterial(int $projectId, int $materialId): array
    {
        return Database::fetchAll(
            "SELECT i.*, u.nombre as instalador_nombre
             FROM installations i
             LEFT JOIN users u ON u.id = i.instalado_por
             WHERE i.project_id = ? AND i.material_id = ?
             ORDER BY i.fecha_instalacion DESC",
            [$projectId, $materialId]
        );
    }
}

