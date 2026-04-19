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
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint

RUN chmod +x /usr/local/bin/app-entrypoint \
    && mkdir -p /var/www/html/public/uploads/member_photos \
        /var/www/html/public/uploads/checkin_photos \
        /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/storage

ENTRYPOINT ["/usr/local/bin/app-entrypoint"]
CMD ["php-fpm"]
