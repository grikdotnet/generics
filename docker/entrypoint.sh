#!/bin/sh

# Ensure the socket directory has correct permissions
mkdir -p /var/run
chown -R www-data:www-data /var/run
chmod 755 /var/run

nginx -c /var/www/docker/nginx.conf 2>&1

php-fpm 2>&1 &

wait $!
