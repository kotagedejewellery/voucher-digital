#!/bin/sh
set -eu
test -n "${APP_KEY:-}" || { echo 'APP_KEY belum diatur.' >&2; exit 1; }
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private
chown -R www-data:www-data storage bootstrap/cache
if [ "$1" = "apache2-foreground" ]; then
  exec "$@"
fi
exec su -s /bin/sh www-data -c 'exec "$@"' -- sh "$@"
