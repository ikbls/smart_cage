FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_pgsql pgsql

COPY . /var/www/html/

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]