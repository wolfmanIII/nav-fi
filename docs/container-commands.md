# Container Commands Guide

How to execute Symfony console commands on production containers running in Google Cloud Run.

---

## Overview

Cloud Run containers are **stateless** and **ephemeral**, so you can't SSH into them. Instead, use one of these methods:

1. **Cloud Run Jobs** - For one-off commands (recommended)
2. **Local Container** - Run production image locally
3. **Cloud Shell** - Interactive debugging (limited)

---

## Method 1: Cloud Run Jobs (Recommended)

Best for: **Scheduled tasks, maintenance, data imports**

### One-Time Command

```bash
# Execute a single Symfony command
gcloud run jobs create nav-fi-console \
  --image gcr.io/PROJECT_ID/nav-fi:latest \
  --region europe-west8 \
  --set-secrets="APP_SECRET=nav-fi-app-secret:latest,DATABASE_URL=nav-fi-db-url:latest" \
  --add-cloudsql-instances="PROJECT_ID:REGION:nav-fi-db" \
  --command="/bin/bash" \
  --args="-c","php bin/console cache:clear --env=prod"

# Execute the job
gcloud run jobs execute nav-fi-console

# View job logs
gcloud run jobs executions logs read nav-fi-console
```

### Reusable Job Template

Create a job once, execute many times with different commands:

```bash
# Create base job (without specific command)
gcloud run jobs create nav-fi-cli \
  --image gcr.io/PROJECT_ID/nav-fi:latest \
  --region europe-west8 \
  --set-secrets="APP_SECRET=nav-fi-app-secret:latest,DATABASE_URL=nav-fi-db-url:latest,GOOGLE_CLIENT_ID=nav-fi-google-id:latest,GOOGLE_CLIENT_SECRET=nav-fi-google-secret:latest" \
  --add-cloudsql-instances="PROJECT_ID:REGION:nav-fi-db" \
  --max-retries=0 \
  --task-timeout=10m

# Execute with custom command
gcloud run jobs execute nav-fi-cli \
  --args="/bin/bash,-c,php bin/console app:my-command"
```

### Common Commands

```bash
# Clear cache
gcloud run jobs execute nav-fi-cli \
  --args="/bin/bash,-c,php bin/console cache:clear --env=prod"

# Run migrations (if not auto-run)
gcloud run jobs execute nav-fi-cli \
  --args="/bin/bash,-c,php bin/console doctrine:migrations:migrate --no-interaction"

# Import data
gcloud run jobs execute nav-fi-cli \
  --args="/bin/bash,-c,php bin/console app:context:import --file=config/seed/context_seed.json"

# Database dump
gcloud run jobs execute nav-fi-cli \
  --args="/bin/bash,-c,php bin/console app:db:dump --file=/tmp/backup.dump && gsutil cp /tmp/backup.dump gs://YOUR_BUCKET/"
```

---

## Method 2: Local Container with Production Image

Best for: **Interactive debugging, exploring production state**

### Pull and Run Production Image

```bash
# Pull the exact image running in production
docker pull gcr.io/PROJECT_ID/nav-fi:latest

# Run interactive shell
docker run -it --rm \
  -e APP_ENV=prod \
  -e APP_SECRET="your-secret" \
  -e DATABASE_URL="postgresql://user:pass@host:5432/dbname" \
  gcr.io/PROJECT_ID/nav-fi:latest \
  /bin/bash

# Inside container:
php bin/console list
php bin/console cache:clear
php bin/console doctrine:schema:validate
```

### With Cloud SQL Proxy (Local Access to Production DB)

```bash
# Terminal 1: Start Cloud SQL Proxy
cloud-sql-proxy --port 5432 PROJECT_ID:REGION:nav-fi-db

# Terminal 2: Run container connected to proxy
docker run -it --rm \
  -e APP_ENV=prod \
  -e DATABASE_URL="postgresql://navfi_user:PASSWORD@host.docker.internal:5432/nav_fi_web" \
  gcr.io/PROJECT_ID/nav-fi:latest \
  /bin/bash
```

**⚠️ WARNING**: This connects to **production database**. Use with extreme caution.

---

## Method 3: Helper Script (Recommended)

Create a local script to simplify command execution.

### Create `scripts/cloud-console.sh`

```bash
#!/bin/bash
set -e

PROJECT_ID="nav-fi-prod"
REGION="europe-west8"
JOB_NAME="nav-fi-cli"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

if [ -z "$1" ]; then
  echo "Usage: $0 <symfony-command>"
  echo ""
  echo "Examples:"
  echo "  $0 cache:clear"
  echo "  $0 doctrine:migrations:migrate"
  echo "  $0 app:context:export --file=/tmp/export.json"
  exit 1
fi

COMMAND="$@"

echo -e "${YELLOW}Executing on Cloud Run:${NC} php bin/console $COMMAND"
echo ""

# Execute job
gcloud run jobs execute $JOB_NAME \
  --region $REGION \
  --args="/bin/bash,-c,php bin/console $COMMAND" \
  --wait

# Get execution name (most recent)
EXECUTION=$(gcloud run jobs executions list --job=$JOB_NAME --region=$REGION --limit=1 --format='value(name)')

echo ""
echo -e "${GREEN}Job completed. Logs:${NC}"
echo ""

# Show logs
gcloud run jobs executions logs read $EXECUTION --region=$REGION
```

