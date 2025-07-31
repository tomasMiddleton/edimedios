# 📝 Sistema de Logging y Mensajes EDI Medios

## 🎯 Características implementadas

- **📊 Logging automático** de todas las actividades del sistema
- **💬 Mensajes coherentes** y descriptivos para usuarios
- **🗄️ Base de datos SQLite** con tabla `activity_logs`
- **📈 Dashboard integrado** con logs en tiempo real
- **🔍 Filtros avanzados** por tipo y estado
- **🛡️ Captura automática** de contexto (IP, user agent, timestamps)

---

## 📋 Tipos de logs registrados

### **📤 Uploads:**

- **`upload/started`** - Inicio del proceso de upload
- **`upload/success`** - Archivo guardado exitosamente
- **`upload/completed`** - Upload completado con metadatos
- **`upload/error`** - Error de validación (tipo, tamaño)
- **`upload/failed`** - Fallo en el proceso

### **👁️ Visualizaciones:**

- **`image_view/success`** - Imagen servida correctamente
- **`image_view/not_found`** - Imagen no encontrada
- **`image_view/error`** - Error procesando imagen

---

## 🗄️ Estructura de base de datos

### **Tabla `activity_logs`:**

```sql
CREATE TABLE activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_type VARCHAR(50) NOT NULL,     -- upload, image_view
    status VARCHAR(20) NOT NULL,            -- success, error, not_found, etc.
    message TEXT NOT NULL,                  -- Mensaje descriptivo
    details TEXT,                           -- Información adicional
    file_path VARCHAR(500),                 -- Ruta del archivo (si aplica)
    file_size INTEGER,                      -- Tamaño del archivo
    ip_address VARCHAR(45),                 -- IP del usuario
    user_agent TEXT,                        -- User agent del navegador
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### **Índices optimizados:**

- `idx_activity_logs_type` - Filtro por tipo de actividad
- `idx_activity_logs_status` - Filtro por estado
- `idx_activity_logs_date` - Consultas por fecha

---

## 💬 Mensajes mejorados

### **✅ Upload exitoso:**

```json
{
  "success": true,
  "message": "✅ Archivo subido exitosamente",
  "details": "Tu imagen 'foto.jpg' se ha guardado correctamente y está lista para usar",
  "file": {
    "name": "1735689600_abc123.jpg",
    "original_name": "foto.jpg",
    "path": "2025/01/1735689600_abc123.jpg",
    "size_formatted": "2.5 MB"
  }
}
```

### **❌ Upload fallido:**

```json
{
  "error": "Tipo de archivo 'pdf' no permitido. Formatos aceptados: jpg, jpeg, png, gif, webp, avif",
  "message": "❌ Error al subir archivo",
  "details": "No se pudo procesar tu archivo. Tipo de archivo 'pdf' no permitido.",
  "help": "Verifica que el archivo sea una imagen válida (JPG, PNG, GIF, WebP, AVIF) y menor a 100MB"
}
```

### **🔍 Imagen no encontrada:**

```
❌ Imagen no encontrada

🔍 Archivo buscado: imagen_inexistente.jpg

💡 Verifica que:
• El archivo existe en el servidor
• La ruta esté escrita correctamente
• No falten barras (/) en la ruta

📝 Ejemplos de rutas válidas:
• Nueva estructura: 2025/01/archivo.jpg
• Legacy: archivo.jpg
• Legacy migrado: legacy/archivo.jpg

🔗 Para ver imágenes disponibles: simple_img_v3.php
```

### **⚙️ Error de procesamiento:**

```
❌ Error procesando imagen: No se pudo leer la imagen

🔍 Archivo: imagen_corrupta.jpg
📐 Dimensiones solicitadas: 300x200
🎨 Formato solicitado: webp
⚙️ Calidad solicitada: 85

💡 Posibles causas:
• Archivo corrupto o no es una imagen válida
• Formato de imagen no soportado
• Problemas de memoria del servidor
• Permisos insuficientes

