FROM debian:bookworm-slim

ENV DEBIAN_FRONTEND=noninteractive

# Install Apache + PHP + ekstensi — libapache2-mod-php otomatis aktifkan mpm_prefork
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

# Konfigurasi Apache: AllowOverride All untuk .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
        /etc/apache2/apache2.conf

# Prioritaskan index.php di atas index.html agar aplikasi PHP tidak tertimpa
RUN sed -i 's/DirectoryIndex .*/DirectoryIndex index.php index.html/' \
        /etc/apache2/mods-enabled/dir.conf

# Hapus default Apache page sebelum copy project
RUN rm -f /var/www/html/index.html

# Set working directory
WORKDIR /var/www/html

# Copy semua file project
COPY . .

# Hapus index.html lagi setelah COPY (pastikan tidak ada sisa)
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

# Entrypoint: handle Railway $PORT + stdout logging
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

EXPOSE 80

CMD ["/docker-entrypoint.sh"]
