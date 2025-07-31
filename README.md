# EDI Medios - Sistema de Carga y Optimización de Archivos

Sistema web avanzado para carga, gestión y optimización automática de imágenes con soporte para formatos modernos WebP y AVIF.

## 🚀 Características

### ✨ Funcionalidades Principales

- **Carga de archivos** con interfaz FilePond
- **Optimización automática** de imágenes
- **Redimensionado dinámico** mediante URL
- **Conversión de formatos** (WebP, AVIF, JPEG)
- **Sistema de cache** inteligente
- **Headers de cache** optimizados (1 año)

### 🎯 Optimización de Imágenes

El sistema permite optimizar imágenes al vuelo usando parámetros en la URL:

```
// Imagen original
https://tu-dominio.com/uploads/imagen.jpg

// Redimensionada
https://tu-dominio.com/uploads/imagen.jpg?w=640&h=360

// Optimizada WebP con calidad específica
https://tu-dominio.com/uploads/imagen.jpg?w=640&h=360&q=85&f=webp

// Conversión AVIF
https://tu-dominio.com/uploads/imagen.jpg?w=300&h=200&f=avif&fit=cover
```

#### Parámetros Soportados

- `w` - Ancho en píxeles
- `h` - Alto en píxeles
- `q` - Calidad (1-100, default: 85)
- `f` - Formato (webp, avif, auto)
- `fit` - Ajuste (cover, contain - próximamente)

## 📁 Estructura del Proyecto

```
edimedios/
├── optimize.php          # 🔧 Procesador de optimización
├── .htaccess            # ⚙️ Reglas de rewrite
├── upload.php           # 📤 Backend de carga
├── submit.php           # 📝 Frontend básico
├── cache/               # 💾 Imágenes optimizadas
├── uploads/             # 📂 Archivos originales
├── test_optimization.php # 🧪 Script de verificación
└── .ia/                 # 🤖 Sistema de gestión IA
```

## ⚡ Requisitos del Sistema

### Extensiones PHP Requeridas

```bash
# Verificar extensiones disponibles
php -m | grep -E "(gd|imagick)"
```

- **PHP >= 7.4**
- **Extensión GD** (obligatoria)
- **Extensión Imagick** (opcional, mejor rendimiento)

### Servidor Web

- **Apache** con mod_rewrite
- **Nginx** con configuración de rewrite equivalente

### Permisos de Directorio

```bash
chmod 755 uploads/
chmod 755 cache/
```

## 🔧 Instalación

### 1. Clonar/Descargar el proyecto

```bash
git clone [tu-repositorio]
cd edimedios
```

### 2. Configurar permisos

```bash
mkdir -p uploads cache
chmod 755 uploads cache
```

### 3. Verificar configuración

```bash
php test_optimization.php
```

### 4. Configurar servidor web

Asegurar que `.htaccess` esté habilitado (Apache) o configurar reglas equivalentes (Nginx).

## 🧪 Pruebas

### Script de Verificación

```bash
php test_optimization.php
```

Este script verifica:

- ✅ Extensiones PHP disponibles
- ✅ Permisos de directorios
- ✅ Configuración del sistema
- ✅ URLs de prueba

### Pruebas Manuales

1. Subir una imagen usando `submit.php`
2. Acceder a la imagen con parámetros de optimización
3. Verificar generación de cache en `cache/`
4. Comprobar headers de respuesta

## 📊 Rendimiento

### Cache Inteligente

- Las imágenes optimizadas se almacenan en `cache/`
- Clave de cache basada en MD5 de parámetros
- Headers de cache de 1 año
- Servido directo sin reprocesamiento

### Formatos Soportados

| Formato | Soporte        | Fallback |
| ------- | -------------- | -------- |
| JPEG    | ✅ Nativo      | -        |
| PNG     | ✅ Nativo      | -        |
| GIF     | ✅ Nativo      | -        |
| WebP    | ✅ Condicional | JPEG     |
| AVIF    | ✅ Condicional | JPEG     |

## 🔒 Seguridad

### Validaciones Implementadas

- ✅ Validación de archivos existentes
- ✅ Sanitización de nombres (`basename()`)
- ✅ Verificación de tipos MIME
- ✅ Headers CORS configurables

### Mejoras Pendientes

- [ ] Lista blanca de extensiones
- [ ] Límites de tamaño por archivo
- [ ] Rate limiting por IP
- [ ] Logs de seguridad detallados

## 🛠️ Desarrollo

### Sistema de Gestión IA

El proyecto incluye un sistema de seguimiento en `.ia/`:

- `historial.md` - Registro de cambios
- `proyectos.json` - Metadata del proyecto
- `preferencias.json` - Configuraciones de desarrollo
- `estadisticas.json` - Métricas de uso

### Próximas Mejoras

- [ ] Interfaz de administración
- [ ] Múltiples filtros de imagen
- [ ] Watermarks automáticos
- [ ] API REST completa
- [ ] Dashboard de analytics

## 📞 Soporte

Para reportar problemas o sugerir mejoras, crear un issue en el repositorio del proyecto.

---

**Versión**: 1.0.0 - Sistema de Optimización Implementado  
**Última actualización**: 2025-01-02
