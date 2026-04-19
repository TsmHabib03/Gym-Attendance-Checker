#!/bin/sh
set -eu

mkdir -p \
    /var/www/html/public/uploads/member_photos \
    /var/www/html/public/uploads/checkin_photos \
    /var/www/html/storage/logs

chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/storage

exec "$@"
