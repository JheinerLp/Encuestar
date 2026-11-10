# =====================================================================
# Dockerfile para SIRCE Cali
# Apache + PHP 8.2 con extensiones PDO MySQL
# =====================================================================
FROM php:8.2-apache

# Actualizar paquetes e instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli \
    && a2enmod rewrite

# Configurar directorio de trabajo
WORKDIR /var/www/html/Encuesta

# Copiar código fuente
COPY . /var/www/html/Encuesta/

# Permisos de Apache
RUN chown -R www-data:www-data /var/www/html/Encuesta \
    && chmod -R 755 /var/www/html/Encuesta

EXPOSE 80

CMD ["apache2-foreground"]
