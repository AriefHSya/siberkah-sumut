FROM debian:bookworm-slim

ENV DEBIAN_FRONTEND=noninteractive

# Install Apache + PHP + ekstensi
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.2 \
    libapache2-mod-php8.2 \
    php8.2-mysqli \
    php8.2-zip \
    php8.2-gd \
    php8.2-xml \
    php8.2-mbstring \
    && a2enmod rewrite php8.2 \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Tulis ulang VirtualHost — eksplisit DirectoryIndex dan AllowOverride
RUN printf '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    DirectoryIndex index.php index.html\n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf

# Hapus default Apache index.html sebelum copy project
RUN rm -f /var/www/html/index.html

WORKDIR /var/www/html

# Copy semua file project
COPY . .

# Pastikan tidak ada index.html yang menimpa aplikasi
RUN rm -f /var/www/html/index.html

# Buat folder runtime dan set permission
RUN mkdir -p application/cache/sessions \
             application/cache/import_tmp \
             application/logs \
             uploads/dokumen uploads/lhr uploads/permohonan \
             uploads/capaian \
             uploads/landing/pejabat uploads/landing/slideshow \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 application/cache application/logs uploads

COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

EXPOSE 80
CMD ["/docker-entrypoint.sh"]
