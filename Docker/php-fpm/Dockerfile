FROM php:fpm-alpine

RUN apk update && apk add --no-cache libzip-dev
RUN docker-php-ext-install pdo_mysql zip

CMD ["php-fpm"]

EXPOSE 9000
