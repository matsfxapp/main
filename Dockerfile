FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN chown -R www-data:www-data /var/www/html/uploads
RUN chmod -R 755 /var/www/html/uploads

RUN a2enmod rewrite
RUN mkdir -p /var/www/html/uploads/profiles

WORKDIR /var/www/html

