# 🗂️ Sistema de Archivos Organizados

## 🎯 Problema resuelto

**ANTES:** Todos los archivos en `uploads/` → Carpeta gigante e inmanejable  
**DESPUÉS:** Estructura organizada por fecha → Fácil navegación y mantenimiento

---

## 📊 Nueva estructura de directorios

```
uploads/
├── 2025/                    # Año actual
│   ├── 01/                 # Enero 2025
│   │   ├── 1735689600_a1b2c3d4.jpg
│   │   ├── 1735689700_e5f6g7h8.png
│   │   └── 1735689800_i9j0k1l2.gif
│   ├── 02/                 # Febrero 2025
│   └── 03/                 # Marzo 2025
├── 2024/                    # Años anteriores
│   ├── 12/
│   └── 11/
├── legacy/                  # Archivos existentes migrados
│   ├── old_file1.jpg       # Archivos sin estructura
│   └── old_file2.png
└── cache/                   # Cache de imágenes optimizadas
    ├── abc123.jpg
    └── def456.webp
```

---

## 🚀 Componentes del sistema

### **1. Upload organizado (`upload_organized.php`)**

- Crea automáticamente estructura `YYYY/MM/`
- Nombres únicos: `timestamp_random.extension`
- Metadatos en `storage/metadata/YYYY/MM/archivo.json`
- Retrocompatible con FilePond y sistemas existentes

### **2. Optimización compatible (`simple_img_v2.php`)**

- Soporta rutas anidadas: `2025/01/archivo.jpg`
- Retrocompatible con archivos legacy: `archivo.jpg`
- Auto-detecta estructura nueva vs legacy
- Cache inteligente por ruta completa

### **3. Migración de archivos (`migrate_files.php`)**

- Mueve archivos existentes a `uploads/legacy/`
- Simulación antes de ejecutar
- Progreso en tiempo real
- Rollback disponible

---

## 🎯 Ventajas del sistema organizado

### **📁 Organización**

- **Máximo 1000-2000 archivos por directorio** (vs miles en uno solo)
- **Navegación por fechas** fácil e intuitiva
- **Backup selectivo** por períodos (ej: solo 2024)
- **Archivado automático** de períodos antiguos

### **⚡ Rendimiento**

- **Listado de directorios más rápido** (menos archivos por carpeta)
- **Cache más eficiente** (mejor distribución)
- **Búsquedas optimizadas** por rango de fechas

### **🛠️ Mantenimiento**

- **Limpieza selectiva** (eliminar meses antiguos)
- **Estadísticas por período** fáciles de generar
- **Migración a CDN** por chunks de tiempo
- **Detección de duplicados** más eficiente

---

## 📋 URLs de ejemplo

### **Nuevos uploads (estructura organizada):**

```bash
# Archivo subido en enero 2025
uploads/2025/01/1735689600_a1b2c3d4.jpg

# URLs de optimización:
simple_img_v2.php?src=2025/01/1735689600_a1b2c3d4.jpg&w=100&h=100
simple_img_v2.php?src=2025/01/1735689600_a1b2c3d4.jpg&w=300&f=webp

# URLs amigables (con Nginx):
uploads/2025/01/1735689600_a1b2c3d4.jpg?w=100&h=100
uploads/2025/01/1735689600_a1b2c3d4.jpg?w=300&f=webp
```

### **Archivos legacy (retrocompatibilidad):**

```bash
# Archivo antiguo migrado
uploads/legacy/old_file.jpg

# URLs funcionan igual:
simple_img_v2.php?src=old_file.jpg&w=100&h=100
uploads/old_file.jpg?w=100&h=100  # Con Nginx
```

---

## 🔧 Implementación paso a paso

### **Paso 1: Subir archivos nuevos**

```bash
git pull  # En el servidor
```

### **Paso 2: Migrar archivos existentes**

```bash
# Acceder via web:
https://medios.void.cl/migrate_files.php

# 1. Revisar análisis de archivos
# 2. Ejecutar simulación
# 3. Hacer backup de uploads/
# 4. Ejecutar migración real
```

### **Paso 3: Probar sistema**

```bash
# Probar optimización con archivos legacy:
https://medios.void.cl/simple_img_v2.php?src=archivo_legacy.jpg&w=100&h=100

# Probar subida nueva (usará estructura organizada):
# Usar el formulario de upload normal
```

### **Paso 4: Activar en producción**

```bash
# Cambiar upload.php por upload_organized.php en formularios
# O usar upload_secure.php que ya incluye la mejora
```

---

## 📊 Compatibilidad

| Componente             | Legacy        | Nueva estructura      | Estado      |
| ---------------------- | ------------- | --------------------- | ----------- |
| `upload.php`           | ✅ Funciona   | ❌ No organiza        | Obsoleto    |
| `upload_organized.php` | ✅ Compatible | ✅ Organiza           | Recomendado |
| `simple_img.php`       | ✅ Funciona   | ❌ Solo archivos root | Funcional   |
| `simple_img_v2.php`    | ✅ Funciona   | ✅ Rutas anidadas     | Recomendado |
| URLs amigables         | ✅ Funciona   | ✅ Con Nginx moderno  | Disponible  |

---

## 🎯 Migración de aplicaciones existentes

### **Frontend (JavaScript)**

```javascript
// ANTES
const imageUrl = `simple_img.php?src=${filename}&w=200&h=200`;

// DESPUÉS (automático si usas las APIs)
const imageUrl = response.optimization_urls.thumbnail_200;
// o manualmente:
const imageUrl = `simple_img_v2.php?src=${relativePath}&w=200&h=200`;
```

### **Backend (PHP)**

```php
// ANTES
$thumbnailUrl = "simple_img.php?src={$filename}&w=100&h=100";

// DESPUÉS
$thumbnailUrl = "simple_img_v2.php?src={$relativePath}&w=100&h=100";
```

---

## 🛡️ Seguridad mejorada

- **Path traversal protection:** Bloquea `../` y `./`
- **Validación de rutas:** Solo acepta estructura válida
- **Metadatos separados:** Info sensible en `storage/`
- **Logs detallados:** Trazabilidad completa de uploads

---

## 📈 Estadísticas automáticas

Con la nueva estructura es fácil generar estadísticas:

```php
// Uploads por mes
$uploads2025_01 = count(glob('uploads/2025/01/*'));
$uploads2024_12 = count(glob('uploads/2024/12/*'));

// Tamaño por período
$size2025 = dirSize('uploads/2025/');
$sizeLegacy = dirSize('uploads/legacy/');

// Formatos más usados por mes
$metadata = glob('storage/metadata/2025/01/*.json');
```

---

## 🎉 Resultado final

### **✅ Lo que obtienes:**

1. **Organización perfecta** por año/mes
2. **Retrocompatibilidad total** con archivos existentes
3. **URLs amigables** funcionando con Nginx
4. **Rendimiento mejorado** en navegación de archivos
5. **Mantenimiento simplificado** por períodos
6. **Escalabilidad** para años de crecimiento

### **🔄 Proceso gradual:**

- **Fase 1:** Migrar archivos existentes a `/legacy/`
- **Fase 2:** Nuevos uploads usan estructura organizada
- **Fase 3:** Aplicaciones usan `simple_img_v2.php`
- **Fase 4:** URLs amigables con Nginx moderno

**¡Tu problema de carpeta gigante está resuelto para siempre!** 🚀
