FROM php:8.2-fpm-alpine

WORKDIR /var/www/mi3cda-chat

# Installe les dépendances + Caddy
RUN apk add --no-cache \
    git \
    postgresql-dev \
    icu-dev \
    caddy \
    supervisor \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_pgsql intl opcache

# Configuration PHP optimisée
RUN echo 'opcache.enable=1' >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'opcache.memory_consumption=128' >> /usr/local/etc/php/conf.d/opcache.ini

# Installe Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie les fichiers
COPY . .

# Installe les dépendances Composer
RUN composer install --no-dev --optimize-autoloader --no-scripts \
    && composer dump-autoload --optimize --classmap-authoritative

# COMPILE LES ASSETS
RUN php bin/console asset-map:compile || echo "AssetMapper not configured, skipping"

# Permissions
RUN chown -R www-data:www-data /var/www/mi3cda-chat/var \
    && chown -R www-data:www-data /var/www/mi3cda-chat/public

# Configuration Caddy
RUN mkdir -p /etc/caddy && cat > /etc/caddy/Caddyfile <<'EOF'
:8000 {
    root * /var/www/mi3cda-chat/public
    php_fastcgi 127.0.0.1:9000
    file_server
    encode gzip
}
EOF

# Configuration Supervisord
RUN cat > /etc/supervisord.conf <<'EOF'
[supervisord]
nodaemon=true
user=root

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:caddy]
command=caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

EXPOSE 8000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
