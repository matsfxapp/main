FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo "upload_max_filesize=10M" > /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size=12M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "file_uploads=On" >> /usr/local/etc/php/conf.d/uploads.ini


RUN a2enmod rewrite
RUN mkdir -p /var/www/html/uploads/profiles
WORKDIR /var/www/html
COPY . .


