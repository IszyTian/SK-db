# Use an official PHP image with Apache
FROM php:8.2-apache

# Install extensions needed for database connections (PDO)
# Un-comment the ones you need based on your database type:

# For PostgreSQL:
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql

# For MySQL:
# RUN docker-php-ext-install pdo pdo_mysql

# Copy your website files to the Apache server directory
COPY . /var/www/html/

# Expose port 80 for web traffic
EXPOSE 80
