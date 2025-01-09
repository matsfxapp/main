FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2enmod rewrite

WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

RUN chmod -R 755 /var/www/html