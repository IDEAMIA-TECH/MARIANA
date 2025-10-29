# 📦 Guía para Importar Materiales

## 🎯 Formas de Importar Materiales

Tienes **3 opciones** para proporcionar los materiales:

### **Opción 1: Archivo CSV** (Recomendado - Más Fácil) 📊

1. **Crea un archivo CSV** con Excel o Google Sheets
2. **Formato** (separado por comas):
   ```csv
   sku,descripcion,unidad,categoria,activo
   MAT-001,Material de Ejemplo,Pieza,Cableado,1
   MAT-002,Otro Material,Rollo,Equipos,1
   ```

3. **Guarda el archivo** como `materials_import.csv` en la raíz del proyecto

4. **Ejecuta:**
   ```bash
   php import_materials.php materials_import.csv
   ```

**O coloca el archivo** en la raíz con el nombre `materials_import.csv` y ejecuta:
```bash
php import_materials.php
```

### **Opción 2: Archivo JSON** 📝

1. **Crea un archivo JSON** con este formato:
   ```json
   [
       {
           "sku": "MAT-001",
           "descripcion": "Material de Ejemplo",
           "unidad": "Pieza",
           "categoria": "Cableado",
           "activo": true
       },
       {
           "sku": "MAT-002",
           "descripcion": "Otro Material",
           "unidad": "Rollo",
           "categoria": "Equipos",
           "activo": true
       }
   ]
   ```

2. **Guarda** como `materials_import.json`

3. **Ejecuta:**
   ```bash
   php import_materials.php materials_import.json
   ```

### **Opción 3: Array PHP Directo** 💻

1. **Edita el archivo** `import_materials.php`

2. **Busca la sección** `$materials_array` y agrega tus materiales:
   ```php
   $materials_array = [
       [
           'sku' => 'MAT-001',
           'descripcion' => 'Material de Ejemplo',
           'unidad' => 'Pieza',
           'categoria' => 'Cableado',
           'activo' => true
       ],
       [
           'sku' => 'MAT-002',
           'descripcion' => 'Otro Material',
           'unidad' => 'Rollo',
           'categoria' => 'Equipos',
           'activo' => true
       ]
   ];
   ```

3. **Ejecuta:**
   ```bash
   php import_materials.php
   ```

## 📋 Campos Requeridos

| Campo | Requerido | Descripción | Ejemplo |
|-------|-----------|-------------|---------|
| `sku` | ✅ **SÍ** | Código único del material | `CAB-CAT6-305M` |
| `descripcion` | ✅ **SÍ** | Nombre del material | `Cable UTP Cat 6` |
| `unidad` | ⚠️ Opcional | Unidad de medida | `Pieza`, `Rollo`, `Metro`, `Kilogramo` |
| `categoria` | ⚠️ Opcional | Categoría | `Cableado`, `Equipos`, `Conectores` |
| `activo` | ⚠️ Opcional | Activo (1/true) o Inactivo (0/false) | `1` o `true` |

## 🔄 Comportamiento

- **Si el SKU ya existe:** Se **actualiza** el material
- **Si el SKU no existe:** Se **inserta** como nuevo material

## 📝 Ejemplos de Archivos

He creado archivos de ejemplo que puedes usar como plantilla:

- **`materials_template.csv`** - Plantilla CSV
- **`materials_template.json`** - Plantilla JSON

## ⚡ Uso Rápido

**Método más rápido:**

1. Abre `materials_template.csv` en Excel
2. Agrega tus materiales siguiendo el formato
3. Guarda como `materials_import.csv`
4. Sube el archivo al servidor
5. Ejecuta: `php import_materials.php`

## ✅ Verificar Importación

Después de importar, revisa en el sistema web:
- Ve a: `materials.php`
- Verifica que los materiales aparezcan en la lista

## 🛠️ Desde el Servidor

Si estás en el servidor, puedes pegar directamente los materiales aquí y yo te los formateo, o puedes enviarme:

```
SKU | Descripción | Unidad | Categoría
```

Y te genero el archivo CSV o JSON listo para importar.

---

**💡 Tip:** Para grandes cantidades, usa CSV desde Excel/Sheets. Para pocos materiales, puedes usar el array PHP directo.

