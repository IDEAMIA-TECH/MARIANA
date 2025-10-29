<?php
declare(strict_types=1);

/**
 * Modelo de Material
 */
class Material
{
    /**
     * Obtener todos los materiales activos
     */
    public static function all(?string $search = null, ?string $categoria = null, bool $includeInactive = false): array
    {
        $conditions = [];
        $params = [];

        if (!$includeInactive) {
            $conditions[] = "m.activo = 1";
        }

        if ($search) {
            $conditions[] = "(m.sku LIKE ? OR m.descripcion LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($categoria) {
            $conditions[] = "m.categoria = ?";
            $params[] = $categoria;
        }

        $where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        return Database::fetchAll(
            "SELECT m.*, 
                    COUNT(DISTINCT pr.id) as usado_en_proyectos
             FROM materials m
             LEFT JOIN project_requirements pr ON pr.material_id = m.id
             $where
             GROUP BY m.id
             ORDER BY m.categoria ASC, m.descripcion ASC",
            $params
        );
    }

    /**
     * Buscar material por ID
     */
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM materials WHERE id = ?",
            [$id]
        );
    }

    /**
     * Buscar material por SKU
     */
    public static function findBySku(string $sku): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM materials WHERE sku = ?",
            [$sku]
        );
    }

    /**
     * Obtener categorías únicas
     */
    public static function getCategorias(): array
    {
        $result = Database::fetchAll(
            "SELECT DISTINCT categoria FROM materials WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC"
        );
        return array_column($result, 'categoria');
    }

    /**
     * Crear nuevo material
     */
    public static function create(array $data): ?int
    {
        try {
            // Verificar que el SKU no exista
            if (self::findBySku($data['sku'])) {
                return null; // SKU duplicado
            }

            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO materials (sku, descripcion, unidad, categoria)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                strtoupper(trim($data['sku'])),
                trim($data['descripcion']),
                trim($data['unidad']),
                !empty($data['categoria']) ? trim($data['categoria']) : null
            ]);
            
            return (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creando material: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar material
     */
    public static function update(int $id, array $data): bool
    {
        try {
            // Verificar que el SKU no esté en uso por otro material
            $existing = self::findBySku($data['sku']);
            if ($existing && $existing['id'] != $id) {
                return false; // SKU ya existe en otro material
            }

            Database::query(
                "UPDATE materials 
                 SET sku = ?, descripcion = ?, unidad = ?, categoria = ?, activo = ?
                 WHERE id = ?",
                [
                    strtoupper(trim($data['sku'])),
                    trim($data['descripcion']),
                    trim($data['unidad']),
                    !empty($data['categoria']) ? trim($data['categoria']) : null,
                    isset($data['activo']) ? (int)$data['activo'] : 1,
                    $id
                ]
            );
            return true;
        } catch (Exception $e) {
            error_log("Error actualizando material: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activar/Desactivar material
     */
    public static function toggleActive(int $id): bool
    {
        try {
            $material = self::findById($id);
            if (!$material) {
                return false;
            }

            Database::query(
                "UPDATE materials SET activo = ? WHERE id = ?",
                [$material['activo'] ? 0 : 1, $id]
            );
            return true;
        } catch (Exception $e) {
            error_log("Error cambiando estado de material: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar material (solo si no está en uso)
     */
    public static function delete(int $id): bool
    {
        try {
            // Verificar si está en uso en algún proyecto
            $inUse = Database::fetchOne(
                "SELECT COUNT(*) as total FROM project_requirements WHERE material_id = ?",
                [$id]
            );
            
            if ($inUse && $inUse['total'] > 0) {
                return false; // No se puede eliminar si está en uso
            }
            
            Database::query("DELETE FROM materials WHERE id = ?", [$id]);
            return true;
        } catch (Exception $e) {
            error_log("Error eliminando material: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar materiales para autocomplete
     */
    public static function search(string $term, ?int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT id, sku, descripcion, unidad, categoria 
             FROM materials 
             WHERE activo = 1 AND (sku LIKE ? OR descripcion LIKE ?)
             ORDER BY descripcion ASC
             LIMIT ?",
            ["%$term%", "%$term%", $limit]
        );
    }
}

