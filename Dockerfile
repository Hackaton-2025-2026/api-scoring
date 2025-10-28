# Use official PHP + Apache image
FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Environment variables (these will be overridden by Render dashboard)
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev cron libicu-dev libxml2-dev zlib1g-dev postgresql-client \
    && docker-php-ext-install pdo pdo_pgsql intl zip opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Set Apache document root to Symfony public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-scripts --no-dev

# Copy the rest of the project
COPY . /var/www/html

RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/public

# Ensure directories exist and have correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/public \
    && chmod -R 777 /var/www/html/var/cache \
    && chmod -R 777 /var/www/html/var/log

RUN echo "APP_SECRET=your_app_secret_placeholder" > .env
RUN echo "DATABASE_URL=postgresql://api_scoring_db_user:fY9YYZqdaoZ8EnKqeE6IPOn7oBWmKZ6L@dpg-d40ihmjuibrs73cs8bvg-a.frankfurt-postgres.render.com/api_scoring_db" >> .env

# Note: Environment variables (APP_SECRET, DATABASE_URL) should be set in Render dashboard
# They will be available at runtime and override any .env file

# Copy cronjob if needed
COPY cronjob /etc/cron.d/cronjob
RUN chmod 0644 /etc/cron.d/cronjob \
    && crontab /etc/cron.d/cronjob

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose Apache port
EXPOSE 80

# Start container using entrypoint
CMD ["docker-entrypoint.sh"]
