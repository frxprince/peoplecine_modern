#!/usr/bin/env bash
set -euo pipefail

mkdir -p \
    /var/www/html/bootstrap/cache \
    /var/www/html/storage/app/private \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/peoplecine_data/bootstrap/sqlite \
    /var/www/peoplecine_data/legacy/wboard/icons \
    /var/www/peoplecine_data/legacy/wboard/uploads

chown -R www-data:www-data \
    /var/www/html/bootstrap/cache \
    /var/www/html/storage \
    /var/www/peoplecine_data/bootstrap/sqlite \
    /var/www/peoplecine_data/legacy/wboard || true

chmod -R ug+rwX \
    /var/www/html/bootstrap/cache \
    /var/www/html/storage \
    /var/www/peoplecine_data/bootstrap/sqlite \
    /var/www/peoplecine_data/legacy/wboard || true

exec "$@"
