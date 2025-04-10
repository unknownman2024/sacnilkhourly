# Use official PHP Apache image
FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory inside the container
WORKDIR /var/www/html

# Copy project files into the container
COPY . .

# Just confirm necessary extensions are enabled
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Open port 80 for HTTP traffic
EXPOSE 80
