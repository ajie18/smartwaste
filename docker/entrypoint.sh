#!/bin/sh
set -e

php artisan package:discover --ansi
php artisan migrate --force
php artisan storage:link --force
php artisan config:cache
php artisan view:cache

if [ "$#" -gt 0 ]; then
	exec "$@"
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"