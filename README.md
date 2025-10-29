# 📦 Sistema de Control de Material para Proyectos

Sistema web para gestionar materiales, compras y entregas en proyectos de construcción e instalación.

## 🚀 Características

- ✅ Autenticación y control de acceso por roles
- 📊 Dashboard con KPIs y gráficas
- 📋 Gestión de proyectos
- 📦 Catálogo de materiales
- 🛒 Registro de compras con costos
- 🚚 Registro de entregas a obra
- 📈 Reportes en PDF y Excel
- 👥 Múltiples roles de usuario (Admin, PM, Almacén, Viewer)

## 📋 Requisitos

- PHP 8.1 o superior
- MySQL 8.0+ o MariaDB 10.5+
- Apache 2.4+ con mod_rewrite
- Extensiones PHP: pdo_mysql, mbstring, json, session, openssl

## 🔧 Instalación

### 1. Clonar o descargar el proyecto

```bash
cd /ruta/de/tu/servidor/web
git clone [url-del-repositorio]
cd control-materiales
```

### 2. Configurar base de datos

Editar `config/database.php` con tus credenciales:

```php
define('DB_HOST', 'tu_host');
define('DB_NAME', 'tu_base_de_datos');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');
```

### 3. Crear tablas en la base de datos

```bash
php install_tables.php
```

Este script creará todas las tablas necesarias y el usuario administrador por defecto.

### 4. Configurar URL base

Editar `config/config.php` y ajustar `APP_URL`:

```php
define('APP_URL', 'http://tudominio.com/control-materiales');
```

### 5. Permisos de archivos

```bash
chmod 755 -R .
```

## 🔑 Credenciales por Defecto

Después de la instalación, puedes iniciar sesión con:

- **Email:** admin@sistema.com
- **Password:** admin123

⚠️ **IMPORTANTE:** Cambia la contraseña inmediatamente después del primer acceso.

## 📁 Estructura del Proyecto

```
control-materiales/
├── config/              # Configuración
├── models/              # Modelos de datos
├── controllers/         # Controladores
├── views/               # Vistas (templates)
├── includes/            # Funciones auxiliares
├── assets/              # CSS, JS, imágenes
├── api/                 # Endpoints AJAX
├── index.php           # Página principal
└── login.php           # Página de login
```

## 👥 Roles de Usuario

- **admin:** Acceso total al sistema
- **pm:** Project Manager - gestiona sus proyectos
- **almacen:** Solo registro de entregas
- **viewer:** Solo lectura

## 🛠️ Desarrollo

Para desarrollo local:

1. Configurar un servidor virtual o usar PHP built-in:
```bash
php -S localhost:8000
```

2. Acceder a: `http://localhost:8000/login.php`

## 📚 Documentación

Ver `context.md` para documentación completa del sistema.

## 🔒 Seguridad

- Los archivos de configuración están protegidos por `.htaccess`
- Las contraseñas se guardan con hash bcrypt
- Uso de prepared statements para prevenir SQL injection
- Validación de entrada en backend y frontend
- Control de acceso por roles

## 📝 Roadmap

- [x] Sistema de autenticación
- [ ] CRUD de proyectos
- [ ] CRUD de materiales
- [ ] Módulo de requerimientos
- [ ] Registro de compras
- [ ] Registro de entregas
- [ ] Dashboard con gráficas
- [ ] Reportes PDF/Excel

## 📄 Licencia

Este proyecto es de uso interno.

---

**Desarrollado para:** Control de materiales en proyectos de construcción e instalación

