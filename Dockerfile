FROM dunglas/frankenphp:php8.3-alpine

# Install PHP extensions needed by Laravel + PostgreSQL
RUN install-php-extensions \
    pdo_pgsql \
    pcntl \
    intl \
    zip \
    opcache

WORKDIR /app

# Copy application source code
COPY . /app

# Ensure writable permissions for runtime directories
RUN chmod -R 777 /app/storage /app/bootstrap/cache

# Server port configuration for FrankenPHP/Caddy
EXPOSE 8000

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
