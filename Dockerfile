# Use PHP 8.1 with Apache
FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    cron \
    libicu-dev \
    libxml2-dev \
    zlib1g-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set Apache document root to Symfony public folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/public \
    && chmod -R 777 /var/www/html/var

# Copy and setup cron job
COPY cronjob /etc/cron.d/cronjob
RUN chmod 0644 /etc/cron.d/cronjob \
    && crontab /etc/cron.d/cronjob

# Expose port 80 (Apache default)
EXPOSE 80

# Start cron and Apache
CMD ["/bin/bash", "-c", "cron && apache2-foreground"]
