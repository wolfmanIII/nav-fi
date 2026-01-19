#!/bin/bash
set -e

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Importing context on database..."
php bin/console app:context:import

echo "Clearing cache..."
php bin/console cache:clear --no-warmup

echo "Warming up cache..."
php bin/console cache:warmup

echo "Starting supervisor..."
exec /usr/bin/supervisord
