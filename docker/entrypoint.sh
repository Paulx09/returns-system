#!/bin/sh
set -e

# Cache configuration for container environment
php artisan config:clear
php artisan config:cache

exec frankenphp run --config /etc/frankenphp/Caddyfile
