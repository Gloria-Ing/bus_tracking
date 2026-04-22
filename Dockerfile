FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable rewrite
RUN a2enmod rewrite

# Copy project
COPY . /var/www/html/

# IMPORTANT: fix Apache port binding
ENV PORT=80

# Tell Apache to use Railway port
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf

EXPOSE 80