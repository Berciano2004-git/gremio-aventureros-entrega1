FROM php:8.4-apache

WORKDIR /var/www/html
COPY gremio.php /var/www/html/index.php

EXPOSE 80
