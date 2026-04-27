FROM composer:2.8 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    --no-scripts

FROM php:8.3-fpm-bookworm AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY docker/php/php.ini         /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/fpm-pool.conf   /usr/local/etc/php-fpm.d/zz-app-pool.conf
COPY docker/php/entrypoint.sh   /usr/local/bin/app-entrypoint

RUN chmod +x /usr/local/bin/app-entrypoint \
    && mkdir -p /var/www/html/public/uploads/member_photos \
        /var/www/html/public/uploads/checkin_photos \
        /var/www/html/storage/logs \
        /var/log/php-fpm \
    && chown -R www-data:www-data \
        /var/www/html/public/uploads \
        /var/www/html/storage \
        /var/log/php-fpm \
    # Remove world-writable permissions from app source (uploaded files are
    # handled by entrypoint; source files should be read-only to www-data).
    && find /var/www/html -not -path '*/uploads/*' -not -path '*/storage/*' \
            -not -path '*/vendor/*' -type f -exec chmod 644 {} + \
    && find /var/www/html -not -path '*/uploads/*' -not -path '*/storage/*' \
            -not -path '*/vendor/*' -type d -exec chmod 755 {} +

ENTRYPOINT ["/usr/local/bin/app-entrypoint"]
CMD sh -c "echo PORT=\$PORT; php -S 0.0.0.0:\$PORT -t public"
