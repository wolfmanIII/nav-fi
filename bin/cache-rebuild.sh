#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_NAME="${1:-${APP_ENV:-dev}}"

echo "Rebuilding Symfony cache (env: ${ENV_NAME})..."
XDEBUG_MODE=off php "${ROOT_DIR}/bin/console" cache:clear --no-warmup --env="${ENV_NAME}"
XDEBUG_MODE=off php "${ROOT_DIR}/bin/console" cache:warmup --env="${ENV_NAME}"
echo "Done."
