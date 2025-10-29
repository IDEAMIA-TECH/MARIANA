# 📋 Guía de Instalación Completa

## ✅ Scripts Ejecutados Localmente

Todos los scripts han sido verificados y están listos:

### 1. `quick_fix.php` ✅
**Estado:** Ejecutado - Archivos críticos verificados
- Crea archivos esenciales si faltan
- ✅ Todos los archivos críticos existen

### 2. `check_server_files.php` ✅  
**Estado:** Ejecutado - Verificación completa
- ✅ 12 archivos críticos encontrados
- ✅ 0 archivos faltantes
- ✅ Todos los directorios existen

### 3. Verificación de Sintaxis ✅
- ✅ `config/database.php` - Sin errores
- ✅ `config/config.php` - Sin errores  
- ✅ `config/constants.php` - Sin errores
- ✅ Todos los scripts PHP - Sintaxis OK

## 📦 Archivos Disponibles Localmente

### Configuración (3 archivos)
- ✅ `config/database.php`
- ✅ `config/config.php`
- ✅ `config/constants.php`

### Modelos (8 archivos)
- ✅ `models/Database.php`
- ✅ `models/User.php`
- ✅ `models/Project.php`
- ✅ `models/Material.php`
- ✅ `models/Requirement.php`
- ✅ `models/Purchase.php`
- ✅ `models/Delivery.php`
- ✅ `models/Report.php`

### Includes (2 archivos)
- ✅ `includes/functions.php`
- ✅ `includes/auth.php`

## 🚀 Pasos para Instalación en el Servidor

### Paso 1: Subir Archivos
Sube TODOS los archivos del proyecto al servidor manteniendo la estructura:
```
/home/ideamiadev/public_html/MARIANA/
├── config/
├── models/
├── controllers/
├── views/
├── includes/
└── *.php (archivos principales)
```

### Paso 2: Ejecutar en el Servidor

**Opción A: Si faltan archivos, ejecuta primero:**
```bash
cd /home/ideamiadev/public_html/MARIANA
php quick_fix.php
```

**Opción B: Verificar que todo existe:**
```bash
php check_server_files.php
```

### Paso 3: Crear Tablas en la BD
```bash
php install_tables.php
```

Esto creará:
- ✅ Todas las tablas necesarias
- ✅ Usuario admin inicial
- ✅ Materiales de ejemplo

### Paso 4: Verificar Instalación
Accede a:
```
http://tudominio.com/MARIANA/login.php
```

**Credenciales:**
- Email: `admin@sistema.com`
- Password: `admin123`

## 🔍 Scripts Disponibles

| Script | Descripción | Cuándo Usarlo |
|--------|-------------|---------------|
| `quick_fix.php` | Crea archivos críticos faltantes | Si falta config/database.php |
| `check_server_files.php` | Verifica qué archivos existen | Después de subir archivos |
| `install_tables.php` | Crea tablas en BD | Primera vez o si faltan tablas |
| `create_missing_files.php` | Crea archivos adicionales | Si faltan modelos/vistas |
| `test_connection.php` | Prueba conexión a BD | Para debug |

## ⚠️ Solución de Problemas

### Error: "config/database.php not found"
```bash
php quick_fix.php
```

### Error: "Table doesn't exist"
```bash
php install_tables.php
```

### Error: "Cannot connect to database"
1. Verifica credenciales en `config/database.php`
2. Ejecuta `php test_connection.php`
3. Verifica que el host permita conexiones remotas

## ✅ Checklist de Instalación

- [ ] Archivos subidos al servidor
- [ ] Directorios creados (config, models, controllers, views, includes)
- [ ] `config/database.php` existe con credenciales correctas
- [ ] `install_tables.php` ejecutado exitosamente
- [ ] Login funciona con admin@sistema.com / admin123
- [ ] Dashboard carga correctamente

## 📝 Notas Importantes

1. **Cambiar APP_URL**: Edita `config/config.php` y actualiza `APP_URL` con tu dominio real
2. **Cambiar contraseña**: Una vez que entres, cambia la contraseña del admin inmediatamente
3. **Permisos**: Asegúrate de que el directorio `logs/` tenga permisos de escritura (777)
4. **Composer**: Si quieres exportar PDF/Excel, ejecuta `composer install` en el servidor

---

**Estado Local:** ✅ Todo listo y verificado  
**Siguiente Paso:** Subir archivos al servidor y ejecutar `install_tables.php`

