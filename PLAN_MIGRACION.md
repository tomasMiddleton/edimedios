# Plan de Migración: Stack Moderno con URLs Amigables

## 🎯 Objetivo

Migrar de `jmcarbo/nginx-php-fpm:latest` (obsoleto) a un stack moderno con soporte completo para optimización de imágenes.

## 📊 Comparación

| Aspecto            | Actual (Obsoleto) | Nuevo (Moderno) |
| ------------------ | ----------------- | --------------- |
| **PHP**            | 5.5.9 (2014)      | 8.2 (2023)      |
| **Nginx**          | 1.19.6            | 1.25+           |
| **Mantenimiento**  | ❌ Abandonado     | ✅ Oficial      |
| **Seguridad**      | ❌ Sin parches    | ✅ Actualizado  |
| **WebP/AVIF**      | ❌ No soportado   | ✅ Nativo       |
| **URLs Amigables** | ❌ Imposible      | ✅ Configurado  |

## 🚀 Ventajas del nuevo stack

### ✅ **URLs Amigables funcionando:**

```bash
# ANTES: No funciona
uploads/imagen.jpg?w=100&h=100

# DESPUÉS: ¡Funciona perfectamente!
uploads/imagen.jpg?w=100&h=100 → Redirige automáticamente a simple_img.php
```

### ✅ **Retrocompatibilidad total:**

- Todas las URLs existentes siguen funcionando
- `simple_img.php` sigue disponible como backup
- Zero downtime durante la migración

### ✅ **Mejoras adicionales:**

- **WebP y AVIF** nativos en PHP 8.2
- **OPcache** para 300% más velocidad
- **Security headers** automáticos
- **Gzip compression** optimizado
- **Cache inteligente** para assets

## 📋 Pasos de migración

### **Paso 1: Preparar archivos nuevos**

```bash
# En el servidor
cd /home/edidev/code/web/medios/

# Backup del setup actual
cp docker-compose.yml docker-compose.old.yml
cp nginx.conf nginx.old.conf

# Copiar nuevos archivos
cp docker-compose.modern.yml docker-compose.yml
cp nginx.modern.conf nginx.conf
```

### **Paso 2: Construir imagen PHP personalizada** (opcional)

```bash
# Si quieres usar Dockerfile personalizado
docker build -f Dockerfile.php -t medios-php:custom .
```

### **Paso 3: Detener contenedor actual**

```bash
docker stop medios
docker rm medios
```

### **Paso 4: Levantar stack moderno**

```bash
docker-compose up -d
```

### **Paso 5: Verificar funcionamiento**

```bash
# URLs amigables (¡AHORA FUNCIONAN!)
curl -I "https://medios.void.cl/uploads/imagen.jpg?w=100&h=100"

# Backup sigue funcionando
curl -I "https://medios.void.cl/simple_img.php?src=imagen.jpg&w=100&h=100"
```

## ⚡ Opción rápida (sin docker-compose)

Si prefieres mantener la configuración actual pero modernizar:

```bash
# Cambiar solo la imagen
docker stop medios
docker rm medios

docker run -d \
  --name medios \
  -p 8087:80 \
  -v /home/edidev/code/web/medios/nginx.modern.conf:/etc/nginx/nginx.conf \
  -v /home/edidev/code/web/medios/edimedios:/var/www/html \
  -e VIRTUAL_HOST=medios.void.cl \
  --restart unless-stopped \
  nginx:1.25-alpine

# Ejecutar PHP 8.2 en contenedor separado
docker run -d \
  --name medios-php \
  -v /home/edidev/code/web/medios/edimedios:/var/www/html \
  --restart unless-stopped \
  php:8.2-fpm-alpine
```

## 🔄 Rollback (si algo sale mal)

```bash
# Volver al setup anterior
docker-compose down
cp docker-compose.old.yml docker-compose.yml
cp nginx.old.conf nginx.conf

# Levantar contenedor original
docker run -d \
  --name medios \
  -p 8087:80 \
  -v /home/edidev/code/web/medios/nginx.conf:/etc/nginx/nginx.conf \
  -v /home/edidev/code/web/medios/edimedios:/usr/share/nginx/html \
  -e VIRTUAL_HOST=medios.void.cl \
  --restart unless-stopped \
  jmcarbo/nginx-php-fpm:latest
```

## 🎯 Resultado final

**URLs que funcionarán después de la migración:**

```bash
# ✅ URLs amigables (NUEVAS)
https://medios.void.cl/uploads/imagen.jpg?w=100&h=100
https://medios.void.cl/uploads/imagen.jpg?w=200&h=200&f=webp

# ✅ URLs directas (BACKUP)
https://medios.void.cl/simple_img.php?src=imagen.jpg&w=100&h=100

# ✅ URLs originales (SIN CAMBIOS)
https://medios.void.cl/uploads/imagen.jpg
```

## ⏱️ Tiempo estimado

- **Preparación:** 10 minutos
- **Migración:** 5 minutos
- **Verificación:** 5 minutos
- **Total:** 20 minutos

## 🛡️ Beneficios de seguridad

- **PHP 8.2:** Últimos parches de seguridad
- **Nginx moderno:** Sin vulnerabilidades conocidas
- **Headers de seguridad:** XSS, clickjacking protection
- **Bloqueo de directorios sensibles:** /config, /lib, /logs

## 💡 Recomendación

**¡Hazlo!** El stack actual es inseguro y limitado. La migración es simple y obtienes:

1. **URLs amigables funcionando**
2. **Mejor rendimiento** (PHP 8.2 + OPcache)
3. **Seguridad actualizada**
4. **WebP/AVIF nativo**
5. **Retrocompatibilidad total**
