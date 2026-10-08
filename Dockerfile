# Use an official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies and clean up cache to keep image size small
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Install both PDO MySQL and MySQLi extensions for Aiven MySQL connection
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Copy your website files to the Apache server directory
COPY . /var/www/html/

# Ensure Apache has the correct permissions to read your files
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 for web traffic
EXPOSE 80

RUN chown -R www-data:www-data /var/www/html


# Expose port 80 for web traffic
EXPOSE 80
