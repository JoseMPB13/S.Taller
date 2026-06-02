# Usar la imagen oficial de PHP con Apache en su versión 8.2
FROM php:8.2-apache

# Instalar dependencias del sistema y extensiones de PHP necesarias para PDO MySQL, PDO PostgreSQL y FPDF (GD)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql pdo_pgsql

# Habilitar el módulo mod_rewrite de Apache para soportar rutas amigables (.htaccess)
RUN a2enmod rewrite

# Reconfigurar el DocumentRoot de Apache para que apunte al directorio 'public/' de la aplicación
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copiar todo el código de la aplicación al contenedor
COPY . /var/www/html

# Crear el directorio para guardar las facturas en PDF si no existe
RUN mkdir -p /var/www/html/public/invoices

# Asignar permisos correctos al usuario y grupo del servidor web (www-data)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/public/invoices

# Exponer el puerto 80 del servidor web Apache
EXPOSE 80
