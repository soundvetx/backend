FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    linux-headers \
    curl \
    libpng-dev \
    libzip-dev \
    zlib-dev \
    libxml2-dev \
    oniguruma-dev \
    nodejs \
    npm \
    mysql-client \
    autoconf \
    automake \
    g++ \
    make

RUN docker-php-ext-install gd zip pdo pdo_mysql

RUN pecl install -o -f xdebug \
    && docker-php-ext-enable xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./dockerfiles/php/php.ini /usr/local/etc/php/php.ini

WORKDIR /var/www

RUN mkdir -p /var/www/storage && chown -R www-data:www-data /var/www

EXPOSE 9000
EXPOSE 8000
EXPOSE 3000
