FROM php:8.2-apache

# Install ekstensi PHP yang diperlukan
RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libjpeg-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Fix MPM conflict: hapus semua MPM symlink, aktifkan hanya mpm_prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_event.* \
          /etc/apache2/mods-enabled/mpm_worker.* \
          /etc/apache2/mods-enabled/mpm_prefork.* \
    && ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && a2enmod rewrite

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
             uploads/dokumen uploads/lhr uploads/permohonan \
             uploads/capaian \
             uploads/landing/pejabat uploads/landing/slideshow \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 application/cache application/logs uploads

# Entrypoint: handle Railway $PORT + logging
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

EXPOSE 80

CMD ["/docker-entrypoint.sh"]
