# Use PHP with Apache
FROM php:8.2-apache

# Enable Apache rewrite (Laravel, routing, etc.)
RUN a2enmod rewrite

# Install PHP extensions (modify as needed)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose Apache port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
