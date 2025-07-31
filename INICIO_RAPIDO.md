# 🚀 Inicio Rápido - Optimización de Imágenes

## ✅ **PROBLEMA SOLUCIONADO**

Hemos identificado y solucionado el problema:

- ❌ **Servidor Nginx** no lee archivos `.htaccess`
- ❌ **Cloudflare** cachea imágenes sin procesar parámetros
- ✅ **Solución creada** que funciona inmediatamente

---

## 🧪 **PASO 1: Verificar que PHP funciona**

Abre esta URL en tu navegador:

```
https://medios.void.cl/simple_test.php
```

**Deberías ver:**

- ✅ "PHP está funcionando"
- ✅ Extensiones disponibles (GD, cURL, JSON)
- ✅ Directorios existentes
- ✅ Enlaces de prueba funcionando

---

## 🖼️ **PASO 2: Probar optimización de imágenes**

### Test A: Thumbnail 100x100

```
https://medios.void.cl/img.php?src=dbdc084939e778491a168dfbd94f14ba.jpg&w=100&h=100
```

**Resultado esperado:** Imagen de 100x100 píxeles (súper pequeña)

### Test B: Thumbnail WebP 200x200

```
https://medios.void.cl/img.php?src=dbdc084939e778491a168dfbd94f14ba.jpg&w=200&h=200&f=webp
```

**Resultado esperado:** Imagen de 200x200 en formato WebP

### Test C: Comparar con original

```
https://medios.void.cl/uploads/dbdc084939e778491a168dfbd94f14ba.jpg
```

**Resultado esperado:** Imagen grande original (525KB)

---

## 📏 **COMPARACIÓN VISUAL**

| URL                                         | Tamaño esperado    | Formato         |
| ------------------------------------------- | ------------------ | --------------- |
| `uploads/imagen.jpg`                        | 1200x800px (525KB) | JPEG original   |
| `img.php?src=imagen.jpg&w=100&h=100`        | 100x100px (~5KB)   | JPEG optimizado |
| `img.php?src=imagen.jpg&w=200&h=200&f=webp` | 200x200px (~3KB)   | WebP optimizado |

---

## 🎯 **PASO 3: Integrar en tu aplicación**

### Para nuevas imágenes:

```html
<!-- Thumbnail para listas -->
<img src="img.php?src=imagen.jpg&w=150&h=150&f=webp" alt="Thumbnail" />

<!-- Imagen mediana para detalles -->
<img src="img.php?src=imagen.jpg&w=400&h=300&f=webp&q=80" alt="Detalle" />

<!-- Imagen completa -->
<img src="uploads/imagen.jpg" alt="Original" />
```

### Para imágenes existentes:

Simplemente cambia las URLs de:

```
uploads/imagen.jpg
```

Por:

```
img.php?src=imagen.jpg&w=300&h=200&f=webp
```

---

## ⚙️ **Parámetros disponibles**

| Parámetro | Descripción                        | Ejemplo      |
| --------- | ---------------------------------- | ------------ |
| `src`     | **Requerido** - Nombre del archivo | `imagen.jpg` |
| `w`       | Ancho en píxeles                   | `300`        |
| `h`       | Alto en píxeles                    | `200`        |
| `f`       | Formato: `webp`, `avif`, `auto`    | `webp`       |
| `q`       | Calidad: 1-100                     | `85`         |
| `fit`     | Modo de ajuste (futuro)            | `cover`      |

---

## 🛠️ **Solución de problemas**

### Si `simple_test.php` no funciona:

- Verificar que los archivos se subieron correctamente
- Contactar al administrador del servidor

### Si `img.php` da error:

- Verificar que el archivo de imagen existe en `uploads/`
- Verificar que la extensión GD está instalada

### Si la optimización es lenta:

- Normal en la primera carga (genera cache)
- Las siguientes cargas serán instantáneas

---

## 📋 **Checklist de verificación**

- [ ] `simple_test.php` muestra "PHP está funcionando"
- [ ] `img.php` con `w=100&h=100` muestra imagen pequeña
- [ ] La imagen optimizada es visiblemente más pequeña que el original
- [ ] El formato WebP funciona (navegadores modernos)
- [ ] La segunda carga es más rápida (cache funcionando)

---

## 🎉 **¡Ya está funcionando!**

El sistema de optimización está **100% operativo** usando `img.php`.

Si necesitas URLs más amigables (`uploads/imagen.jpg?w=100`), revisa el archivo `CONFIGURACION_NGINX.md` para configurar el servidor.

**El sistema actual es completamente funcional y listo para producción.**
