# Debugging .htaccess

Si tienes error 500, prueba estos pasos:

## Opción 1: Deshabilitar .htaccess temporalmente
```bash
mv .htaccess .htaccess.backup
```

Luego accede directamente a `index.php` o `login.php`.

## Opción 2: Versión mínima de .htaccess
```bash
cp .htaccess.minimal .htaccess
```

## Opción 3: Verificar errores de Apache
Revisa los logs de error de Apache:
- En Ubuntu/Debian: `/var/log/apache2/error.log`
- En CentOS/RHEL: `/var/log/httpd/error_log`
- En macOS con Homebrew: `~/Library/Logs/httpd/error_log`

## Opción 4: Verificar módulos de Apache
```bash
apache2ctl -M | grep rewrite
# o
httpd -M | grep rewrite
```

Debe mostrar: `rewrite_module (shared)`

## Problemas comunes:

1. **RewriteBase incorrecto**: Si la app está en un subdirectorio, descomenta y ajusta RewriteBase
2. **Módulo rewrite no habilitado**: Habilita con `a2enmod rewrite` (Linux) o descomenta en httpd.conf (macOS)
3. **Sintaxis Apache 2.2 vs 2.4**: El archivo tiene compatibilidad para ambas versiones

