#!/bin/bash
set -e

echo "📦 Running Symfony initialization..."

# Ensure var directories exist and are writable
mkdir -p var/cache var/log
chown -R www-data:www-data var
chmod -R 777 var

# Clear cache for prod
php bin/console cache:clear --no-warmup

echo "🚀 Starting Apache..."
exec apache2-foreground
