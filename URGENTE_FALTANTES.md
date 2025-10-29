# ⚠️ ARCHIVOS FALTANTES EN EL SERVIDOR

## Error actual:
```
Failed to open stream: No such file or directory
config/database.php
```

## 🔧 SOLUCIÓN INMEDIATA:

### Opción 1: Ejecutar Quick Fix (MÁS RÁPIDO)
```bash
php quick_fix.php
```

Este script crea **SOLO** los archivos más críticos:
- ✅ config/database.php
- ✅ config/config.php  
- ✅ config/constants.php
- ✅ models/Database.php
- ✅ includes/functions.php
- ✅ includes/auth.php

### Opción 2: Crear archivo manualmente

Crea el archivo `config/database.php` con este contenido:

```php
<?php
declare(strict_types=1);

define('DB_HOST', '173.231.22.109');
define('DB_NAME', 'ideamiadev_marina');
define('DB_USER', 'ideamiadev_mariana');
define('DB_PASS', '3G$qaHNHc5i5HdA$');
define('DB_CHARSET', 'utf8mb4');
```

### Opción 3: Subir TODOS los archivos

**La mejor solución es subir TODOS los archivos del proyecto al servidor.**

## 📋 LISTA DE ARCHIVOS CRÍTICOS MÍNIMOS:

Para que `index.php` funcione, necesitas estos archivos:

```
MARIANA/
├── config/
│   ├── database.php      ⚠️ FALTA ESTE
│   ├── config.php
│   └── constants.php
├── models/
│   ├── Database.php
│   └── User.php
├── includes/
│   ├── functions.php
│   └── auth.php
├── views/
│   └── layouts/
│       └── header.php
├── index.php
└── login.php
```

## 🚀 ORDEN DE ACCIÓN RECOMENDADO:

1. **Ejecuta:** `php quick_fix.php` (crea archivos críticos básicos)
2. **Sube** TODOS los archivos de:
   - `models/` (7 archivos)
   - `controllers/` (7 archivos)
   - `views/` (todas las vistas)
   - Todos los `*.php` de la raíz
3. **Ejecuta:** `php check_server_files.php` (verifica qué falta)
4. **Ejecuta:** `php create_missing_files.php` (crea los que faltan)

## ✅ VERIFICACIÓN:

```bash
php check_server_files.php
```

Debe mostrar ✅ para todos los archivos críticos.

