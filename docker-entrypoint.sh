#!/bin/bash
set -e

# Railway inject PORT env var — Apache harus listen di port itu
PORT=${PORT:-80}
echo "Starting Apache on port $PORT..."

# Update Apache port config
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/:80>/:$PORT>/" /etc/apache2/sites-enabled/000-default.conf

# Arahkan Apache error log ke stderr agar Railway bisa capture
echo "ErrorLog /proc/self/fd/2" >> /etc/apache2/apache2.conf
echo "CustomLog /proc/self/fd/1 combined" >> /etc/apache2/apache2.conf

# Pastikan folder runtime ada dan writable
mkdir -p /var/www/html/application/cache/sessions \
         /var/www/html/application/cache/import_tmp \
         /var/www/html/application/logs
chmod -R 777 /var/www/html/application/cache \
             /var/www/html/application/logs \
             /var/www/html/uploads

exec apache2-foreground
