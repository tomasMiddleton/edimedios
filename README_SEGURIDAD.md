# 🛡️ Sistema de Seguridad EDI Medios

## 📋 Descripción General

El sistema de seguridad de EDI Medios proporciona protección completa para todos los endpoints y operaciones del sistema, incluyendo CORS configurable, rate limiting, validación de archivos y logs de auditoría.

## 🏗️ Arquitectura

```
Sistema de Seguridad
├── config/security.json       # Configuración centralizada
├── lib/SecurityManager.php    # Clase principal
├── security_manager.php       # API de gestión
├── test_security.php         # Tests del sistema
└── logs/security.log         # Logs de auditoría
```

## ⚙️ Configuración

### 1. Archivo de Configuración

El archivo `config/security.json` contiene toda la configuración:

```json
{
  "cors": {
    "enabled": true,
    "allowed_origins": [
      "https://medios.void.cl",
      "https://www.medios.void.cl",
      "http://localhost:3000"
    ],
    "allowed_methods": ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
    "allowed_headers": [
      "Origin",
      "X-Requested-With",
      "Content-Type",
      "Accept",
      "Authorization"
    ],
    "allow_credentials": false,
    "max_age": 3600
  },
  "rate_limiting": {
    "enabled": true,
    "requests_per_minute": 60,
    "requests_per_hour": 1000,
    "blocked_duration_minutes": 15
  },
  "file_upload": {
    "max_file_size_mb": 100,
    "allowed_extensions": ["jpg", "jpeg", "png", "gif", "webp", "avif"],
    "allowed_mime_types": [
      "image/jpeg",
      "image/png",
      "image/gif",
      "image/webp",
      "image/avif"
    ],
    "block_executable_content": true
  },
  "api_security": {
    "require_https": false,
    "ip_whitelist": [],
    "ip_blacklist": [],
    "log_all_requests": true,
    "block_user_agents": ["bot", "crawler", "scraper"]
  },
  "emergency": {
    "maintenance_mode": false,
    "maintenance_message": "Sistema en mantenimiento. Intente más tarde."
  }
}
```

### 2. Implementación en PHP

Cada archivo PHP debe incluir:

```php
// Cargar sistema de seguridad
require_once(__DIR__ . '/lib/SecurityManager.php');

try {
    $security = new SecurityManager();
    $security->applySecurityChecks();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Security system error: ' . $e->getMessage()]);
    exit;
}
```

## 🌐 CORS (Cross-Origin Resource Sharing)

### Configuración de Orígenes

```json
"allowed_origins": [
    "https://medios.void.cl",           // Dominio principal
    "https://www.medios.void.cl",       // Con www
    "http://localhost:3000",            // Desarrollo local
    "https://*.medios.void.cl"          // Wildcards soportados
]
```

### Métodos y Headers

- **Métodos permitidos**: GET, POST, PUT, DELETE, OPTIONS
- **Headers permitidos**: Origin, X-Requested-With, Content-Type, Accept, Authorization
- **Credenciales**: Configurables (por defecto: false)
- **Cache**: 3600 segundos por defecto

### Verificación CORS

```bash
# Probar origen específico
curl -H "Origin: https://medios.void.cl" \
     -H "Access-Control-Request-Method: POST" \
     -X OPTIONS \
     https://tu-dominio.com/simple_img_v3.php
```

## ⚡ Rate Limiting

### Límites por Defecto

- **60 requests por minuto**
- **1000 requests por hora**
- **Bloqueo de 15 minutos** al exceder límites

### Identificación de Clientes

El sistema identifica clientes únicos usando:

- Dirección IP
- User-Agent
- Hash SHA256 de la combinación

### Configuración Personalizada

```json
"rate_limiting": {
    "enabled": true,
    "requests_per_minute": 120,    // Aumentar para APIs intensivas
    "requests_per_hour": 2000,     // Límite por hora
    "blocked_duration_minutes": 30 // Tiempo de bloqueo
}
```

## 📁 Validación de Archivos

### Verificaciones de Seguridad

1. **Tamaño máximo**: 100MB por defecto
2. **Extensiones permitidas**: Solo formatos de imagen
3. **MIME types**: Verificación estricta
4. **Contenido ejecutable**: Detección y bloqueo

### Detección de Amenazas

El sistema detecta:

