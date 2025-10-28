# ----------------------------
# Dockerfile Symfony pour Render
# ----------------------------

# Utiliser PHP 8.1 avec Apache
FROM php:8.1-apache

# Définir le répertoire de travail
WORKDIR /var/www/html

# Installer les dépendances système et extensions PHP nécessaires à Symfony
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev cron libicu-dev libxml2-dev zlib1g-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# Définir le document root d'Apache sur le dossier public de Symfony
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copier Composer depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les fichiers du projet avant d'exécuter composer
COPY . /var/www/html

# Installer les dépendances PHP du projet
RUN composer install --no-dev --optimize-autoloader

# Configurer les permissions pour Apache et Symfony
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/public \
    && chmod -R 777 /var/www/html/var

# Copier le cronjob (optionnel)
COPY cronjob /etc/cron.d/cronjob
RUN chmod 0644 /etc/cron.d/cronjob \
    && crontab /etc/cron.d/cronjob

# Exposer le port 80 (Apache)
EXPOSE 80

# Lancer cron et Apache en avant-plan
CMD ["/bin/bash", "-c", "cron && apache2-foreground"]
