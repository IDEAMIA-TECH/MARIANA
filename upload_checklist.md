# Checklist de Archivos para Subir al Servidor

## 📁 Estructura Completa de Directorios

Asegúrate de subir TODA esta estructura:

```
MARIANA/
├── config/
│   ├── database.php       ✅ REQUERIDO
│   ├── config.php         ✅ REQUERIDO
│   └── constants.php      ✅ REQUERIDO
│
├── models/
│   ├── Database.php       ✅ REQUERIDO
│   └── User.php           ✅ REQUERIDO
│
├── controllers/
│   └── AuthController.php ✅ REQUERIDO
│
├── views/
│   └── auth/
│       └── login.php       ✅ REQUERIDO
│
├── includes/
│   ├── functions.php       ✅ REQUERIDO
│   └── auth.php            ✅ REQUERIDO
│
├── logs/
│   └── .htaccess           (se crea automáticamente)
│
├── index.php               ✅ REQUERIDO
├── login.php               ✅ REQUERIDO
├── logout.php              ✅ REQUERIDO
│
└── .htaccess               (opcional)
```

## 🔍 Verificación en el Servidor

Una vez subidos los archivos, ejecuta en el servidor:

```bash
php check_server_files.php
```

O desde el navegador:
```
http://tudominio.com/MARIANA/check_server_files.php
```

## ⚠️ Problemas Comunes

### 1. Error "No such file or directory"
**Causa:** Archivos no subidos o estructura de carpetas incorrecta

**Solución:**
- Verificar que TODAS las carpetas existan: `config/`, `models/`, `includes/`, etc.
- Verificar permisos: `chmod 755 -R MARIANA/`
- Re-subir archivos faltantes

### 2. Permisos de escritura
**Causa:** El servidor no puede escribir en `logs/`

**Solución:**
```bash
mkdir -p logs
chmod 755 logs
touch logs/error.log
chmod 666 logs/error.log
```

### 3. Rutas incorrectas
**Causa:** `__DIR__` puede no funcionar como se espera

**Solución:** Si persiste el problema, podemos usar rutas absolutas o relativas explícitas.

## 📦 Comando para Subir Todo (FTP/SFTP)

Si usas FTP/SFTP:
1. Subir TODA la carpeta `MARIANA/` manteniendo la estructura
2. Asegurar permisos: `chmod 755` para directorios, `chmod 644` para archivos
3. Ejecutar `check_server_files.php` para verificar

## 🔐 Archivos Sensibles

El archivo `config/database.php` contiene credenciales. Asegúrate de:
- ✅ No subirlo a repositorios públicos
- ✅ Tener permisos 600 o 644 máximo
- ✅ Verificar que .htaccess proteja archivos de configuración