- Ejecutables PE (Windows)
- Binarios ELF (Linux)
- Scripts con shebang (#!)
- Código PHP embebido
- JavaScript malicioso

### Configuración de Upload

```json
"file_upload": {
    "max_file_size_mb": 50,        // Reducir para sitios pequeños
    "allowed_extensions": ["jpg", "png"], // Solo JPEG y PNG
    "block_executable_content": true,     // Siempre recomendado
    "scan_for_viruses": false            // Requiere ClamAV
}
```

## 🔐 Control de Acceso

### IP Whitelist/Blacklist

```json
"api_security": {
    "ip_whitelist": [
        "192.168.1.100",    // IP específica
        "10.0.0.0/8"        // Rango de red
    ],
    "ip_blacklist": [
        "203.0.113.0/24"    // Bloquear rangos maliciosos
    ]
}
```

### User-Agent Filtering

```json
"block_user_agents": [
    "bot",               // Bots genéricos
    "crawler",           // Crawlers
    "scraper",          // Scrapers
    "BadBot/1.0"        // Bots específicos
]
```

## 📊 Logs de Seguridad

### Eventos Registrados

- Violaciones CORS
- Rate limiting excedido
- IPs bloqueadas
- User-agents rechazados
- Uploads rechazados
- Cambios de configuración

### Formato de Log

```json
{
  "timestamp": "2025-01-02T12:00:00Z",
  "type": "cors_violation",
  "message": "Origen no permitido: https://evil-site.com",
  "ip": "203.0.113.1",
  "user_agent": "BadBot/1.0",
  "request_uri": "/simple_img_v3.php",
  "details": {
    "origin": "https://evil-site.com",
    "allowed_origins": ["https://medios.void.cl"]
  }
}
```

### Rotación de Logs

- **Automática**: 30 días por defecto
- **Manual**: `security_manager.php?action=rotate_logs`
- **Compresión**: Logs antiguos se comprimen

## 🚨 Modo de Emergencia

### Activación de Mantenimiento

```json
"emergency": {
    "maintenance_mode": true,
    "maintenance_message": "Mantenimiento programado hasta las 14:00"
}
```

### Respuesta Automática

```http
HTTP/1.1 503 Service Unavailable
Retry-After: 3600
Content-Type: application/json

{
    "error": "maintenance_mode",
    "message": "Mantenimiento programado hasta las 14:00",
    "retry_after": "3600"
}
```

## 🔧 API de Gestión

### Endpoints Disponibles

#### Obtener Configuración

```bash
GET /security_manager.php?action=get_config
```

#### Actualizar Configuración

```bash
POST /security_manager.php
Content-Type: application/json

{
    "action": "update_config",
    "config": {
        "cors": {
            "enabled": true,
            "allowed_origins": ["https://nuevo-dominio.com"]
        }
    }
}
```

#### Probar CORS

```bash
GET /security_manager.php?action=test_cors&origin=https://ejemplo.com
```

#### Obtener Logs de Seguridad

```bash
GET /security_manager.php?action=get_security_logs&limit=100
```

## 🖥️ Dashboard de Gestión

### Acceso al Panel

1. Abrir `stats_dashboard.php`
2. Ir a la pestaña **"Seguridad"**
3. Configurar opciones en tiempo real

### Funcionalidades del Dashboard

- ✅ **Habilitar/deshabilitar CORS**
- ✅ **Gestionar orígenes permitidos**
- ✅ **Configurar rate limiting**
- ✅ **Ajustar límites de upload**
- ✅ **Activar modo mantenimiento**
- ✅ **Ver logs en tiempo real**

## 🧪 Testing y Verificación

### Script de Pruebas

```bash
# Ejecutar tests completos
php test_security.php
```

### Pruebas Manuales

#### 1. Test CORS

```bash
curl -H "Origin: https://medios.void.cl" \
     -X OPTIONS \
     https://tu-dominio.com/simple_img_v3.php
```

#### 2. Test Rate Limiting

```bash
# Hacer múltiples requests rápidos
for i in {1..70}; do
    curl https://tu-dominio.com/simple_img_v3.php
done
```

#### 3. Test Upload Security

```bash
# Intentar subir archivo malicioso
curl -F "filepond=@malicious.php" \
     https://tu-dominio.com/upload_organized_v2.php
```

## 🔒 Mejores Prácticas

### 1. Configuración de Producción

```json
{
  "cors": {
    "enabled": true,
    "allowed_origins": ["https://medios.void.cl"], // Solo dominio principal
    "allow_credentials": false // Nunca true sin HTTPS
  },
  "rate_limiting": {
    "enabled": true,
    "requests_per_minute": 30, // Más restrictivo
    "blocked_duration_minutes": 60 // Bloqueos más largos
  },
  "api_security": {
    "require_https": true, // Forzar HTTPS
    "log_all_requests": true // Auditoría completa
  }
}
```

### 2. Monitoreo

- **Revisar logs diariamente**
- **Alertas por rate limiting excesivo**
- **Backup de configuración**
- **Tests automáticos semanales**

### 3. Actualizaciones

- **Actualizar lista de User-Agents bloqueados**
- **Revisar orígenes CORS regularmente**
- **Ajustar límites según uso real**
- **Rotar logs mensualmente**

## 🚀 Integración en Archivos Existentes

### Para Nuevos Endpoints

```php
<?php
// 1. Cargar SecurityManager
require_once(__DIR__ . '/lib/SecurityManager.php');

// 2. Aplicar seguridad
try {
    $security = new SecurityManager();
    $security->applySecurityChecks();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Security error: ' . $e->getMessage()]);
    exit;
}

// 3. Tu código aquí...
header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
?>
```

### Para Archivos de Solo Lectura

```php
<?php
// Solo aplicar CORS, sin rate limiting
require_once(__DIR__ . '/lib/SecurityManager.php');

try {
    $security = new SecurityManager();
    $security->applyCORS();  // Solo headers CORS
} catch (Exception $e) {
    // Log error pero continuar
    error_log('Security warning: ' . $e->getMessage());
}

// Tu código aquí...
?>
```

## 📞 Soporte y Troubleshooting

### Problemas Comunes

1. **CORS bloqueado**: Verificar origen en `allowed_origins`
2. **Rate limit excedido**: Ajustar límites o revisar traffic
3. **Upload rechazado**: Verificar extensión y MIME type
4. **Logs no se crean**: Verificar permisos de carpeta `logs/`

### Debugging

```bash
# Ver logs en tiempo real
tail -f logs/security.log

# Verificar configuración
curl https://tu-dominio.com/security_manager.php?action=get_config

# Test completo del sistema
php test_security.php > test_results.html
```

### Contacto

- **Issues**: Crear issue en el repositorio
- **Emergencias**: Activar modo mantenimiento
- **Actualizaciones**: Seguir el changelog

---

**🔐 Recuerda**: La seguridad es un proceso continuo. Revisa y actualiza regularmente tu configuración según las necesidades de tu aplicación.
