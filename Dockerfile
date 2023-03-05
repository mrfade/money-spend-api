FROM composer:2.3 as vendor

WORKDIR /app

COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer install \
  --no-interaction \
  --no-plugins \
  --no-scripts \
  --no-dev \
  --prefer-dist

COPY . .
RUN composer dump-autoload

FROM php:8.1-apache
WORKDIR /var/www/html

COPY --from=vendor app/vendor/ ./vendor/
COPY . .

EXPOSE 80