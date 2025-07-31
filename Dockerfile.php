FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libavif-dev \
    git \
    unzip

# Configurar e instalar extensiones GD (para imágenes)
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Instalar otras extensiones útiles
RUN docker-php-ext-install \
    opcache \
    pdo_mysql \
    mysqli \
    exif

# Configurar OPcache para mejor rendimiento
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini

# Configurar PHP para archivos grandes
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

# Crear directorio de trabajo
WORKDIR /var/www/html

# Establecer permisos correctos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"] 