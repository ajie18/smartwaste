#!/bin/sh
set -e

# Railway supplies PORT at runtime; Apache must listen on that port.
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/*:80>/*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

php artisan package:discover --ansi
php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan view:cache

if [ "$#" -gt 0 ]; then
	exec "$@"
fi

exec apache2-foreground