# Utilisez une image PHP comme base
FROM php:8.1-apache

# Installez les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    cron \
    && docker-php-ext-install pdo pdo_mysql zip

# Retournez au répertoire de travail principal
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurez le serveur web Apache
RUN a2enmod rewrite && \
    service apache2 restart

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Exposez le port sur lequel Symfony écoute (par défaut 8000)
EXPOSE 8000

# Add cron job
COPY cronjob /etc/cron.d/cronjob
RUN chmod 0644 /etc/cron.d/cronjob
RUN crontab /etc/cron.d/cronjob

# Start cron service
CMD ["/bin/bash", "-c", "cron && apache2-foreground"]
