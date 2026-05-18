FROM php:8.2-apache

# Install ekstensi PHP yang diperlukan
RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libjpeg-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Fix MPM conflict: hapus .load + .conf event/worker, aktifkan hanya prefork
RUN find /etc/apache2 -name "*.load" -exec \
        sed -i 's/^\(LoadModule mpm_\)/#\1/' {} \; \
    && rm -f /etc/apache2/mods-enabled/mpm_event.conf \
             /etc/apache2/mods-enabled/mpm_worker.conf \
    && echo "LoadModule mpm_prefork_module /usr/lib/apache2/modules/mod_mpm_prefork.so" \
        > /etc/apache2/mods-enabled/mpm_prefork.load \
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
