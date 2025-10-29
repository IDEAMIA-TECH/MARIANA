# Sistema de Logging

## ¿Dónde están los logs?

Los errores ahora se guardan en: **`logs/error.log`**

## Ver errores

### Opción 1: Ver últimas líneas del log
```bash
php view_errors.php
```

### Opción 2: Ver directamente el archivo
```bash
tail -f logs/error.log
```

### Opción 3: Ver últimas 50 líneas
```bash
tail -50 logs/error.log
```

## Limpiar logs

```bash
php clear_logs.php
```

O manualmente:
```bash
> logs/error.log
```

## Configuración

En desarrollo, los errores se muestran en pantalla Y se guardan en el log.

Para producción, edita `index.php` y cambia:
```php
$is_development = false; // Cambiar a false
```

## Errores 500

Si tienes un error 500:

1. Revisa `logs/error.log` inmediatamente después del error
2. Ejecuta `php view_errors.php` para ver los últimos errores
3. Si el log está vacío, puede ser un error de Apache (revisa logs de Apache)

## Logs de Apache

Si PHP no captura el error, revisa los logs de Apache:

**macOS con Homebrew:**
```bash
tail -f ~/Library/Logs/httpd/error_log
```

**Ubuntu/Debian:**
```bash
tail -f /var/log/apache2/error.log
```

**CentOS/RHEL:**
```bash
tail -f /var/log/httpd/error_log
```

**Buscar ubicación exacta:**
```bash
php check_logs.php
```
