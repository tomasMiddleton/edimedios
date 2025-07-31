# 📊 Sistema de Estadísticas EDI Medios

## 🎯 Características principales

- **📈 Tracking automático** de uploads y visualizaciones
- **🗄️ Base de datos local SQLite** (sin servidor externo)
- **📊 Dashboard visual** con gráficos interactivos
- **⚡ Alto rendimiento** con índices optimizados
- **🔄 Retrocompatibilidad total** con sistemas existentes

---

## 🗄️ Arquitectura de datos

### **Tablas principales:**

```sql
-- Archivos subidos
uploads (id, filename, original_name, relative_path, file_size,
         mime_type, extension, year, month, upload_date,
         ip_address, user_agent, created_at)

-- Visualizaciones/optimizaciones
image_views (id, image_path, width, height, format, quality,
             view_date, ip_address, user_agent, referer,
             cache_hit, processing_time_ms, created_at)

-- Estadísticas diarias (cache para rendimiento)
daily_stats (id, date, total_views, total_uploads, unique_images,
             avg_processing_time, top_format, created_at)
```

### **Índices optimizados:**

- `idx_uploads_path` - Búsqueda por ruta de archivo
- `idx_uploads_date` - Filtros por fecha de upload
- `idx_views_path` - Estadísticas por imagen
- `idx_views_date` - Análisis temporal

---

## 🚀 Componentes del sistema

### **1. StatsManager (`lib/StatsManager.php`)**

Clase principal para manejo de estadísticas:

```php
// Registrar upload
$stats->recordUpload([
    'filename' => 'imagen.jpg',
    'original_name' => 'Mi Foto.jpg',
    'relative_path' => '2025/01/imagen.jpg',
    'file_size' => 1024000,
    'mime_type' => 'image/jpeg',
    // ... más campos
]);

// Registrar visualización
$stats->recordView('2025/01/imagen.jpg', [
    'width' => 200,
    'height' => 200,
    'format' => 'webp',
    'quality' => 85,
    'cache_hit' => false,
    'processing_time_ms' => 150
]);

// Obtener estadísticas
$generalStats = $stats->getGeneralStats();
$topImages = $stats->getTopImages(10);
$periodStats = $stats->getStatsByPeriod(30);
```

### **2. Upload con tracking (`upload_organized_v2.php`)**

- Sistema de upload organizado **+ tracking**
- Registra automáticamente en base de datos
- Mantiene retrocompatibilidad total
- Respuesta incluye URLs de estadísticas

### **3. Optimizador con tracking (`simple_img_v3.php`)**

- Optimización de imágenes **+ tracking**
- Registra cada visualización/optimización
- Mide tiempos de procesamiento
- Detecta cache hits/misses
- Compatible con estructura organizada y legacy

### **4. Dashboard visual (`stats_dashboard.php`)**

- Métricas generales en cards atractivas
- Gráficos interactivos con Chart.js
- Top imágenes más vistas
- Estadísticas detalladas por imagen
- Filtros por período

### **5. Inicializador (`init_stats.php`)**

- Verifica requisitos del sistema
- Crea base de datos y tablas
- Prueba funcionalidades
- Proporciona enlaces útiles

---

## 📊 Métricas disponibles

### **📈 Estadísticas generales:**

- Total de archivos subidos
- Total de visualizaciones
- Imágenes únicas vistas
- Tamaño total almacenado
- Promedio de vistas por imagen
- Actividad diaria (uploads/views)

### **🏆 Top rankings:**

- Imágenes más vistas (con detalles)
- Formatos más utilizados
- Patrones de uso por día
- Rendimiento de cache

### **📅 Análisis temporal:**

- Visualizaciones por día (últimos 30 días)
- Tendencias de uploads
- Picos de actividad
- Patrones estacionales

### **🖼️ Estadísticas por imagen:**

- Número total de visualizaciones
- Primera y última visualización
- Días con actividad
- Tiempo promedio de procesamiento
- Parámetros de optimización más usados

---

## 🔧 Instalación y configuración

### **Paso 1: Verificar requisitos**

```bash
# Requisitos mínimos:
- PHP 5.5+ (recomendado 7.4+)
- PDO con driver SQLite
- Extensión GD para imágenes
- Permisos de escritura en storage/
```

### **Paso 2: Inicializar sistema**

```bash
# Acceder via navegador:
https://medios.void.cl/init_stats.php

# O ejecutar manualmente:
php init_stats.php
```

### **Paso 3: Configurar .gitignore**

```bash
# Agregar líneas:
storage/stats.db
storage/stats.db-*
storage/metadata/
```

### **Paso 4: Integrar en aplicaciones**

```php
// Reemplazar upload.php por upload_organized_v2.php
// Reemplazar simple_img.php por simple_img_v3.php
// Opcional: usar StatsManager directamente
```

---

## 📱 URLs y endpoints

