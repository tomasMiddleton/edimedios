# Configuración para Nginx + Cloudflare

## 🎯 Problema identificado

- **Servidor:** Nginx (no lee archivos .htaccess)
- **CDN:** Cloudflare (cachea imágenes directamente)
- **Solución:** Configurar Nginx + ajustar Cloudflare

## 🔧 1. Configuración de Nginx

Agregar estas reglas al archivo de configuración del sitio (generalmente en `/etc/nginx/sites-available/tu-sitio`):

```nginx
server {
    listen 80;
    server_name medios.void.cl;
    root /path/to/your/project;
    index index.php index.html;

    # Optimización de imágenes
    location ~ ^/uploads/([^/]+\.(jpg|jpeg|png|gif))$ {
        # Si hay query string, redirigir a optimize.php
        if ($args) {
            rewrite ^/uploads/(.+)$ /optimize.php?img=$1 last;
        }

        # Si no hay query string, servir archivo directamente
        try_files $uri =404;

        # Headers de cache para imágenes estáticas
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Access-Control-Allow-Origin "*";
    }

    # Procesar archivos PHP
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; # Ajustar versión PHP
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Headers de cache para imágenes optimizadas
    location ~* \.(jpg|jpeg|png|gif|webp|avif)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Access-Control-Allow-Origin "*";
    }
}
```

**Comandos para aplicar:**

```bash
sudo nano /etc/nginx/sites-available/medios.void.cl
sudo nginx -t  # Verificar configuración
sudo systemctl reload nginx
```

## ☁️ 2. Configuración de Cloudflare

### Opción A: Desactivar cache para optimización (Recomendado)

1. Ir a Cloudflare Dashboard → Rules → Page Rules
2. Crear regla: `medios.void.cl/uploads/*`
3. Configurar: **Cache Level: Bypass**
4. Esto permite que las URLs con parámetros lleguen al servidor

### Opción B: Cache Rules específicas

1. Ir a Cloudflare Dashboard → Rules → Cache Rules
2. Crear regla para `medios.void.cl/uploads/*`
3. Configurar:
   - **Cache status:** Bypass cuando `Query String contains w OR h OR f OR q`
   - **Cache everything else:** Cache for 1 year

### Opción C: Workers (Avanzado)

Crear un Cloudflare Worker que maneje la optimización:

```javascript
export default {
  async fetch(request) {
    const url = new URL(request.url);

    // Si es imagen con parámetros, pasar al servidor
    if (url.pathname.startsWith("/uploads/") && url.search) {
      return fetch(request);
    }

    // Todo lo demás, cache normal
    return fetch(request);
  },
};
```

## 🚀 3. Solución inmediata: img.php

**Mientras configuras Nginx**, puedes usar `img.php` que funciona inmediatamente:

### URLs de ejemplo:

```
# Thumbnail 100x100
https://medios.void.cl/img.php?src=imagen.jpg&w=100&h=100

# WebP optimizado
https://medios.void.cl/img.php?src=imagen.jpg&w=300&h=200&f=webp&q=80

# Mantener aspecto original
https://medios.void.cl/img.php?src=imagen.jpg&w=500
```

### Ventajas de img.php:

- ✅ **Funciona inmediatamente** (no requiere configuración de servidor)
- ✅ **Compatible con cualquier servidor** (Apache, Nginx, LiteSpeed)
- ✅ **Bypass automático de Cloudflare** (es un archivo PHP, no imagen estática)
- ✅ **Cache propio** (genera archivos optimizados)

## 🔄 4. Migración gradual

1. **Fase 1:** Usar `img.php` para nuevas implementaciones
2. **Fase 2:** Configurar Nginx para URLs amigables
3. **Fase 3:** Actualizar Cloudflare para permitir parámetros
4. **Fase 4:** Migrar URLs existentes gradualmente

## 🧪 5. Tests de verificación

### Test básico:

```bash
curl -I "https://medios.void.cl/simple_test.php"
```

### Test de optimización:

```bash
curl -I "https://medios.void.cl/img.php?src=imagen.jpg&w=100&h=100"
```

### Test de cache:

```bash
# Primera llamada (genera cache)
time curl "https://medios.void.cl/img.php?src=imagen.jpg&w=100&h=100" > /dev/null

# Segunda llamada (desde cache)
time curl "https://medios.void.cl/img.php?src=imagen.jpg&w=100&h=100" > /dev/null
```

## ⚡ 6. Optimizaciones adicionales

### Para Cloudflare:

- Activar **Auto Minify** para CSS/JS
- Usar **Rocket Loader** para JavaScript
- Activar **Polish** para optimización automática de imágenes

### Para Nginx:

- Activar **gzip** compression
- Configurar **browser caching**
- Usar **HTTP/2**

## 📞 Contacto con el administrador del servidor

Si no tienes acceso a la configuración de Nginx, proporciona este documento al administrador del servidor con estas instrucciones específicas.
