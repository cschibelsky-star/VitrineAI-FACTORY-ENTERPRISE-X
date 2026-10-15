#!/bin/sh
set -eu
umask 077
mkdir -p /data/storage/framework/cache/data /data/storage/framework/sessions /data/storage/framework/views /data/storage/logs /app/bootstrap/cache
if [ ! -f /data/app-key ]; then php -r 'file_put_contents("/data/app-key", "base64:".base64_encode(random_bytes(32)));'; fi
if [ ! -f /data/database.sqlite ]; then touch /data/database.sqlite; fi
php runtime/hml/initialize.php
chown -R www-data:www-data /data /app/bootstrap/cache
exec php-fpm -F
