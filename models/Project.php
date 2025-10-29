<?php
declare(strict_types=1);

/**
 * Modelo de Proyecto
 */
class Project
{
    /**
     * Obtener todos los proyectos
     */
    public static function all(?string $search = null): array
    {
        if ($search) {
            return Database::fetchAll(
                "SELECT p.*, u.nombre as created_by_name 
                 FROM projects p
                 LEFT JOIN users u ON u.id = p.created_by
                 WHERE p.nombre LIKE ? OR p.ubicacion LIKE ?
                 ORDER BY p.created_at DESC",
                ["%$search%", "%$search%"]
            );
        }
        
        return Database::fetchAll(
            "SELECT p.*, u.nombre as created_by_name 
             FROM projects p
             LEFT JOIN users u ON u.id = p.created_by
             ORDER BY p.created_at DESC"
        );
    }

    /**
     * Buscar proyecto por ID
     */
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT p.*, u.nombre as created_by_name 
             FROM projects p
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.id = ?",
            [$id]
        );
    }

    /**
     * Crear nuevo proyecto
     */
    public static function create(array $data): ?int
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO projects (nombre, descripcion, ubicacion, estado, fecha_inicio, fecha_fin, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['ubicacion'] ?? null,
                $data['estado'] ?? PROJECT_PLANNING,
                $data['fecha_inicio'] ?? null,
                $data['fecha_fin'] ?? null,
                $data['created_by']
            ]);
            
            return (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creando proyecto: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar proyecto
     */
    public static function update(int $id, array $data): bool
    {
        try {
            Database::query(
                "UPDATE projects 
                 SET nombre = ?, descripcion = ?, ubicacion = ?, 
                     estado = ?, fecha_inicio = ?, fecha_fin = ?
                 WHERE id = ?",
                [
                    $data['nombre'],
                    $data['descripcion'] ?? null,
                    $data['ubicacion'] ?? null,
                    $data['estado'] ?? PROJECT_PLANNING,
                    $data['fecha_inicio'] ?? null,
                    $data['fecha_fin'] ?? null,
                    $id
                ]
            );
            return true;
        } catch (Exception $e) {
            error_log("Error actualizando proyecto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar proyecto (solo si no tiene compras o entregas)
     */
    public static function delete(int $id): bool
    {
        try {
            // Verificar si tiene compras
            $purchases = Database::fetchOne(
                "SELECT COUNT(*) as total FROM purchases WHERE project_id = ?",
                [$id]
            );
            
            if ($purchases && $purchases['total'] > 0) {
                return false; // No se puede eliminar si tiene compras
            }
            
            // Eliminar (se eliminarán en cascada: requirements, inventory, cost_stats)
            Database::query("DELETE FROM projects WHERE id = ?", [$id]);
            return true;
        } catch (Exception $e) {
            error_log("Error eliminando proyecto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener proyectos del usuario actual
     */
    public static function getUserProjects(int $userId, bool $isAdmin = false): array
    {
        if ($isAdmin) {
            return self::all();
        }
        
        return Database::fetchAll(
            "SELECT p.*, u.nombre as created_by_name 
             FROM projects p
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.created_by = ?
             ORDER BY p.created_at DESC",
            [$userId]
        );
    }

    /**
     * Obtener estadísticas básicas del proyecto
     */
    public static function getStats(int $projectId): array
    {
        $stats = Database::fetchOne(
            "SELECT 
                COUNT(DISTINCT pr.id) as total_materiales,
                COUNT(DISTINCT CASE WHEN inv.qty_entregada > 0 THEN pr.id END) as materiales_entregados,
                SUM(pr.qty_requerida) as total_requerido,
                SUM(inv.qty_entregada) as total_entregado,
                SUM(inv.qty_disponible) as total_disponible,
                SUM(mcs.total_costo) as total_invertido
             FROM project_requirements pr
             LEFT JOIN inventory inv ON inv.project_id = pr.project_id AND inv.material_id = pr.material_id
             LEFT JOIN material_cost_stats mcs ON mcs.project_id = pr.project_id AND mcs.material_id = pr.material_id
             WHERE pr.project_id = ?",
            [$projectId]
        );
        
        return $stats ?: [
            'total_materiales' => 0,
            'materiales_entregados' => 0,
            'total_requerido' => 0,
            'total_entregado' => 0,
            'total_disponible' => 0,
            'total_invertido' => 0
        ];
    }
}

