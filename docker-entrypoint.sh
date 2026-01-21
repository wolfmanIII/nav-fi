#!/bin/bash
set -e

# Migration moved to Cloud Run Job (see scripts/migrate_and_deploy.sh)
# php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Starting supervisor..."
exec /usr/bin/supervisord
