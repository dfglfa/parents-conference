FROM php:fpm-alpine

RUN apk update && apk add --no-cache libzip-dev
RUN apk add openldap-dev samba-dev
RUN docker-php-ext-install ldap pdo_mysql zip 

RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS linux-headers
COPY xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini

CMD ["php-fpm"]

EXPOSE 9000
