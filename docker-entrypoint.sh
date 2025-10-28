#!/bin/bash
set -e

echo "📦 Running Symfony initialization..."

# Ensure directories exist and have correct permissions
mkdir -p var/cache var/log
chown -R www-data:www-data var
chmod -R 777 var/cache var/log

# Run database migrations at container startup
php bin/console doctrine:migrations:migrate --no-interaction || true

# Clear cache for prod
php bin/console cache:clear --no-warmup || true

echo "🚀 Starting Apache..."
exec apache2-foreground
