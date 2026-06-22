###############################################################################
# Stage 1 — Composer dependencies
###############################################################################
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    && true

###############################################################################
# Stage 2 — Application image (PHP-FPM + Nginx + Supervisor)
###############################################################################
FROM php:8.4-fpm-alpine

LABEL maintainer="dev"

# ── System dependencies ──────────────────────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    git \
    unzip \
    # PHP extension build deps
    libpng-dev \
    libxml2-dev \
    oniguruma-dev \
    libzip-dev \
    icu-dev \
    && docker-php-ext-install \
    pdo_mysql \
    bcmath \
    zip \
    intl \
    && apk add --no-cache --virtual .ext-deps $PHPIZE_DEPS linux-headers \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .ext-deps \
    && rm -rf /var/cache/apk/*

# ── Composer binary ──────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Config files ─────────────────────────────────────────────────────────────
COPY docker/nginx/default.conf   /etc/nginx/http.d/default.conf
COPY docker/php/php.ini          /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf     /etc/supervisord.conf
COPY docker/entrypoint.sh        /usr/local/bin/entrypoint.sh
RUN  chmod +x /usr/local/bin/entrypoint.sh

# ── Application source ───────────────────────────────────────────────────────
WORKDIR /var/www/html
COPY . .

# Use pre-built vendor if available (dev: override via volume mount)
COPY --from=vendor /app/vendor ./vendor

# ── Bootstrap cache — cleared at runtime by entrypoint.sh ───────────────────
# (Needed here too for non-volume builds / production images)
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

# ── User alignment ───────────────────────────────────────────────────────────
# Match www-data uid/gid to the host user (default 1000) so that volume-mounted
# files have consistent ownership between host and container.
# In production, pass --build-arg APP_UID=<deploy-user-uid> to override.
ARG APP_UID=1000
ARG APP_GID=1000
RUN deluser nginx 2>/dev/null || true \
    && delgroup www-data 2>/dev/null || true \
    && deluser  www-data 2>/dev/null || true \
    && addgroup -g ${APP_GID} www-data \
    && adduser  -u ${APP_UID} -G www-data -s /sbin/nologin -D www-data

# ── Permissions ──────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
         -type d -exec chmod 750 {} \; \
    && find /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
         -type f -exec chmod 640 {} \; \
    && mkdir -p /run/nginx /var/lib/nginx/tmp /var/lib/nginx/logs \
    && chown -R www-data:www-data /var/log/nginx /run/nginx /var/lib/nginx \
    && mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/log/supervisor

USER www-data:www-data

EXPOSE 8080

# ── Healthcheck ──────────────────────────────────────────────────────────────
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://localhost:8080/ || exit 1

# entrypoint.sh esegue: package:discover, config:cache, route:cache,
# migrate --force, fix permessi — poi avvia supervisord
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
