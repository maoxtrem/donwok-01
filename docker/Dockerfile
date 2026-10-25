FROM php:8.3-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev git unzip \
    && docker-php-ext-install -j"$(nproc)" intl pdo_mysql opcache \
    && pecl install redis \
    && docker-php-ext-enable redis opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

CMD ["php-fpm", "-F"]
