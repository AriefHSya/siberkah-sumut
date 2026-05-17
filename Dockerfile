FROM php:8.2-apache

# Install ekstensi PHP yang diperlukan
RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libjpeg-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Aktifkan mod_rewrite Apache
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy semua file project
COPY . .

# Konfigurasi Apache untuk .htaccess
RUN echo '<Directory /var/www/html>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/siberkah.conf \
    && a2enconf siberkah

# Buat folder yang diperlukan dan set permission
RUN mkdir -p application/cache/sessions \
             application/cache/import_tmp \
             application/logs \
             uploads/dokumen \
             uploads/lhr \
             uploads/permohonan \
             uploads/capaian \
             uploads/landing/pejabat \
             uploads/landing/slideshow \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 application/cache \
    && chmod -R 777 application/logs \
    && chmod -R 777 uploads

EXPOSE 80
