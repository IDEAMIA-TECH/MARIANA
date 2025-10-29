-- ============================================
-- SISTEMA DE TAREAS Y SUBTAREAS
-- Agregar estas tablas a la base de datos existente
-- ============================================

-- ============================================
-- TABLA: tasks (Tareas principales y subtareas)
-- ============================================
CREATE TABLE IF NOT EXISTS tasks (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id       INT UNSIGNED NOT NULL,
  parent_id        INT UNSIGNED NULL COMMENT 'NULL = Tarea principal, ID = Subtarea',
  nombre           VARCHAR(200) NOT NULL,
  descripcion      TEXT NULL,
  estado           ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
  fecha_inicio     DATE NULL,
  fecha_fin_estimada DATE NULL,
  fecha_fin_real   DATE NULL,
  orden             INT DEFAULT 0 COMMENT 'Orden de visualización',
  responsable_id   INT UNSIGNED NULL COMMENT 'Usuario responsable',
  created_by        INT UNSIGNED NOT NULL,
  created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (responsable_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_project (project_id),
  INDEX idx_parent (parent_id),
  INDEX idx_estado (estado),
  INDEX idx_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: task_requirements
-- Relaciona tareas con requerimientos (materiales) del proyecto
-- ============================================
CREATE TABLE IF NOT EXISTS task_requirements (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id          INT UNSIGNED NOT NULL,
  requirement_id   INT UNSIGNED NOT NULL COMMENT 'ID de project_requirements',
  qty_asignada     DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Cantidad del requerimiento asignada a esta tarea',
  comentarios      TEXT NULL,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (requirement_id) REFERENCES project_requirements(id) ON DELETE CASCADE,
  UNIQUE KEY unique_task_requirement (task_id, requirement_id),
  INDEX idx_task (task_id),
  INDEX idx_requirement (requirement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