🔗 Intenta con otra imagen o contacta al administrador
```

---

## 🚀 APIs de logging

### **Registrar actividad:**

```php
$stats = new StatsManager();

// Log de upload exitoso
$logId = $stats->logActivity(
    'upload',                    // Tipo de actividad
    'completed',                 // Estado
    'Archivo subido exitosamente: foto.jpg',  // Mensaje
    'Guardado como: 2025/01/foto.jpg (2.5MB)', // Detalles
    '2025/01/foto.jpg',         // Ruta del archivo
    2621440                     // Tamaño en bytes
);

// Log de error
$stats->logActivity(
    'upload',
    'error',
    'Tipo de archivo no permitido',
    'Archivo rechazado: documento.pdf (extensión: pdf)',
    null,                       // Sin ruta (archivo rechazado)
    1048576                     // Tamaño del archivo rechazado
);
```

### **Obtener logs:**

```php
// Logs recientes (últimos 50)
$recentLogs = $stats->getActivityLogs(50);

// Logs filtrados por tipo
$uploadLogs = $stats->getActivityLogs(30, 'upload');

// Logs filtrados por estado
$errorLogs = $stats->getActivityLogs(20, null, 'error');

// Estadísticas de logs
$logStats = $stats->getLogStats();
```

---

## 📊 Dashboard de logs

### **🎨 Interfaz visual:**

- **Filtros interactivos**: Todos, Éxitos, Errores, Uploads, Visualizaciones
- **Tabla responsive**: Tiempo, Tipo, Estado, Mensaje, Archivo, IP
- **Badges coloridos**: Verde (éxito), Rojo (error), Amarillo (advertencia)
- **Tooltips**: Detalles completos al hacer hover

### **🔍 Funcionalidades:**

- **Filtrado en tiempo real** por JavaScript
- **Logs limitados** a últimos 30 para rendimiento
- **Información contextual** (IP, tamaño archivo, timestamps)
- **Enlaces directos** a archivos cuando disponibles

### **📱 Responsive design:**

- **Bootstrap 5** para diseño adaptativo
- **Tabla scrollable** en dispositivos móviles
- **Iconos Font Awesome** para mejor UX

---

## 🧪 Testing del sistema

### **Script de pruebas (`test_logging.php`):**

```bash
# Acceder via navegador:
https://medios.void.cl/test_logging.php

# Pruebas incluidas:
✅ Simular upload exitoso
❌ Simular error de upload
👁️ Simular visualización de imagen
🔍 Simular imagen no encontrada
📋 Recuperar logs recientes
📊 Estadísticas de logs
```

### **Tests automáticos:**

- **Registro de actividades** en base de datos
- **Recuperación de logs** con filtros
- **Estadísticas por tipo** y estado
- **Integridad de datos** (tipos, timestamps)

---

## 🔧 Integración en archivos existentes

### **`upload_organized_v2.php`:**

```php
// Log inicio
$stats->logActivity('upload', 'started', 'Inicio de proceso de upload');

// Log validación fallida
$stats->logActivity('upload', 'error', $errorMsg, $details, null, $fileSize);

// Log archivo guardado
$stats->logActivity('upload', 'success', 'Archivo subido exitosamente', $details);

// Log proceso completo
$stats->logActivity('upload', 'completed', $successMsg, $details);
```

### **`simple_img_v3.php`:**

```php
// Log imagen no encontrada
$stats->logActivity('image_view', 'not_found', 'Imagen no encontrada', $details);

// Log visualización exitosa
$stats->logActivity('image_view', 'success', 'Imagen servida', $details);

