FROM php:8.1-fpm

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /var/www/html
COPY . .

RUN mkdir -p uploads/songs uploads/covers uploads/profiles && \
    chown -R www-data:www-data uploads && \
    chmod -R 777 uploads

CMD ["php-fpm"]
