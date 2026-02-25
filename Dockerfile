FROM php:8.2-apache

# Install dependencies for AWS SDK and system tools
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy source code
COPY src/ /var/www/html/

# Install PHP dependencies (AWS SDK)
RUN composer install --no-dev --optimize-autoloader

# Fix permissions for volumes
RUN chown -R www-www-data /var/www/html/storage
RUN chmod -R 777 /var/www/html/storage

# Enable Apache Rewrite (optional, good practice)
RUN a2enmod rewrite

EXPOSE 80
