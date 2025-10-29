-- ============================================
-- Sistema de Control de Material para Proyectos
-- Base de datos MySQL 8.0+
-- Base de datos: ideamiadev_marina
-- ============================================

-- NOTA: No creamos la base de datos porque ya existe
-- USE ideamiadev_marina;

-- ============================================
-- TABLA: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(100) NOT NULL,
  email            VARCHAR(120) NOT NULL UNIQUE,
  password_hash    VARCHAR(255) NOT NULL,
  rol              ENUM('admin','pm','almacen','viewer') DEFAULT 'viewer',
  activo           TINYINT(1) DEFAULT 1,
  last_login       DATETIME NULL,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: projects
-- ============================================
CREATE TABLE IF NOT EXISTS projects (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(150) NOT NULL,
  descripcion      TEXT,
  ubicacion        VARCHAR(200),
  estado           ENUM('planning','active','on_hold','completed') DEFAULT 'planning',
  fecha_inicio     DATE NULL,
  fecha_fin        DATE NULL,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by       INT UNSIGNED NOT NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_estado (estado),
  INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: materials
-- ============================================
CREATE TABLE IF NOT EXISTS materials (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku              VARCHAR(50) NOT NULL UNIQUE,
  descripcion      VARCHAR(200) NOT NULL,
  unidad           VARCHAR(20) NOT NULL,
  categoria        VARCHAR(50),
  activo           TINYINT(1) DEFAULT 1,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sku (sku),
  INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: project_requirements
-- ============================================
CREATE TABLE IF NOT EXISTS project_requirements (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id       INT UNSIGNED NOT NULL,
  material_id      INT UNSIGNED NOT NULL,
  qty_requerida    DECIMAL(12,2) NOT NULL CHECK (qty_requerida > 0),
  comentarios      TEXT,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
  UNIQUE KEY unique_project_material (project_id, material_id),
  INDEX idx_project (project_id),
  INDEX idx_material (material_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: inventory
-- ============================================
CREATE TABLE IF NOT EXISTS inventory (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id       INT UNSIGNED NOT NULL,
  material_id      INT UNSIGNED NOT NULL,
  qty_disponible   DECIMAL(12,2) DEFAULT 0 CHECK (qty_disponible >= 0),
  qty_entregada    DECIMAL(12,2) DEFAULT 0 CHECK (qty_entregada >= 0),
  last_update      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
  UNIQUE KEY unique_inventory (project_id, material_id),
  INDEX idx_project (project_id),
  INDEX idx_material (material_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: purchases
-- Historial de compras con costo unitario y proveedor
-- ============================================
CREATE TABLE IF NOT EXISTS purchases (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id       INT UNSIGNED NOT NULL,
  material_id      INT UNSIGNED NOT NULL,
  qty_comprada     DECIMAL(12,2) NOT NULL CHECK (qty_comprada > 0),
  costo_unitario   DECIMAL(12,2) NOT NULL CHECK (costo_unitario >= 0),
  moneda           VARCHAR(10) DEFAULT 'MXN',
  proveedor        VARCHAR(150),
  numero_factura   VARCHAR(50),
  comprado_por     INT UNSIGNED NOT NULL,
  fecha_compra     DATE NOT NULL,
  cancelado        TINYINT(1) DEFAULT 0,
  motivo_cancelacion TEXT NULL,
  cancelado_por    INT UNSIGNED NULL,
  fecha_cancelacion DATETIME NULL,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE RESTRICT,
  FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
  FOREIGN KEY (comprado_por) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (cancelado_por) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_project (project_id),
  INDEX idx_material (material_id),
  INDEX idx_fecha_compra (fecha_compra),
  INDEX idx_cancelado (cancelado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: deliveries
-- ============================================
CREATE TABLE IF NOT EXISTS deliveries (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id       INT UNSIGNED NOT NULL,
  material_id      INT UNSIGNED NOT NULL,
  qty_entregada    DECIMAL(12,2) NOT NULL CHECK (qty_entregada > 0),
  entregado_a      VARCHAR(120) NOT NULL,
  entregado_por    INT UNSIGNED NOT NULL,
  fecha_entrega    DATE NOT NULL,
  comentarios      TEXT,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE RESTRICT,
  FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
  FOREIGN KEY (entregado_por) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_project (project_id),
  INDEX idx_material (material_id),
  INDEX idx_fecha_entrega (fecha_entrega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: material_cost_stats
-- Cálculo agregado de costos promedio
-- ============================================
CREATE TABLE IF NOT EXISTS material_cost_stats (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id           INT UNSIGNED NOT NULL,
  material_id          INT UNSIGNED NOT NULL,
  total_qty_comprada   DECIMAL(12,2) DEFAULT 0,
  total_costo          DECIMAL(14,2) DEFAULT 0,
  costo_promedio_calc  DECIMAL(12,2) DEFAULT 0,
  last_update          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
  UNIQUE KEY unique_stats (project_id, material_id),
  INDEX idx_project (project_id),
  INDEX idx_material (material_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: activity_log (Opcional - para auditoría)
-- ============================================
CREATE TABLE IF NOT EXISTS activity_log (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id           INT UNSIGNED NULL,
  action            VARCHAR(50) NOT NULL,
  table_name        VARCHAR(50),
  record_id         INT UNSIGNED,
  old_values        JSON NULL,
  new_values        JSON NULL,
  ip_address        VARCHAR(45),
  user_agent        TEXT,
  created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user (user_id),
  INDEX idx_action (action),
  INDEX idx_table (table_name),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS INICIALES
-- ============================================

-- Usuario administrador por defecto (password: admin123)
-- Nota: Si el usuario ya existe, se ignorará por el UNIQUE constraint
INSERT IGNORE INTO users (id, nombre, email, password_hash, rol) VALUES
(1, 'Administrador', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Categorías de materiales comunes
INSERT IGNORE INTO materials (sku, descripcion, unidad, categoria) VALUES
('CAB-CAT6-305M', 'Cable UTP Cat 6 - 305m', 'Rollo', 'Cableado'),
('CAB-FIB-50M', 'Cable Fibra Óptica 50m', 'Rollo', 'Cableado'),
('AP-WIFI6-IND', 'Access Point WiFi 6 Industrial', 'Pieza', 'Equipos'),
('SW-24PORT-POE', 'Switch 24 Puertos PoE', 'Pieza', 'Equipos'),
('PP-PATCH-24', 'Patch Panel 24 Puertos', 'Pieza', 'Cableado'),
('RJ45-CONN', 'Conector RJ45', 'Pieza', 'Conectores');

