# 🚀 Guía de Instalación - Sistema de Control de Materiales

## ✅ Requisitos Previos

- PHP 8.1 o superior
- MySQL 8.0+ o MariaDB 10.5+
- Apache/Nginx con mod_rewrite habilitado
- Composer (para dependencias opcionales)

## 📦 Instalación Paso a Paso

### Paso 1: Subir archivos al servidor

Sube todos los archivos del proyecto al servidor manteniendo la estructura de carpetas.

### Paso 2: Crear tablas en la base de datos

**Opción A: Usando el script PHP (Recomendado)**
```bash
cd /ruta/del/proyecto
php install_tables.php
```

**Opción B: Manualmente con MySQL**
```bash
mysql -h 173.231.22.109 -u ideamiadev_mariana -p ideamiadev_marina < database.sql
```

### Paso 3: Verificar que todos los archivos existen

```bash
php check_server_files.php
```

O desde el navegador:
```
http://tudominio.com/MARIANA/check_server_files.php
```

Si faltan archivos, ejecuta:
```bash
php create_missing_files.php
```

### Paso 4: Instalar dependencias (Opcional pero Recomendado)

Para exportación PDF y Excel:

```bash
composer install
```

Esto instalará:
- `phpoffice/phpspreadsheet` - Para exportar a Excel
- `tecnickcom/tcpdf` - Para exportar a PDF

**Nota:** Si no instalas Composer, el sistema funcionará pero sin exportación PDF/Excel.

### Paso 5: Configurar permisos

```bash
chmod 755 -R .
chmod 777 logs/  # Si el directorio logs/ existe
mkdir -p logs
chmod 777 logs/
```

### Paso 6: Configurar APP_URL

Edita `config/config.php` y ajusta:
```php
define('APP_URL', 'http://tudominio.com/MARIANA');
```

### Paso 7: Verificar conexión

Accede a:
```
http://tudominio.com/MARIANA/test_connection.php
```

### Paso 8: Acceder al sistema

1. Ve a: `http://tudominio.com/MARIANA/login.php`
2. Ingresa:
   - **Email:** admin@sistema.com
   - **Password:** admin123
3. **Cambia la contraseña inmediatamente**

## 🔧 Solución de Problemas

### Error 500
1. Revisa `logs/error.log`
2. Verifica permisos de archivos
3. Verifica que todas las carpetas existan
4. Prueba deshabilitando `.htaccess` temporalmente

### Error de conexión a BD
1. Verifica credenciales en `config/database.php`
2. Verifica que el host permita conexiones remotas
3. Ejecuta `test_connection.php`

### Faltan extensiones PHP
Verifica que estén instaladas:
```bash
php -m | grep -E "(pdo_mysql|mbstring|json|session|openssl)"
```

### Exportación PDF/Excel no funciona
Instala dependencias:
```bash
composer require phpoffice/phpspreadsheet tecnickcom/tcpdf
```

## 📁 Estructura de Archivos Requerida

Asegúrate de que existan estas carpetas:
```
MARIANA/
├── config/
├── models/
├── controllers/
├── views/
│   ├── layouts/
│   ├── auth/
│   ├── projects/
│   ├── materials/
│   ├── requirements/
│   ├── purchases/
│   ├── deliveries/
│   └── reports/
├── includes/
├── assets/
├── logs/
└── api/
```

## ✅ Verificación Final

Ejecuta:
```bash
php check_server_files.php
```

Todos los archivos deben aparecer como ✅ (existentes).

## 🎉 Listo!

El sistema debería estar funcionando. Accede a `login.php` para comenzar.

---

**Soporte:** Revisa `logs/error.log` para cualquier error.

