FROM php:8.1-apache

WORKDIR /var/www/html

# Set environment variables for production build
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Installer dépendances système et PHP
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev cron libicu-dev libxml2-dev zlib1g-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache

# Activer mod_rewrite
RUN a2enmod rewrite

# Définir document root Symfony
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copier Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier composer.json et composer.lock pour installer les dépendances
COPY composer.json composer.lock ./

# Installer dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Copier le reste du projet
COPY . /var/www/html

# Permissions correctes
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/public \
    && chmod -R 777 /var/www/html/var

# Copier cron (optionnel)
COPY cronjob /etc/cron.d/cronjob
RUN chmod 0644 /etc/cron.d/cronjob \
    && crontab /etc/cron.d/cronjob

EXPOSE 80

CMD ["/bin/bash", "-c", "cron && apache2-foreground"]
