# Use the official PHP 8.2 image with Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory inside the container
WORKDIR /var/www/html

# Copy all project files to container
COPY . .

# Install required PHP extensions (e.g., DOM and cURL)
RUN docker-php-ext-install dom curl

# Optional: expose port 80
EXPOSE 80
