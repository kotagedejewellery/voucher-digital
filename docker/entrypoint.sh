#!/bin/sh
set -eu
test -n "${APP_KEY:-}" || { echo 'APP_KEY belum diatur.' >&2; exit 1; }
php -r '$key = getenv("APP_KEY"); if (str_starts_with($key, "base64:")) { $key = base64_decode(substr($key, 7), true); } exit(is_string($key) && strlen($key) === 32 ? 0 : 1);' \
  || { echo 'APP_KEY harus berupa key Laravel 32-byte yang valid.' >&2; exit 1; }
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private
chown -R www-data:www-data storage bootstrap/cache
if [ "$1" = "apache2-foreground" ]; then
  exec "$@"
fi
exec su -s /bin/sh www-data -c 'exec "$@"' -- sh "$@"
