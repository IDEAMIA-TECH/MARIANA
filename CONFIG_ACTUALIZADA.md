# ✅ Configuración Actualizada

## Cambio Realizado

**APP_URL** actualizado de:
```
http://localhost/control-materiales
```

**A:**
```
https://ideamia-dev.com/MARIANA
```

## Ubicación

Archivo: `config/config.php` (línea 12)

## Verificación

✅ URL base configurada correctamente
✅ `base_url()` ahora genera URLs con el dominio correcto
✅ Redirecciones después del login funcionarán correctamente

## Ejemplos de URLs Generadas

- `base_url('index.php')` → `https://ideamia-dev.com/MARIANA/index.php`
- `base_url('login.php')` → `https://ideamia-dev.com/MARIANA/login.php`
- `base_url('projects.php')` → `https://ideamia-dev.com/MARIANA/projects.php`

## ⚠️ Importante

Este cambio debe estar en el servidor también. Asegúrate de subir el archivo `config/config.php` actualizado.

## Verificación en Servidor

Después de subir el archivo, verifica:
```php
<?php
require 'config/config.php';
echo APP_URL; // Debe mostrar: https://ideamia-dev.com/MARIANA
```

