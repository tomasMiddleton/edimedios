# Configuración Nginx para URLs Amigables

## 🎯 Objetivo

Lograr que `uploads/imagen.jpg?w=100&h=100` funcione redirigiendo a `simple_img.php`

## 📋 Requisitos

- Acceso SSH al servidor: `/home/edidev/code/web/medios/`
- Permisos para editar configuración Nginx
- Reiniciar servicio Nginx

## 🔧 Pasos para implementar

### 1. Localizar archivo de configuración Nginx

```bash
# Buscar configuración del sitio
find /etc/nginx -name "*medios*" -o -name "*void.cl*"

# O revisar la configuración principal
cat /etc/nginx/nginx.conf
```

### 2. Editar configuración del virtual host

Buscar el bloque `server` para `medios.void.cl` y agregar:

```nginx
server {
    listen 80;
    server_name medios.void.cl;
    root /usr/share/nginx/html;  # Esta es la ruta dentro del contenedor
    index index.php index.html;

    # NUEVA REGLA: Optimización de imágenes
    location ~ ^/uploads/([^/]+\.(jpg|jpeg|png|gif))$ {
        # Si hay query string, redirigir a simple_img.php
        if ($args) {
            rewrite ^/uploads/(.+)$ /simple_img.php?src=$1 last;
        }

        # Si no hay query string, servir archivo directamente
        try_files $uri =404;
    }

    # Procesar PHP
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php5.5-fpm.sock;  # Ajustar según versión
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Headers de cache para imágenes
    location ~* \.(jpg|jpeg|png|gif|webp|avif)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Access-Control-Allow-Origin "*";
    }
}
```

### 3. Verificar y aplicar cambios

```bash
# Verificar sintaxis
sudo nginx -t

# Si está OK, recargar
sudo systemctl reload nginx
```

### 4. Probar funcionamiento

```bash
# Debería funcionar:
curl -I "https://medios.void.cl/uploads/imagen.jpg?w=100&h=100"
```

## ⚠️ Consideraciones importantes

### Docker + Nginx

Si Nginx está **dentro del contenedor Docker**:

- La configuración está en el contenedor, no en el host
- Necesitas editar dentro del contenedor o reconstruir la imagen
- Más complejo de mantener

### Nginx como proxy reverso

Si Nginx está **en el host** haciendo proxy al contenedor:

- Más fácil de configurar
- Configuración persiste entre reinicios del contenedor
- Recomendado para producción

## 🎯 Recomendación

**Para desarrollo y rapidez:** Usar `simple_img.php` directamente

**Para producción:** Configurar Nginx para URLs amigables

## 🔍 Investigar tu setup actual

```bash
# Ver si Nginx está en host o contenedor
ps aux | grep nginx

# Ver puertos y procesos
netstat -tlnp | grep :80

# Ver configuración actual del contenedor
docker exec medios cat /etc/nginx/nginx.conf
```
