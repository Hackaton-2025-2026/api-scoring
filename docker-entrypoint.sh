#!/bin/bash
set -e

echo "📦 Waiting for PostgreSQL to be ready..."

# Extraire les infos depuis DATABASE_URL
# DATABASE_URL=postgres://user:password@host:port/dbname
DB_URL=${DATABASE_URL}
DB_USER=$(echo $DB_URL | sed -E 's|postgres://([^:]+):.*|\1|')
DB_PASS=$(echo $DB_URL | sed -E 's|postgres://[^:]+:([^@]+)@.*|\1|')
DB_HOST=$(echo $DB_URL | sed -E 's|postgres://[^@]+@([^:/]+).*|\1|')
DB_PORT=$(echo $DB_URL | sed -E 's|postgres://[^@]+@[^:/]+:([0-9]+).*|\1|')
DB_NAME=$(echo $DB_URL | sed -E 's|.*/([^/?]+).*|\1|')

MAX_TRIES=15
i=0
until pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USER >/dev/null 2>&1 || [ $i -eq $MAX_TRIES ]; do
  i=$((i+1))
  echo "Trying to connect to database ($i/$MAX_TRIES)..."
  sleep 2
done

if [ $i -eq $MAX_TRIES ]; then
  echo "❌ PostgreSQL not reachable!"
  exit 1
fi

echo "📦 Database ready, running schema update and loading fixtures..."

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
