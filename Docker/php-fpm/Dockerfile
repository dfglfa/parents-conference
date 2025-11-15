FROM php:fpm-alpine

RUN apk update && apk add --no-cache libzip-dev
RUN apk add openldap-dev samba-dev
RUN docker-php-ext-install ldap pdo_mysql zip 

CMD ["php-fpm"]

EXPOSE 9000
