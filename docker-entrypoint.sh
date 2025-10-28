#!/bin/bash
set -e

# Full PostgreSQL URL
DB_URL='postgresql://api_scoring_db_user:fY9YYZqdaoZ8EnKqeE6IPOn7oBWmKZ6L@dpg-d40ihmjuibrs73cs8bvg-a.frankfurt-postgres.render.com/api_scoring_db'

echo "📦 Waiting for PostgreSQL to be ready..."

# Extract parts from DB_URL
DB_USER=$(echo $DB_URL | cut -d':' -f2 | sed 's|//||')
DB_PASS=$(echo $DB_URL | cut -d':' -f3 | cut -d'@' -f1)
DB_HOST=$(echo $DB_URL | cut -d'@' -f2 | cut -d'/' -f1)
DB_NAME=$(echo $DB_URL | awk -F'/' '{print $NF}')
DB_PORT=5432  # default PostgreSQL port

MAX_TRIES=15
i=0
until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" >/dev/null 2>&1 || [ $i -eq $MAX_TRIES ]; do
  i=$((i+1))
  echo "Trying to connect to database ($i/$MAX_TRIES)..."
  sleep 2
done

if [ $i -eq $MAX_TRIES ]; then
  echo "❌ PostgreSQL not reachable!"
  exit 1
fi

echo "📦 Database ready, updating schema and loading fixtures..."

# Make sure var directories exist with all necessary subdirectories
mkdir -p var/cache/dev var/cache/prod var/log

# Fix permissions so the running process can write
chmod -R 777 var
chmod -R 777 var/cache var/log

# Ensure specific cache directories have write permissions
chmod -R 777 var/cache/dev var/cache/prod

# Force update schema
php bin/console doctrine:schema:update --force

# Load fixtures
php bin/console doctrine:fixtures:load --no-interaction || true

# Clear cache
php bin/console cache:clear --no-warmup

# Re-ensure permissions before starting Apache (Render might use different user)
echo "📦 Final permissions check..."
mkdir -p var/cache/prod
chmod -R 777 var/cache var/log

echo "🚀 Starting Apache..."
exec apache2-foreground
