FROM php:8.1-fpm

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /var/www/html
COPY . .

RUN mkdir -p /var/www/html/uploads/songs /var/www/html/uploads/covers /var/www/html/uploads/profiles
RUN chown -R www-data:www-data /var/www/html/uploads
RUN chmod -R 777 /var/www/html/uploads
