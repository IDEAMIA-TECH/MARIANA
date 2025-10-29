<?php
declare(strict_types=1);

/**
 * Modelo de Tareas (Principales y Subtareas)
 */
class Task
{
    /**
     * Obtener todas las tareas principales de un proyecto (sin parent)
     */
    public static function getMainTasks(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT t.*, 
                    u_resp.nombre as responsable_nombre,
                    u_creador.nombre as creador_nombre,
                    COUNT(DISTINCT st.id) as subtareas_count,
                    COUNT(DISTINCT tr.id) as materiales_count
             FROM tasks t
             LEFT JOIN users u_resp ON u_resp.id = t.responsable_id
             LEFT JOIN users u_creador ON u_creador.id = t.created_by
             LEFT JOIN tasks st ON st.parent_id = t.id
             LEFT JOIN task_requirements tr ON tr.task_id = t.id
             WHERE t.project_id = ? AND t.parent_id IS NULL
             GROUP BY t.id
             ORDER BY t.orden ASC, t.created_at ASC",
            [$projectId]
        );
    }

    /**
     * Obtener subtareas de una tarea principal
     */
    public static function getSubtasks(int $parentTaskId): array
    {
        return Database::fetchAll(
            "SELECT t.*, 
                    u_resp.nombre as responsable_nombre,
                    u_creador.nombre as creador_nombre,
                    COUNT(DISTINCT tr.id) as materiales_count
             FROM tasks t
             LEFT JOIN users u_resp ON u_resp.id = t.responsable_id
             LEFT JOIN users u_creador ON u_creador.id = t.created_by
             LEFT JOIN task_requirements tr ON tr.task_id = t.id
             WHERE t.parent_id = ?
             GROUP BY t.id
             ORDER BY t.orden ASC, t.created_at ASC",
            [$parentTaskId]
        );
    }

    /**
     * Obtener tarea por ID con información completa
     */
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT t.*, 
                    u_resp.nombre as responsable_nombre,
                    u_creador.nombre as creador_nombre,
                    p.nombre as project_nombre
             FROM tasks t
             LEFT JOIN users u_resp ON u_resp.id = t.responsable_id
             LEFT JOIN users u_creador ON u_creador.id = t.created_by
             LEFT JOIN projects p ON p.id = t.project_id
             WHERE t.id = ?",
            [$id]
        );
    }

    /**
     * Obtener materiales asignados a una tarea
     */
    public static function getMaterials(int $taskId): array
    {
        return Database::fetchAll(
            "SELECT tr.*,
                    pr.qty_requerida as qty_total_proyecto,
                    m.sku, m.descripcion, m.unidad, m.categoria,
                    inv.qty_disponible, inv.qty_entregada,
                    (SELECT SUM(qty_asignada) FROM task_requirements WHERE requirement_id = tr.requirement_id) as qty_total_asignada
             FROM task_requirements tr
             JOIN project_requirements pr ON pr.id = tr.requirement_id
             JOIN materials m ON m.id = pr.material_id
             LEFT JOIN inventory inv ON inv.project_id = pr.project_id AND inv.material_id = pr.material_id
             WHERE tr.task_id = ?
             ORDER BY m.descripcion ASC",
            [$taskId]
        );
    }

    /**
     * Crear tarea (principal o subtarea)
     */
    public static function create(array $data): ?int
    {
        try {
            $pdo = Database::getConnection();
            
            // Obtener siguiente orden
            $nextOrder = 0;
            if ($data['parent_id']) {
                $result = Database::fetchOne(
                    "SELECT MAX(orden) + 1 as next_order FROM tasks WHERE parent_id = ?",
                    [$data['parent_id']]
                );
                $nextOrder = (int)($result['next_order'] ?? 0);
            } else {
                $result = Database::fetchOne(
                    "SELECT MAX(orden) + 1 as next_order FROM tasks WHERE project_id = ? AND parent_id IS NULL",
                    [$data['project_id']]
                );
                $nextOrder = (int)($result['next_order'] ?? 0);
            }

            $stmt = $pdo->prepare("
                INSERT INTO tasks 
                (project_id, parent_id, nombre, descripcion, estado, fecha_inicio, fecha_fin_estimada, responsable_id, created_by, orden)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['project_id'],
                $data['parent_id'] ?? null,
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['estado'] ?? 'pending',
                $data['fecha_inicio'] ?? null,
                $data['fecha_fin_estimada'] ?? null,
                $data['responsable_id'] ?? null,
                $data['created_by'],
                $nextOrder
            ]);

            return (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creando tarea: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar tarea
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $values = [];

            $allowed = ['nombre', 'descripcion', 'estado', 'fecha_inicio', 'fecha_fin_estimada', 'responsable_id', 'orden'];
            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }

            // Si se completa, registrar fecha_fin_real
            if (isset($data['estado']) && $data['estado'] === 'completed') {
                $fields[] = "fecha_fin_real = NOW()";
            } elseif (isset($data['estado']) && $data['estado'] !== 'completed') {
                $fields[] = "fecha_fin_real = NULL";
            }

            if (empty($fields)) {
                return false;
            }

            $values[] = $id;
            $sql = "UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = ?";
            
            Database::query($sql, $values);
            return true;
        } catch (Exception $e) {
            error_log("Error actualizando tarea: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar tarea (se eliminan subtareas en cascada)
     */
    public static function delete(int $id): bool
    {
        try {
            Database::query("DELETE FROM tasks WHERE id = ?", [$id]);
            return true;
        } catch (Exception $e) {
            error_log("Error eliminando tarea: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Asignar material a tarea
     */
    public static function assignMaterial(int $taskId, int $requirementId, float $qtyAsignada, string $comentarios = ''): bool
    {
        try {
            // Verificar que no se exceda la cantidad total del requerimiento
            $requirement = Database::fetchOne(
                "SELECT qty_requerida FROM project_requirements WHERE id = ?",
                [$requirementId]
            );

            if (!$requirement) {
                return false;
            }

            // Calcular cantidad ya asignada en otras tareas
            $alreadyAssigned = Database::fetchOne(
                "SELECT COALESCE(SUM(qty_asignada), 0) as total FROM task_requirements WHERE requirement_id = ? AND task_id != ?",
                [$requirementId, $taskId]
            );
            
            $available = (float)$requirement['qty_requerida'] - (float)($alreadyAssigned['total'] ?? 0);
            
            if ($qtyAsignada > $available) {
                throw new Exception("La cantidad asignada ($qtyAsignada) excede la disponible ($available) del requerimiento");
            }

            // Insertar o actualizar
            Database::query(
                "INSERT INTO task_requirements (task_id, requirement_id, qty_asignada, comentarios)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE qty_asignada = VALUES(qty_asignada), comentarios = VALUES(comentarios)",
                [$taskId, $requirementId, $qtyAsignada, $comentarios]
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error asignando material a tarea: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desasignar material de tarea
     */
    public static function unassignMaterial(int $taskId, int $requirementId): bool
    {
        try {
            Database::query(
                "DELETE FROM task_requirements WHERE task_id = ? AND requirement_id = ?",
                [$taskId, $requirementId]
            );
            return true;
        } catch (Exception $e) {
            error_log("Error desasignando material de tarea: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de tarea (avance, materiales, etc)
     */
    public static function getStats(int $taskId): array
    {
        $task = self::findById($taskId);
        if (!$task) {
            return [];
        }

        $materials = self::getMaterials($taskId);
        $subtasks = self::getSubtasks($taskId);

        // Calcular avance de materiales
        $totalMateriales = count($materials);
        $materialesEntregados = 0;
        $totalQtyAsignada = 0;
        $totalQtyEntregada = 0;

        foreach ($materials as $mat) {
            $totalQtyAsignada += (float)$mat['qty_asignada'];
            $totalQtyEntregada += (float)$mat['qty_entregada'];
            
            if ((float)$mat['qty_entregada'] >= (float)$mat['qty_asignada']) {
                $materialesEntregados++;
            }
        }

        // Calcular avance de subtareas
        $totalSubtareas = count($subtasks);
        $subtareasCompletadas = 0;
        foreach ($subtasks as $st) {
            if ($st['estado'] === 'completed') {
                $subtareasCompletadas++;
            }
        }

        return [
            'total_materiales' => $totalMateriales,
            'materiales_entregados' => $materialesEntregados,
            'pct_materiales' => $totalMateriales > 0 ? round(($materialesEntregados / $totalMateriales) * 100, 1) : 0,
            'total_qty_asignada' => $totalQtyAsignada,
            'total_qty_entregada' => $totalQtyEntregada,
            'pct_qty_entregada' => $totalQtyAsignada > 0 ? round(($totalQtyEntregada / $totalQtyAsignada) * 100, 1) : 0,
            'total_subtareas' => $totalSubtareas,
            'subtareas_completadas' => $subtareasCompletadas,
            'pct_subtareas' => $totalSubtareas > 0 ? round(($subtareasCompletadas / $totalSubtareas) * 100, 1) : 0
        ];
    }

    /**
     * Reordenar tareas
     */
    public static function reorder(int $projectId, array $taskIds): bool
    {
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            foreach ($taskIds as $order => $taskId) {
                Database::query(
                    "UPDATE tasks SET orden = ? WHERE id = ? AND project_id = ?",
                    [$order + 1, $taskId, $projectId]
                );
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error reordenando tareas: " . $e->getMessage());
            return false;
        }
    }
}

