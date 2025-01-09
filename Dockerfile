FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2enmod rewrite
RUN chown -R www-data:www-data /var/www/html
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
RUN echo "ServerName 0.0.0.0" >> /etc/apache2/apache2.conf
COPY . /var/www/html

WORKDIR /var/www/html

