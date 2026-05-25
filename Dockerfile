# Dockerfile - PHP 8.1 + Apache minimal for GradebookRA
FROM php:8.1-apache

# Install required extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
  && docker-php-ext-install pdo pdo_mysql \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable mod_rewrite (if required by the app)
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Ensure ca.pem (if present) is available at project root
# (config/database.php expects it at ../ca.pem relative to config/)

# Set minimal permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Default command (Apache runs in foreground)
CMD ["apache2-foreground"]
