FROM php:8.2-apache

# Install MySQL + PDO extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache rewrite (optional but useful)
RUN a2enmod rewrite

# Copy project files into container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

EXPOSE 80