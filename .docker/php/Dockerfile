FROM php:8.5-fpm-alpine3.23

RUN apk add --no-cache \
      icu-libs \
      libzip \
    && apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS \
      icu-dev \
      libzip-dev \
      linux-headers \
    && docker-php-ext-install -j"$(nproc)" \
      bcmath \
      intl \
      pcntl \
      pdo_mysql \
      sockets \
      zip \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
