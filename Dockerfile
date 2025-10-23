FROM php:8.1-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create upload directories with proper permissions
RUN mkdir -p uploads/songs uploads/covers uploads/profiles && \
    chown -R www-data:www-data uploads && \
    chmod -R 755 uploads && \
    chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80