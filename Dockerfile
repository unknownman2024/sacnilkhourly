FROM php:8.2-apache

# Enable Apache mod_rewrite (if needed)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy your PHP files into the container
COPY . .

# Expose default port
EXPOSE 80
