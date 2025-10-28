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

# Fix permissions
mkdir -p var/cache var/log
chmod -R 777 var

# Force update schema
php bin/console doctrine:schema:update --force

# Load fixtures
php bin/console doctrine:fixtures:load --no-interaction || true

# Clear cache
php bin/console cache:clear --no-warmup

echo "🚀 Starting Apache..."
exec apache2-foreground