### **Dashboard y visualización:**

```bash
stats_dashboard.php                    # Dashboard principal
stats_dashboard.php?image=ruta.jpg     # Estadísticas de imagen específica
simple_img_v3.php                      # Optimizador con tracking
init_stats.php                         # Inicializador del sistema
```

### **APIs de datos:**

```bash
# Upload con tracking
POST upload_organized_v2.php
→ Registra upload + retorna URLs de optimización

# Optimización con tracking
GET simple_img_v3.php?src=imagen.jpg&w=200&h=200
→ Optimiza imagen + registra visualización

# Estadísticas vía StatsManager
$stats->getGeneralStats()              # Métricas generales
$stats->getTopImages(15)               # Top 15 más vistas
$stats->getStatsByPeriod(30)           # Últimos 30 días
$stats->getFormatStats()               # Formatos más usados
$stats->getImageStats('imagen.jpg')    # Estadísticas específicas
```

---

## 💡 Casos de uso

### **🔍 Análisis de contenido:**

- "¿Cuáles son mis imágenes más populares?"
- "¿Qué formatos prefieren los usuarios?"
- "¿Cuándo hay más actividad en mi plataforma?"

### **⚡ Optimización de rendimiento:**

- "¿Qué imágenes tardan más en procesar?"
- "¿Está funcionando bien el cache?"
- "¿Necesito más recursos de servidor?"

### **📈 Crecimiento del negocio:**

- "¿Está creciendo el uso de mi plataforma?"
- "¿Qué tipo de contenido sube más la gente?"
- "¿Cuál es el patrón de uso diario/semanal?"

### **🛠️ Mantenimiento técnico:**

- "¿Qué archivos puedo archivar o eliminar?"
- "¿Necesito limpiar el cache?"
- "¿Hay patrones de error o problemas?"

---

## 📊 Dashboard features

### **🎨 Diseño moderno:**

- Cards con gradientes atractivos
- Iconos Font Awesome
- Responsive Bootstrap 5
- Gráficos interactivos Chart.js

### **📈 Gráficos disponibles:**

- **Línea temporal:** Visualizaciones por día
- **Dona:** Distribución de formatos
- **Ranking:** Top imágenes más vistas
- **Métricas:** Cards con números destacados

### **🔍 Funcionalidades:**

- **Vista general:** Métricas del sistema completo
- **Vista detallada:** Estadísticas por imagen individual
- **Navegación:** Enlaces directos a optimizador y upload
- **Responsivo:** Funciona en desktop y móvil

---

## 🚀 Rendimiento y escalabilidad

### **🗄️ Base de datos:**

- **SQLite:** Sin servidor, archivo local
- **Índices optimizados:** Consultas rápidas
- **Estadísticas diarias:** Cache para agregaciones
- **Tamaño estimado:** ~1MB por 10,000 visualizaciones

### **⚡ Optimizaciones:**

- **Inserts batch:** Para uploads múltiples
- **Lazy loading:** Dashboard carga progresivamente
- **Cache de consultas:** Estadísticas frecuentes
- **Cleanup automático:** Datos antiguos opcionales

### **📈 Límites estimados:**

- **Uploads:** Millones sin problemas
- **Visualizaciones:** Decenas de millones
- **Consultas dashboard:** Sub-segundo típico
- **Espacio disco:** Muy eficiente

---

## 🔄 Migración y compatibilidad

### **🔄 Desde sistema anterior:**

```php
// ANTES
simple_img.php?src=imagen.jpg&w=200&h=200

// DESPUÉS (con tracking)
simple_img_v3.php?src=imagen.jpg&w=200&h=200
// Misma funcionalidad + estadísticas automáticas
```

### **📊 Datos legacy:**

- Archivos existentes funcionan normalmente
- Solo nuevas interacciones se registran
- No se pierden datos existentes
- Migración opcional de metadatos históricos

### **🔗 Integración gradual:**

1. **Fase 1:** Instalar sistema de estadísticas
2. **Fase 2:** Usar nuevos endpoints para uploads
3. **Fase 3:** Migrar optimización a v3
4. **Fase 4:** Analizar datos y optimizar

---

## 🎯 Resumen ejecutivo

### **✅ Lo que obtienes:**

- **📊 Visibilidad total** del uso de tu plataforma
- **🚀 Dashboard profesional** con métricas en tiempo real
- **⚡ Optimización basada en datos** reales de uso
- **🔄 Integración transparente** sin romper funcionalidad
- **📈 Información para crecer** tu negocio/plataforma

### **🎯 Beneficios inmediatos:**

- Saber qué contenido es más popular
- Identificar patrones de uso de tus usuarios
- Optimizar rendimiento basado en datos reales
- Tomar decisiones informadas sobre recursos
- Demostrar el valor de tu plataforma con métricas

**¡Tu plataforma de medios ahora tiene inteligencia de negocio incorporada!** 📊🚀