// Log error de procesamiento
$stats->logActivity('image_view', 'error', 'Error procesando imagen', $details);
```

---

## 📈 Análisis de logs

### **🔍 Casos de uso:**

- **Debugging**: Identificar errores frecuentes
- **Análisis de uso**: Qué imágenes se consultan más
- **Seguridad**: Detectar patrones sospechosos por IP
- **Rendimiento**: Analizar tiempos de procesamiento
- **UX**: Entender puntos de fricción para usuarios

### **📊 Métricas disponibles:**

- **Uploads por día**: Tendencia de crecimiento
- **Errores más frecuentes**: Áreas de mejora
- **IPs más activas**: Análisis de usuarios
- **Archivos problemáticos**: Imágenes que fallan often
- **Rendimiento promedio**: Tiempos de respuesta

---

## 🛠️ Mantenimiento

### **🧹 Limpieza automática:**

```php
// Eliminar logs antiguos (90 días por defecto)
$deletedCount = $stats->cleanOldLogs(90);
echo "Eliminados $deletedCount logs antiguos";

// Limpieza personalizada (30 días)
$stats->cleanOldLogs(30);
```

### **📊 Monitoreo:**

- **Tamaño de base de datos**: Controlar crecimiento
- **Logs por día**: Detectar picos anómalos
- **Errores frecuentes**: Alertas proactivas
- **Performance**: Tiempo de respuesta de queries

---

## 🎯 Beneficios obtenidos

### **👥 Para usuarios:**

- **Mensajes claros** que explican qué pasó
- **Ayuda contextual** para resolver problemas
- **Feedback inmediato** en uploads y visualizaciones
- **Información útil** sobre errores y soluciones

### **🔧 Para administradores:**

- **Visibilidad total** de actividad del sistema
- **Debugging eficiente** con contexto completo
- **Análisis de patrones** de uso y errores
- **Datos para optimización** y mejoras
- **Auditoría completa** de todas las operaciones

### **📈 Para el negocio:**

- **Métricas de uso** reales y detalladas
- **Identificación de problemas** antes que afecten usuarios
- **Optimización basada en datos** de uso real
- **Compliance y auditoría** con logs detallados

---

## 🚀 Implementación en producción

### **Paso 1: Sincronizar código**

```bash
git pull
```

### **Paso 2: Inicializar sistema**

```bash
# Via navegador:
https://medios.void.cl/init_stats.php
```

### **Paso 3: Probar logging**

```bash
# Test completo:
https://medios.void.cl/test_logging.php

# Dashboard con logs:
https://medios.void.cl/stats_dashboard.php
```

### **Paso 4: Verificar funcionamiento**

- Subir una imagen → Ver log de upload
- Visualizar imagen → Ver log de visualización
- Intentar imagen inexistente → Ver log de error
- Revisar dashboard → Confirmar logs aparecen

---

## 📋 Checklist de funcionamiento

### **✅ Logs de uploads:**

- [ ] Upload exitoso registra log `upload/completed`
- [ ] Error de tipo registra log `upload/error`
- [ ] Error de tamaño registra log `upload/error`
- [ ] Fallo guardado registra log `upload/failed`

### **✅ Logs de visualizaciones:**

- [ ] Imagen encontrada registra log `image_view/success`
- [ ] Imagen no encontrada registra log `image_view/not_found`
- [ ] Error procesamiento registra log `image_view/error`
- [ ] Cache hit registra log con detalle

### **✅ Dashboard:**

- [ ] Logs aparecen en tabla
- [ ] Filtros funcionan correctamente
- [ ] Badges muestran colores correctos
- [ ] Información completa visible

### **✅ Mensajes:**

- [ ] Upload exitoso muestra mensaje positivo
- [ ] Errores muestran ayuda contextual
- [ ] Imagen no encontrada da sugerencias
- [ ] Errores procesamiento explican causas

---

## 🎉 Resultado final

**Tu sistema ahora tiene logging y mensajes de nivel profesional:**

- ✅ **Tracking completo** de toda actividad
- ✅ **Mensajes coherentes** y útiles para usuarios
- ✅ **Dashboard integrado** para administradores
- ✅ **Base de datos optimizada** para consultas rápidas
- ✅ **Filtros avanzados** para análisis detallado
- ✅ **Retrocompatibilidad** mantenida al 100%

**¡Tu plataforma ahora comunica claramente con usuarios y administradores!** 📝✨
