FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install required PHP extensions for MySQL/XAMPP compatibility
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy PHP application files into Apache root
COPY . /var/www/html/

EXPOSE 80