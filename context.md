# 📦 Sistema de Control de Material para Proyectos (PHP + MySQL)

## 📋 Tabla de Contenidos
1. [Objetivo del Sistema](#1-objetivo-del-sistema)
2. [Módulos Principales](#2-módulos-principales)
3. [Flujo General](#3-flujo-general)
4. [Estructura de Base de Datos](#4-estructura-de-base-de-datos)
5. [Lógica Principal Backend](#5-lógica-principal-backend)
6. [Pantallas / Vistas](#6-pantallas--vistas)
7. [Seguridad y Trazabilidad](#7-seguridad-y-trazabilidad)
8. [Reportes](#8-reportes)
9. [Stack Tecnológico](#9-stack-tecnológico)
10. [Estructura del Proyecto](#10-estructura-del-proyecto)
11. [Instalación y Configuración](#11-instalación-y-configuración)
12. [Guía de Desarrollo Paso a Paso](#12-guía-de-desarrollo-paso-a-paso)
13. [Permisos por Rol](#13-permisos-por-rol)
14. [Roadmap de Implementación](#14-roadmap-de-implementación)

---

## 1. Objetivo del sistema

El sistema permitirá a la PM (Project Manager) y al equipo:
- Cargar la lista de materiales requeridos para la obra (cable, APs, switches, patch panels, etc.).
- Registrar compras (altas a inventario) con fecha, proveedor, responsable y costo.
- Registrar entregas de material a obra (bajas de inventario) con fecha, responsable y destinatario.
- Visualizar avance del suministro:
  - % Entregado a obra
  - % Disponible en almacén
  - % Faltante por comprar
  - Costo promedio y total de compras
  - Último costo unitario registrado
- Control de múltiples proyectos simultáneos
- Trazabilidad completa (quién hizo qué y cuándo)
- Exportación de reportes (PDF/Excel)

---

## 2. Módulos principales
1. **Catálogo de Materiales**
2. **Compras / Entradas a Inventario**
3. **Entregas a Obra / Salidas de Inventario**
4. **Dashboard de Avance del Proyecto**
5. **Usuarios / Control de Acceso (básico)**
6. **Reportes de Costos Promedio y Totales**

---

## 3. Flujo general

1. La PM crea la "Lista de Requerimientos" del proyecto:
   - Para cada producto: descripción, unidad, cantidad requerida total para la obra.
2. Conforme se van comprando materiales:
   - Se registra la compra → esto aumenta inventario disponible y guarda el costo unitario.
3. Conforme se entrega material al sitio:
   - Se registra la salida → esto descuenta inventario y marca entregado en obra.
4. El dashboard calcula:
   - % Entregado, % En almacén, % Faltante.
   - Costo promedio, último costo y costo total invertido.

---

## 4. Estructura de base de datos (MySQL)

### 4.1 Script SQL Completo

```sql
-- ============================================
-- Sistema de Control de Material para Proyectos
-- Base de datos MySQL 8.0+
-- ============================================

CREATE DATABASE IF NOT EXISTS control_materiales 
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE control_materiales;

-- ============================================
-- TABLA: users
-- ============================================
CREATE TABLE users (
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
CREATE TABLE projects (
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
CREATE TABLE materials (
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
CREATE TABLE project_requirements (
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
CREATE TABLE inventory (
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
CREATE TABLE purchases (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id       INT UNSIGNED NOT NULL,
  material_id      INT UNSIGNED NOT NULL,
  qty_comprada     DECIMAL(12,2) NOT NULL CHECK (qty_comprada > 0),
  costo_unitario   DECIMAL(12,2) NOT NULL CHECK (costo_unitario >= 0),
  moneda           VARCHAR(10) DEFAULT 'MXN',
  tipo_cambio      DECIMAL(12,4) NULL,
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
CREATE TABLE deliveries (
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
CREATE TABLE material_cost_stats (
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
CREATE TABLE activity_log (
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
INSERT INTO users (nombre, email, password_hash, rol) VALUES
('Administrador', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Categorías de materiales comunes
INSERT INTO materials (sku, descripcion, unidad, categoria) VALUES
('CAB-CAT6-305M', 'Cable UTP Cat 6 - 305m', 'Rollo', 'Cableado'),
('CAB-FIB-50M', 'Cable Fibra Óptica 50m', 'Rollo', 'Cableado'),
('AP-WIFI6-IND', 'Access Point WiFi 6 Industrial', 'Pieza', 'Equipos'),
('SW-24PORT-POE', 'Switch 24 Puertos PoE', 'Pieza', 'Equipos'),
('PP-PATCH-24', 'Patch Panel 24 Puertos', 'Pieza', 'Cableado'),
('RJ45-CONN', 'Conector RJ45', 'Pieza', 'Conectores');


---

## 5. Lógica principal (backend PHP)

### 5.1 Registro de Requerimientos
**Flujo:**
1. Validar que el proyecto existe y está activo
2. Validar que el material existe
3. Validar que no existe ya un requerimiento para ese proyecto/material (UNIQUE)
4. Insertar en `project_requirements`
5. Crear registro inicial en `inventory` con:
   - `qty_disponible = 0`
   - `qty_entregada = 0`
6. Crear registro inicial en `material_cost_stats` con valores en 0
7. Registrar en `activity_log` (opcional)

**Validaciones:**
- `qty_requerida > 0`
- Proyecto debe existir
- Material debe existir y estar activo

### 5.2 Registro de Compras
**Flujo (dentro de una transacción):**
1. Validar datos de entrada:
   - `qty_comprada > 0`
   - `costo_unitario >= 0`
   - Proyecto existe
   - Material existe
   - Usuario tiene permiso
2. Insertar en `purchases`
3. Actualizar `inventory`:
   ```sql
   UPDATE inventory 
   SET qty_disponible = qty_disponible + :qty_comprada,
       last_update = NOW()
   WHERE project_id = :project_id AND material_id = :material_id
   ```
4. Actualizar `material_cost_stats`:
   ```sql
   UPDATE material_cost_stats
   SET total_qty_comprada = total_qty_comprada + :qty_comprada,
       total_costo = total_costo + (:qty_comprada * :costo_unitario),
       costo_promedio_calc = (total_costo + (:qty_comprada * :costo_unitario)) / 
                             (total_qty_comprada + :qty_comprada),
       last_update = NOW()
   WHERE project_id = :project_id AND material_id = :material_id
   ```
5. Si no existe registro en `material_cost_stats`, crearlo
6. Registrar en `activity_log`

**Manejo de Cancelaciones:**
- Al cancelar una compra, revertir los cambios:
  - Disminuir `inventory.qty_disponible`
  - Recalcular `material_cost_stats` excluyendo la compra cancelada

### 5.3 Registro de Entregas
**Flujo (dentro de una transacción):**
1. Validar inventario disponible:
   ```sql
   SELECT qty_disponible FROM inventory 
   WHERE project_id = :project_id AND material_id = :material_id
   ```
   - Si `qty_disponible < qty_entregada`, lanzar error
2. Insertar en `deliveries`
3. Actualizar `inventory`:
   ```sql
   UPDATE inventory
   SET qty_disponible = qty_disponible - :qty_entregada,
       qty_entregada = qty_entregada + :qty_entregada,
       last_update = NOW()
   WHERE project_id = :project_id AND material_id = :material_id
   ```
4. Validar que no quede `qty_disponible` negativo
5. Registrar en `activity_log`

### 5.4 Dashboard - Cálculos
**KPIs por Material:**
```sql
SELECT 
  pr.id,
  m.sku,
  m.descripcion AS material,
  m.unidad,
  pr.qty_requerida,
  inv.qty_disponible,
  inv.qty_entregada,
  -- Porcentajes
  ROUND((inv.qty_entregada / pr.qty_requerida) * 100, 2) AS pct_entregado,
  ROUND((inv.qty_disponible / pr.qty_requerida) * 100, 2) AS pct_en_almacen,
  ROUND(((pr.qty_requerida - inv.qty_entregada - inv.qty_disponible) / pr.qty_requerida) * 100, 2) AS pct_faltante,
  -- Costos
  stats.costo_promedio_calc AS costo_promedio,
  stats.total_costo AS costo_total,
  -- Último costo
  (SELECT costo_unitario FROM purchases 
   WHERE project_id = pr.project_id AND material_id = pr.material_id 
     AND cancelado = 0 
   ORDER BY fecha_compra DESC LIMIT 1) AS ultimo_costo
FROM project_requirements pr
JOIN materials m ON m.id = pr.material_id
LEFT JOIN inventory inv ON inv.project_id = pr.project_id AND inv.material_id = pr.material_id
LEFT JOIN material_cost_stats stats ON stats.project_id = pr.project_id AND stats.material_id = pr.material_id
WHERE pr.project_id = :project_id
ORDER BY m.descripcion
```

**KPIs Globales del Proyecto:**
- Total requerido (suma de todas las `qty_requerida`)
- Total comprado (suma de `total_qty_comprada` de `material_cost_stats`)
- Total entregado (suma de `qty_entregada` de `inventory`)
- Total disponible (suma de `qty_disponible` de `inventory`)
- Total invertido (suma de `total_costo` de `material_cost_stats`)
- Porcentaje de avance físico: `(total_entregado / total_requerido) * 100`

---

## 6. Pantallas / Vistas (Bootstrap 5 + PHP)

### 6.1 Login
- Email / contraseña con validación
- Recordar sesión (opcional)
- Redirección según rol después del login
- Mensajes de error claros
- Protección contra fuerza bruta (rate limiting)

### 6.2 Proyectos
**Lista de Proyectos:**
- Tabla con DataTables: búsqueda, filtros, paginación
- Columnas: Nombre, Ubicación, Estado, Fecha inicio, Fecha fin, Acciones
- Botones de acción por proyecto:
  - Ver Dashboard
  - Gestionar Materiales
  - Ver Compras
  - Ver Entregas
  - Reportes
  - Editar (si tiene permisos)

**Crear/Editar Proyecto:**
- Formulario con validación:
  - Nombre (requerido, max 150 chars)
  - Descripción (textarea)
  - Ubicación (text)
  - Estado (select: planning, active, on_hold, completed)
  - Fecha inicio (date picker)
  - Fecha fin (date picker, debe ser >= fecha inicio)

### 6.3 Requerimientos
**Tabla tipo BOM (Bill of Materials):**

| Material | SKU | Unidad | Cantidad Requerida | Disponible | Entregado | % Avance | Acciones |
|----------|-----|--------|-------------------|------------|-----------|----------|----------|
| Cable UTP Cat 6 | CAB-CAT6 | Rollo | 10 | 5 | 3 | 30% | Editar/Eliminar |

- Botón "+ Agregar Material" que abre modal
- Validación: no permitir duplicados (proyecto + material)
- Al agregar, crear automáticamente registros en `inventory` y `material_cost_stats`

### 6.4 Captura de Compras
**Formulario:**
- Proyecto (select, solo proyectos activos)
- Material (select con búsqueda/autocomplete, solo materiales del proyecto)
- Cantidad (number, > 0)
- Costo unitario (decimal, >= 0)
- Moneda (select: MXN, USD, EUR) - default MXN
- Proveedor (text)
- Número de factura (text, opcional)
- Fecha de compra (date picker, default hoy)
- Botón "Registrar Compra"

**Tabla Histórica (debajo del formulario):**
| Fecha | Material | Cantidad | Costo Unitario | Total | Proveedor | Comprado Por | Estado | Acciones |
|-------|----------|----------|----------------|-------|-----------|--------------|--------|----------|
| 2024-01-15 | Cable Cat6 | 5 | $1,200.00 | $6,000.00 | Proveedor A | Juan Pérez | Activo | Cancelar |

- Filtros por fecha, proveedor, material
- Botón "Cancelar" solo para compras no canceladas (requiere permiso admin)

### 6.5 Entrega a Obra
**Formulario:**
- Proyecto (select)
- Material (select, solo materiales con inventario disponible > 0)
- Cantidad disponible (mostrar automáticamente, solo lectura)
- Cantidad a entregar (number, validar que <= disponible)
- Entregado a (text, requerido)
- Fecha de entrega (date picker, default hoy)
- Comentarios (textarea, opcional)
- Botón "Registrar Entrega"

**Historial de Entregas:**
| Fecha | Material | Cantidad | Entregado A | Entregado Por | Comentarios |
|-------|----------|----------|-------------|---------------|-------------|
| 2024-01-20 | Cable Cat6 | 3 | Obra Zona A | María López | Entrega parcial |

### 6.6 Dashboard
**KPIs Globales (tarjetas superiores):**
- Total Requerido
- Total Comprado
- Total Entregado
- Total Disponible
- % Avance Físico
- Total Invertido

**Gráficas (Chart.js):**
1. **Gráfica de Barras:** % Entregado, % En Almacén, % Faltante (por material)
2. **Gráfica de Pastel:** Distribución de Costos por Material
3. **Gráfica de Líneas:** Evolución de Compras y Entregas (por mes)

**Tabla Resumen de Materiales:**
| Material | Requerido | Comprado | Disponible | Entregado | % Avance | Estado |
|----------|-----------|----------|------------|-----------|----------|--------|
| Cable Cat6 | 10 | 10 | 2 | 8 | 80% | 🟢 Completo |
| Switch 24P | 5 | 3 | 3 | 0 | 0% | 🟡 En Almacén |
| AP WiFi 6 | 8 | 5 | 0 | 5 | 62.5% | 🔴 Faltante |

**Colores de Estado:**
- 🟢 Verde = Completamente entregado (entregado >= requerido)
- 🟡 Amarillo = Parcialmente entregado o en almacén
- 🔴 Rojo = Faltante por comprar (comprado < requerido)

---

## 7. Seguridad y Trazabilidad

### 7.1 Seguridad
- **Autenticación:**
  - Passwords hasheados con `password_hash()` (bcrypt)
  - Sesiones con `session_regenerate_id()` en login
  - Timeout de sesión configurable (default: 1 hora)
  - Cookies `HttpOnly` y `Secure` en producción

- **Validación y Sanitización:**
  - Validar TODOS los inputs en backend
  - Usar `filter_var()` y `htmlspecialchars()` para sanitizar
  - Validar tipos de datos y rangos
  - Prepared statements PDO para prevenir SQL injection

- **Control de Acceso:**
  - Middleware de verificación de sesión
  - Verificación de permisos por rol antes de cada acción
  - Redirección automática si no tiene permisos

### 7.2 Trazabilidad
- **Campos de Auditoría en cada tabla:**
  - `created_at`: Fecha de creación
  - `created_by`: Usuario que creó (o `entregado_por`, `comprado_por`)
  - `updated_at`: Última modificación
  - `cancelado`: Flag para anulación sin eliminar
  - `cancelado_por` y `fecha_cancelacion`: Si fue cancelado

- **Log de Actividad (Opcional):**
  - Tabla `activity_log` para registrar cambios importantes
  - Guardar: acción, tabla, registro_id, valores antiguos/nuevos, IP, user_agent
  - Útil para auditoría y debugging

- **Registro de Usuario:**
  - Todas las acciones críticas registran el usuario responsable
  - No eliminar registros, solo marcar como cancelado

---

## 8. Reportes

### 8.1 Reporte de Costo Promedio por Material

**Consulta Principal:**
```sql
SELECT
    m.sku,
    m.descripcion AS material,
    m.unidad,
    pr.qty_requerida,
    s.total_qty_comprada,
    s.total_costo,
    s.costo_promedio_calc AS costo_promedio_unitario,
    inv.qty_entregada AS cantidad_entregada,
    inv.qty_disponible AS cantidad_disponible,
    -- Última compra
    (SELECT p.costo_unitario 
     FROM purchases p 
     WHERE p.project_id = :project_id 
       AND p.material_id = pr.material_id 
       AND p.cancelado = 0 
     ORDER BY p.fecha_compra DESC LIMIT 1) AS ultimo_costo,
    (SELECT p.proveedor 
     FROM purchases p 
     WHERE p.project_id = :project_id 
       AND p.material_id = pr.material_id 
       AND p.cancelado = 0 
     ORDER BY p.fecha_compra DESC LIMIT 1) AS ultimo_proveedor
FROM project_requirements pr
JOIN materials m ON m.id = pr.material_id
LEFT JOIN material_cost_stats s ON s.project_id = pr.project_id AND s.material_id = pr.material_id
LEFT JOIN inventory inv ON inv.project_id = pr.project_id AND inv.material_id = pr.material_id
WHERE pr.project_id = :project_id
ORDER BY m.descripcion;
```

### 8.2 Reporte Global de Proyecto

**Contenido del Reporte:**
1. **Información del Proyecto:**
   - Nombre, descripción, ubicación
   - Estado, fechas inicio/fin
   - Responsable del proyecto

2. **Resumen Ejecutivo:**
   - Total de materiales requeridos
   - Total comprado (cantidad y valor)
   - Total entregado
   - Total disponible en almacén
   - % Avance físico global
   - Total invertido (monto financiero)

3. **Detalle por Material:**
   - Tabla completa con todos los materiales
   - Incluye: requerido, comprado, entregado, disponible, costos, porcentajes

4. **Historial de Compras (opcional):**
   - Lista de todas las compras con fechas y proveedores

5. **Historial de Entregas (opcional):**
   - Lista de todas las entregas

**Formatos de Exportación:**
- **HTML:** Vista previa en navegador con estilos
- **PDF:** Usando TCPDF o mPDF, con logo, tablas formateadas
- **Excel:** Usando PhpSpreadsheet, con hojas separadas (Resumen, Materiales, Compras, Entregas)

**Filtros Opcionales:**
- Rango de fechas
- Solo materiales con faltante
- Solo materiales entregados

---

## 9. Stack Tecnológico

### Backend
- **PHP 8.1+** (tipado estricto, namespaces)
- **MySQL 8.0+** / **MariaDB 10.5+**
- **PDO** para conexión a base de datos (preparado statements)
- **Sesiones PHP** para autenticación
- **password_hash()** y **password_verify()** para seguridad

### Frontend
- **Bootstrap 5.3+** (UI responsive)
- **jQuery 3.6+** (manipulación DOM y AJAX)
- **Chart.js 4.0+** (gráficas interactivas)
- **DataTables** (tablas avanzadas con búsqueda/paginación)
- **Font Awesome 6** (iconos)

### Librerías Adicionales
- **PhpSpreadsheet** (exportación Excel)
- **TCPDF** o **mPDF** (generación PDF)
- **Moment.js** (formato de fechas en JS)

### Servidor
- **Apache 2.4+** o **Nginx**
- **mod_rewrite** habilitado (para URLs amigables)
- **PHP Extensiones requeridas:**
  - pdo_mysql
  - mbstring
  - json
  - session
  - openssl

---

## 10. Estructura del Proyecto

```
control-materiales/
├── config/
│   ├── database.php          # Configuración de BD
│   ├── config.php            # Configuración general
│   └── constants.php         # Constantes del sistema
│
├── models/
│   ├── Database.php          # Clase base para conexión
│   ├── User.php              # Modelo de usuarios
│   ├── Project.php           # Modelo de proyectos
│   ├── Material.php          # Modelo de materiales
│   ├── Purchase.php          # Modelo de compras
│   ├── Delivery.php          # Modelo de entregas
│   ├── Inventory.php         # Modelo de inventario
│   └── Report.php            # Modelo de reportes
│
├── controllers/
│   ├── AuthController.php    # Login, logout, registro
│   ├── ProjectController.php # CRUD proyectos
│   ├── MaterialController.php # CRUD materiales
│   ├── RequirementController.php # Requerimientos
│   ├── PurchaseController.php  # Compras
│   ├── DeliveryController.php  # Entregas
│   ├── DashboardController.php # Dashboard
│   └── ReportController.php    # Reportes
│
├── views/
│   ├── layouts/
│   │   ├── header.php        # Header común
│   │   ├── footer.php        # Footer común
│   │   └── sidebar.php       # Menú lateral
│   ├── auth/
│   │   └── login.php         # Página de login
│   ├── projects/
│   │   ├── index.php         # Lista de proyectos
│   │   ├── create.php        # Crear proyecto
│   │   ├── edit.php          # Editar proyecto
│   │   └── dashboard.php     # Dashboard del proyecto
│   ├── materials/
│   │   ├── index.php         # Catálogo de materiales
│   │   └── create.php        # Crear material
│   ├── requirements/
│   │   └── index.php         # Lista de requerimientos
│   ├── purchases/
│   │   ├── index.php         # Historial de compras
│   │   └── create.php        # Registrar compra
│   ├── deliveries/
│   │   ├── index.php         # Historial de entregas
│   │   └── create.php        # Registrar entrega
│   └── reports/
│       └── project.php       # Reporte de proyecto
│
├── includes/
│   ├── functions.php         # Funciones auxiliares
│   ├── auth.php              # Verificación de sesión
│   └── permissions.php      # Verificación de permisos
│
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css
│   │   ├── datatables.min.css
│   │   └── custom.css        # Estilos personalizados
│   ├── js/
│   │   ├── jquery.min.js
│   │   ├── bootstrap.bundle.min.js
│   │   ├── chart.js
│   │   ├── datatables.min.js
│   │   └── app.js            # JS personalizado
│   └── img/
│       └── logo.png
│
├── vendor/                   # Composer dependencies
│   └── (PhpSpreadsheet, TCPDF, etc.)
│
├── api/                      # Endpoints AJAX
│   ├── dashboard-data.php    # Datos para dashboard
│   └── material-autocomplete.php
│
├── .htaccess                 # Configuración Apache
├── index.php                 # Punto de entrada principal
├── composer.json             # Dependencias PHP
├── README.md                 # Documentación
└── database.sql              # Script SQL completo
```

---

## 11. Instalación y Configuración

### 11.1 Requisitos Previos
```bash
# Verificar versiones
php -v          # Debe ser >= 8.1
mysql --version  # Debe ser >= 8.0
composer --version
```

### 11.2 Pasos de Instalación

#### Paso 1: Clonar/Descargar proyecto
```bash
cd /var/www/html  # o tu directorio de servidor
mkdir control-materiales
cd control-materiales
```

#### Paso 2: Instalar dependencias con Composer
```bash
composer init
composer require phpoffice/phpspreadsheet
composer require tecnickcom/tcpdf
```

#### Paso 3: Configurar Base de Datos
```bash
# Crear base de datos
mysql -u root -p < database.sql

# O ejecutar el script SQL desde MySQL Workbench / phpMyAdmin
```

#### Paso 4: Configurar archivo de configuración
Editar `config/database.php`:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'control_materiales');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');
define('DB_CHARSET', 'utf8mb4');
```

Editar `config/config.php`:
```php
<?php
define('APP_NAME', 'Control de Materiales');
define('APP_URL', 'http://localhost/control-materiales');
define('TIMEZONE', 'America/Mexico_City');
date_default_timezone_set(TIMEZONE);

// Seguridad
define('SESSION_LIFETIME', 3600); // 1 hora
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
```

#### Paso 5: Configurar Apache (.htaccess)
```apache
RewriteEngine On
RewriteBase /control-materiales/

# Proteger archivos de configuración
<FilesMatch "^(config|includes)">
    Order deny,allow
    Deny from all
</FilesMatch>

# Redirigir a index.php si archivo no existe
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Seguridad adicional
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
</IfModule>
```

#### Paso 6: Permisos de archivos
```bash
chmod 755 -R .
chmod 777 uploads/  # Si hay carga de archivos
```

#### Paso 7: Verificar instalación
1. Acceder a `http://localhost/control-materiales`
2. Login con: `admin@sistema.com` / `admin123`
3. Cambiar password inmediatamente

---

## 12. Guía de Desarrollo Paso a Paso

### Fase 1: Configuración Base (Día 1-2)

#### 1.1 Estructura Inicial
- [ ] Crear estructura de carpetas
- [ ] Configurar `index.php` como router básico
- [ ] Crear `config/database.php` con clase PDO
- [ ] Crear `includes/functions.php` con funciones helper

#### 1.2 Sistema de Autenticación
- [ ] Crear `models/User.php`
- [ ] Crear `controllers/AuthController.php`
- [ ] Crear `views/auth/login.php`
- [ ] Implementar verificación de sesión en `includes/auth.php`
- [ ] Crear layout base (`views/layouts/header.php`, `footer.php`)

#### 1.3 Control de Acceso
- [ ] Crear `includes/permissions.php`
- [ ] Implementar middleware de permisos por rol
- [ ] Crear sidebar con menú según rol

### Fase 2: Módulos Base (Día 3-5)

#### 2.1 CRUD Proyectos
- [ ] Crear `models/Project.php`
- [ ] Crear `controllers/ProjectController.php`
- [ ] Crear vistas: `projects/index.php`, `create.php`, `edit.php`
- [ ] Implementar validaciones frontend y backend
- [ ] Agregar paginación y búsqueda

#### 2.2 CRUD Materiales
- [ ] Crear `models/Material.php`
- [ ] Crear `controllers/MaterialController.php`
- [ ] Crear vistas: `materials/index.php`, `create.php`, `edit.php`
- [ ] Implementar autocomplete para búsqueda de materiales

#### 2.3 Requerimientos por Proyecto
- [ ] Crear vista `requirements/index.php`
- [ ] Agregar funcionalidad para agregar requerimientos
- [ ] Validar UNIQUE (proyecto + material)
- [ ] Crear registros automáticos en `inventory` y `material_cost_stats`

### Fase 3: Módulos de Operación (Día 6-8)

#### 3.1 Registro de Compras
- [ ] Crear `models/Purchase.php`
- [ ] Crear `controllers/PurchaseController.php`
- [ ] Crear `views/purchases/create.php` con formulario
- [ ] Implementar transacción:
  - Insertar en `purchases`
  - Actualizar `inventory`
  - Actualizar `material_cost_stats`
- [ ] Crear `views/purchases/index.php` con historial
- [ ] Implementar cancelación de compras (con reversión)

#### 3.2 Registro de Entregas
- [ ] Crear `models/Delivery.php`
- [ ] Crear `controllers/DeliveryController.php`
- [ ] Crear `views/deliveries/create.php`
- [ ] Validar inventario disponible antes de entregar
- [ ] Actualizar `inventory` en transacción
- [ ] Crear historial de entregas

### Fase 4: Dashboard y Reportes (Día 9-11)

#### 4.1 Dashboard
- [ ] Crear `controllers/DashboardController.php`
- [ ] Crear `views/projects/dashboard.php`
- [ ] Implementar query de KPIs por material
- [ ] Agregar gráficas con Chart.js:
  - Gráfica de barras: % entregado, % en almacén, % faltante
  - Gráfica de pastel: distribución de costos
- [ ] Crear endpoint AJAX: `api/dashboard-data.php`
- [ ] Implementar tablas con colores según estado

#### 4.2 Reportes
- [ ] Crear `models/Report.php`
- [ ] Crear `controllers/ReportController.php`
- [ ] Implementar reporte de costos promedio (HTML)
- [ ] Agregar exportación a Excel con PhpSpreadsheet
- [ ] Agregar exportación a PDF con TCPDF
- [ ] Crear reporte global de proyecto

### Fase 5: Mejoras y Seguridad (Día 12-13)

#### 5.1 Validaciones
- [ ] Validar todos los inputs (sanitización)
- [ ] Validar permisos en cada acción
- [ ] Implementar CSRF tokens
- [ ] Validar tipos de datos

#### 5.2 Seguridad
- [ ] Implementar rate limiting en login
- [ ] Agregar logs de actividad (opcional)
- [ ] Validar permisos de archivos
- [ ] Revisar SQL injection (usar siempre prepared statements)
- [ ] Implementar XSS protection

#### 5.3 UX/UI
- [ ] Agregar mensajes de éxito/error
- [ ] Implementar confirmaciones para acciones destructivas
- [ ] Mejorar responsive design
- [ ] Agregar tooltips y ayuda contextual

### Fase 6: Testing y Documentación (Día 14)

#### 6.1 Testing
- [ ] Probar todos los flujos principales
- [ ] Probar con diferentes roles de usuario
- [ ] Validar cálculos de costos y porcentajes
- [ ] Probar cancelaciones y reversiones

#### 6.2 Documentación
- [ ] Actualizar README.md
- [ ] Documentar funciones principales
- [ ] Crear guía de usuario básica

---

## 13. Permisos por Rol

### Admin
- ✅ Acceso total al sistema
- ✅ Crear/editar/eliminar usuarios
- ✅ Gestión de proyectos
- ✅ Gestión de catálogo de materiales
- ✅ Registrar compras y entregas
- ✅ Ver todos los reportes
- ✅ Cancelar compras

### PM (Project Manager)
- ✅ Crear y gestionar sus proyectos
- ✅ Agregar requerimientos a sus proyectos
- ✅ Registrar compras para sus proyectos
- ✅ Registrar entregas para sus proyectos
- ✅ Ver dashboard de sus proyectos
- ✅ Ver reportes de sus proyectos
- ❌ Gestionar usuarios
- ❌ Modificar catálogo global de materiales
- ❌ Cancelar compras (requiere admin)

### Almacén
- ✅ Ver lista de proyectos
- ✅ Registrar entregas
- ✅ Ver inventario disponible
- ✅ Ver historial de entregas
- ❌ Registrar compras
- ❌ Modificar requerimientos
- ❌ Crear proyectos
- ❌ Ver reportes financieros (solo reportes de inventario)

### Viewer (Solo lectura)
- ✅ Ver lista de proyectos
- ✅ Ver dashboard de proyectos
- ✅ Ver requerimientos
- ✅ Ver historial de compras y entregas
- ✅ Ver reportes (sin exportación)
- ❌ Todas las acciones de modificación

---

## 14. Roadmap de Implementación

### Sprint 1 (Semana 1)
- [x] Estructura del proyecto
- [x] Base de datos
- [ ] Autenticación y roles
- [ ] CRUD Proyectos

### Sprint 2 (Semana 2)
- [ ] CRUD Materiales
- [ ] Requerimientos por proyecto
- [ ] Registro de compras
- [ ] Registro de entregas

### Sprint 3 (Semana 3)
- [ ] Dashboard con KPIs
- [ ] Gráficas interactivas
- [ ] Reportes HTML

### Sprint 4 (Semana 4)
- [ ] Exportación PDF/Excel
- [ ] Validaciones completas
- [ ] Mejoras de UX
- [ ] Testing y ajustes finales

---

## 15. Entregables Finales

- [ ] Script SQL completo (`database.sql`)
- [ ] Estructura completa del proyecto (carpetas y archivos base)
- [ ] Sistema de autenticación funcional
- [ ] CRUD de proyectos y materiales
- [ ] Módulo de requerimientos
- [ ] Módulo de compras con cálculos automáticos
- [ ] Módulo de entregas con validaciones
- [ ] Dashboard interactivo con gráficas
- [ ] Sistema de reportes (HTML, PDF, Excel)
- [ ] Control de acceso por roles
- [ ] Documentación técnica y de usuario

---

## 16. Mejores Prácticas de Código

### PHP
- Usar **tipado estricto**: `declare(strict_types=1);`
- Usar **namespaces** para organizar clases
- Usar **prepared statements** siempre (PDO)
- Validar y sanitizar todas las entradas
- Manejar errores con try-catch
- Usar transacciones para operaciones complejas

### JavaScript
- Usar **let/const** en lugar de var
- Validar datos antes de enviar AJAX
- Manejar errores de AJAX adecuadamente
- Evitar código duplicado

### Seguridad
- Nunca confiar en datos del cliente
- Validar en frontend Y backend
- Usar HTTPS en producción
- Implementar CSRF protection
- Sanitizar outputs (evitar XSS)

---

**Desarrollado para:**  
Proyecto de control de materiales, compras y entregas — obra de cableado estructurado y red.

**Stack Tecnológico:**  
PHP 8.1+ | MySQL 8.0+ | Bootstrap 5.3 | Chart.js 4.0 | jQuery 3.6

---

*Documento actualizado para desarrollo paso a paso completo*


