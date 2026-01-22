#!/bin/bash
set -e

# Migrazione spostata su Cloud Run Job (vedi scripts/migrate_and_deploy.sh)
# php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Starting supervisor..."
exec /usr/bin/supervisord