### Usage

```bash
# Make executable
chmod +x scripts/cloud-console.sh

# Run commands
./scripts/cloud-console.sh cache:clear
./scripts/cloud-console.sh doctrine:migrations:status
./scripts/cloud-console.sh app:user:create admin@example.com --admin
```

---

## Common Use Cases

### 1. Clear Cache After Deploy

```bash
gcloud run jobs execute nav-fi-cli \
  --args="/bin/bash,-c,php bin/console cache:clear --env=prod"
```

### 2. Check Migration Status

```bash
gcloud run jobs execute nav-fi-cli \
  --args="/bin/bash,-c,php bin/console doctrine:migrations:status"
```

### 3. Create Admin User

```bash
gcloud run jobs execute nav-fi-cli \
  --args="/bin/bash,-c,php bin/console app:user:create admin@nav-fi.com --password=SecurePass123 --admin"
```

### 4. Export Database

```bash
# Create Cloud Storage bucket first
gsutil mb -l europe-west8 gs://nav-fi-backups

# Export database
gcloud run jobs execute nav-fi-cli \
  --args="/bin/bash,-c,php bin/console app:db:dump --file=/tmp/backup.dump && gsutil cp /tmp/backup.dump gs://nav-fi-backups/backup-$(date +%Y%m%d).dump"
```

### 5. Validate Database Schema

```bash
gcloud run jobs execute nav-fi-cli \
  --args="/bin/bash,-c,php bin/console doctrine:schema:validate"
```

---

## Scheduled Jobs (Cron Alternative)

Cloud Run Jobs can be scheduled via **Cloud Scheduler**.

### Example: Daily Cache Clear

```bash
# Create scheduler job
gcloud scheduler jobs create http daily-cache-clear \
  --location=europe-west8 \
  --schedule="0 2 * * *" \
  --uri="https://run.googleapis.com/v1/projects/PROJECT_ID/locations/europe-west8/jobs/nav-fi-cli:run" \
  --http-method=POST \
  --oauth-service-account-email=SERVICE_ACCOUNT_EMAIL \
  --message-body='{"overrides":{"containerOverrides":[{"args":["/bin/bash","-c","php bin/console cache:clear --env=prod"]}]}}'
```

---

## Troubleshooting

### Job Fails with "Error 1"

```bash
# View full error logs
gcloud run jobs executions logs read EXECUTION_NAME --region europe-west8

# Common causes:
# - Missing secrets: Add via --set-secrets
# - Database unreachable: Verify --add-cloudsql-instances
# - Command syntax error: Test locally first
```

### Job Times Out

```bash
# Increase timeout (default 10 minutes)
gcloud run jobs update nav-fi-cli \
  --task-timeout=30m \
  --region europe-west8
```

### Can't Access Database

```bash
# Verify Cloud SQL connection is configured
gcloud run jobs describe nav-fi-cli --region europe-west8 | grep cloudsql

# Add if missing
gcloud run jobs update nav-fi-cli \
  --add-cloudsql-instances="PROJECT_ID:REGION:nav-fi-db" \
  --region europe-west8
```

---

## Security Best Practices

1. **Never hardcode secrets** - Always use Secret Manager
2. **Limit job permissions** - Use dedicated service accounts
3. **Audit job executions** - Monitor via Cloud Logging
4. **Test locally first** - Validate commands before production
5. **Use read-only DB user** - For export/query jobs

---

## Useful Aliases

Add to `~/.bashrc` or `~/.zshrc`:

```bash
# Cloud Run console shortcut
alias cloud-console='./scripts/cloud-console.sh'

# Quick cache clear
alias cloud-cache='gcloud run jobs execute nav-fi-cli --region europe-west8 --args="/bin/bash,-c,php bin/console cache:clear"'

# View latest job logs
alias cloud-logs='gcloud run jobs executions logs read $(gcloud run jobs executions list --job=nav-fi-cli --region=europe-west8 --limit=1 --format="value(name)") --region europe-west8'
```

---

## Reference Links

- [Cloud Run Jobs Documentation](https://cloud.google.com/run/docs/create-jobs)
- [Cloud Scheduler Guide](https://cloud.google.com/scheduler/docs)
- [Symfony Console Commands](https://symfony.com/doc/current/console.html)
