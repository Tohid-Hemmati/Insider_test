#!/bin/bash
RUN mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/public/build
chown -R www-data:www-data /var/www/html/public/build
php artisan config:cache

php artisan migrate --force

php artisan db:seed --force
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf