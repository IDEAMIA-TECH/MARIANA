-- ============================================
-- AGREGAR COLUMNA DE INSTALACIONES
-- Ejecutar este script para agregar la funcionalidad de instalaciones
-- ============================================

-- Agregar columna qty_instalada a inventory
-- Nota: Si la columna ya existe, este comando puede fallar dependiendo de la versión de MySQL
-- En MySQL 8.0+, usar: ALTER TABLE inventory ADD COLUMN qty_instalada DECIMAL(12,2) DEFAULT 0 CHECK (qty_instalada >= 0) AFTER qty_entregada;
ALTER TABLE inventory 
ADD COLUMN qty_instalada DECIMAL(12,2) DEFAULT 0 CHECK (qty_instalada >= 0) AFTER qty_entregada;

-- Tabla para historial de instalaciones (opcional, para auditoría)
CREATE TABLE IF NOT EXISTS installations (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id       INT UNSIGNED NOT NULL,
  material_id      INT UNSIGNED NOT NULL,
  qty_instalada    DECIMAL(12,2) NOT NULL CHECK (qty_instalada > 0),
  instalado_por    INT UNSIGNED NOT NULL,
  fecha_instalacion DATE NOT NULL,
  ubicacion        VARCHAR(200) NULL,
  comentarios      TEXT NULL,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE RESTRICT,
  FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
  FOREIGN KEY (instalado_por) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_project (project_id),
  INDEX idx_material (material_id),
  INDEX idx_fecha_instalacion (fecha_instalacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

