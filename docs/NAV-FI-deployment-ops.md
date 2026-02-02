# Nav-Fi³ – Deployment & Operations Guide

> **Scope**: Google Cloud Run, Docker, and Production Maintenance.

## 1. Cloud Run Deployment Lifecycle

### Prerequisites
- Google Cloud Project with Billing.
- `gcloud` CLI and Docker (local or Cloud Build).

### Initial Setup & Secrets
Store sensitive data in **Secret Manager**:
- `nav-fi-app-secret`: Symfony APP_SECRET.
- `nav-fi-db-url`: `postgresql://user:pass@/cloudsql/PROJECT:REGION:INSTANCE/DB`.
- `nav-fi-google-id` / `google-secret`: OAuth credentials.

### Deployment Command
```bash
gcloud run deploy nav-fi \
  --image gcr.io/$PROJECT_ID/nav-fi:latest \
  --platform managed \
  --region $REGION \
  --memory 512Mi --cpu 1 \
  --set-env-vars="APP_ENV=prod" \
  --set-secrets="APP_SECRET=nav-fi-app-secret:latest,DATABASE_URL=nav-fi-db-url:latest,GOOGLE_CLIENT_ID=nav-fi-google-id:latest,GOOGLE_CLIENT_SECRET=nav-fi-google-secret:latest" \
  --add-cloudsql-instances="PROJECT:REGION:INSTANCE"
  --add-cloudsql-instances="PROJECT:REGION:INSTANCE"
```

### Deployment Scripts (Recommended)
Instead of running manual commands, use the provided scripts:

1.  **`scripts/migrate_and_deploy.sh`** (**Recommended**):
    *   Builds the image.
    *   Runs database migrations safely via a Cloud Run Job.
    *   Deploys the application ONLY if migrations succeed.
    *   *Safe to run even if no DB changes are present (migrations will just check).*

2.  **`scripts/deploy.sh`**:
    *   Only builds and deploys.
    *   *Risky if you have pending database changes.*

For secrets setup, refer to [`docs/setup_secrets_explained.md`](./setup_secrets_explained.md).

## 2. Production Maintenance (Cloud Run Jobs)
Since containers are ephemeral, use **Cloud Run Jobs** for one-off tasks.

### Common Commands
```bash
# Clear Cache
gcloud run jobs execute nav-fi-cli --args="/bin/bash,-c,php bin/console cache:clear"

# Run Migrations
gcloud run jobs execute nav-fi-cli --args="/bin/bash,-c,php bin/console doctrine:migrations:migrate --no-interaction"

# Database Dump
gcloud run jobs execute nav-fi-cli --args="/bin/bash,-c,php bin/console app:db:dump --file=/tmp/backup.dump && gsutil cp /tmp/backup.dump gs://backups/"
```

## 3. Best Practices & Performance
Based on structural analysis of the Nav-Fi container:

1.  **Multi-Stage Build**: Ensure `Dockerfile` uses stages to exclude Node.js, npm, and dev dependencies from the final production image.
2.  **Cold Start Optimization**:
    *   Avoid `cache:clear` in `docker-entrypoint.sh`. The image should be immutable.
    *   Pre-warm the cache during the `docker build` phase.
3.  **Database Strategy**:
    *   For high traffic, run migrations as a separate **Cloud Run Job** before deployment.
4.  **Security**:
    *   Run the container as a non-root user (`www-data`).
    *   Use `Secret Manager` for all credentials.

## 4. Monitoring & Troubleshooting
- **Logs**: `gcloud run services logs tail nav-fi`.
- **Health Check**: Endpoint `/health` returns JSON status.
- **Scaling**: Default scales to zero when idle. Use `--min-instances` to avoid cold starts if budget allows.
