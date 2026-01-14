# Cloud Run Deployment Guide

Complete guide to deploy **Nav-Fi** to Google Cloud Run.

---

## Prerequisites

1. **Google Cloud Account** with billing enabled
2. **gcloud CLI** installed and authenticated
3. **Docker** installed locally (for building images)
4. **Project built locally** (tested, migrations ready, assets compiled)

---

## 1. Initial Setup

### A. Google Cloud Project Setup

> [!NOTE]
> You can use the **same Google Cloud project** you already configured for Google OAuth authentication. There's no need to create a new project.

```bash
# If using an existing project (recommended)
# Use the same PROJECT_ID from your Google OAuth setup
export PROJECT_ID="your-existing-project-id"
export REGION="europe-west8"

# Set as active project
gcloud config set project $PROJECT_ID

# Enable required APIs
gcloud services enable run.googleapis.com
gcloud services enable cloudbuild.googleapis.com
gcloud services enable secretmanager.googleapis.com
gcloud services enable sqladmin.googleapis.com
```

**Alternative: Create a new project** (only if you prefer separation)

```bash
# Set project ID (change to your preferred name)
export PROJECT_ID="nav-fi-prod"
export REGION="europe-west8"

# Create project
gcloud projects create $PROJECT_ID --name="Nav-Fi Production"

# Set as active project
gcloud config set project $PROJECT_ID

# Enable required APIs (same as above)
gcloud services enable run.googleapis.com
gcloud services enable cloudbuild.googleapis.com
gcloud services enable secretmanager.googleapis.com
gcloud services enable sqladmin.googleapis.com
```

### B. Set Up PostgreSQL Database

**Option 1: Cloud SQL (Recommended for Production)**

```bash
# Create Cloud SQL instance
gcloud sql instances create nav-fi-db \
  --database-version=POSTGRES_18 \
  --tier=db-f1-micro \
  --region=$REGION

# Create database
gcloud sql databases create nav_fi_web --instance=nav-fi-db

# Create user
gcloud sql users create navfi_user \
  --instance=nav-fi-db \
  --password=CHANGE_ME_STRONG_PASSWORD

# Get connection name (save this!)
gcloud sql instances describe nav-fi-db --format='value(connectionName)'
# Output: PROJECT_ID:REGION:nav-fi-db
```

**Option 2: External PostgreSQL**
If using an external provider (Supabase, Neon, etc.), just note the connection URL.

---

## 2. Configure Secrets

Store sensitive data in **Secret Manager** (never in code or .env files):

```bash
# Create APP_SECRET (generate a random 32-char string)
echo $(openssl rand -base64 32) | gcloud secrets create nav-fi-app-secret --data-file=-

# Database URL (adjust if using external DB)
echo "postgresql://navfi_user:CHANGE_ME_STRONG_PASSWORD@/cloudsql/PROJECT_ID:REGION:nav-fi-db/nav_fi_web" \
  | gcloud secrets create nav-fi-db-url --data-file=-

# Google OAuth credentials (from Google Cloud Console)
echo "YOUR_CLIENT_ID.apps.googleusercontent.com" | gcloud secrets create nav-fi-google-id --data-file=-
echo "YOUR_CLIENT_SECRET" | gcloud secrets create nav-fi-google-secret --data-file=-

# Verify secrets
gcloud secrets list
```

---

## 3. Build and Push Docker Image

```bash
# Enable Container Registry (or Artifact Registry)
gcloud services enable containerregistry.googleapis.com

# Configure Docker to use gcloud
gcloud auth configure-docker

# Build image (from project root)
docker build -t gcr.io/$PROJECT_ID/nav-fi:latest .

# Push to Google Container Registry
docker push gcr.io/$PROJECT_ID/nav-fi:latest
```

**Alternative: Use Cloud Build** (recommended for CI/CD)

```bash
# Build image directly on Google Cloud (no local Docker needed)
gcloud builds submit --tag gcr.io/$PROJECT_ID/nav-fi:latest
```

---

## 4. Deploy to Cloud Run

### First Deployment

```bash
gcloud run deploy nav-fi \
  --image gcr.io/$PROJECT_ID/nav-fi:latest \
  --platform managed \
  --region $REGION \
  --allow-unauthenticated \
  --memory 512Mi \
  --cpu 1 \
  --min-instances 0 \
  --max-instances 10 \
  --timeout 60 \
  --port 8080 \
  --set-env-vars="APP_ENV=prod" \
  --set-secrets="APP_SECRET=nav-fi-app-secret:latest,DATABASE_URL=nav-fi-db-url:latest,GOOGLE_CLIENT_ID=nav-fi-google-id:latest,GOOGLE_CLIENT_SECRET=nav-fi-google-secret:latest" \
  --add-cloudsql-instances="PROJECT_ID:REGION:nav-fi-db"
```

**Explanation:**
- `--min-instances 0`: Scale to zero (save costs, but cold starts)
- `--max-instances 10`: Auto-scale up to 10 containers
- `--memory 512Mi`: Memory per container (adjust based on usage)
- `--cpu 1`: 1 vCPU per container
- `--timeout 60`: Max request timeout (increase if needed)
- `--add-cloudsql-instances`: Connects Cloud SQL via Unix socket

### Update Deployment

```bash
# Rebuild and push new image
docker build -t gcr.io/$PROJECT_ID/nav-fi:latest .
docker push gcr.io/$PROJECT_ID/nav-fi:latest

# Deploy update
gcloud run deploy nav-fi \
  --image gcr.io/$PROJECT_ID/nav-fi:latest \
  --region $REGION
```

---

## 5. Configure Custom Domain (Optional)

```bash
# Map custom domain
gcloud run domain-mappings create \
  --service nav-fi \
  --domain nav-fi.yourdomain.com \
  --region $REGION

# Follow DNS instructions to add CNAME record
# Then update Google OAuth redirect URIs to use new domain
```

---

## 6. Post-Deployment Configuration

### A. Update Google OAuth Consent Screen

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Navigate to **APIs & Services** > **OAuth consent screen**
3. Update **Authorized redirect URIs** to:
   ```
   https://YOUR_CLOUDRUN_URL/connect/google/check
   ```

### B. Test Health Endpoint

```bash
# Get service URL
SERVICE_URL=$(gcloud run services describe nav-fi --region $REGION --format='value(status.url)')

# Test health check
curl $SERVICE_URL/health
# Expected: {"status":"ok","timestamp":1234567890}
```

### C. Monitor Logs

```bash
# View real-time logs
gcloud run services logs tail nav-fi --region $REGION

# View specific errors
gcloud run services logs read nav-fi --region $REGION --filter="severity>=ERROR"
```

---

## 7. Cost Optimization

### Enable Always-Free Tier
Cloud Run offers **2 million requests/month** free.

```bash
# Set to scale to zero when idle (default)
gcloud run services update nav-fi \
  --min-instances 0 \
  --region $REGION
```

### Monitor Costs

```bash
# Set up budget alert (replace with your email)
gcloud billing budgets create \
  --billing-account=BILLING_ACCOUNT_ID \
  --display-name="Nav-Fi Monthly Budget" \
  --budget-amount=10USD \
  --threshold-rule=percent=50 \
  --threshold-rule=percent=90 \
  --all-updates-rule-pubsub-topic=projects/$PROJECT_ID/topics/budget-alerts \
  --all-updates-rule-monitoring-notification-channels=EMAIL_CHANNEL_ID
```

---

## 8. CI/CD Setup (Optional)

### Using GitHub Actions

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Cloud Run

on:
  push:
    branches:
      - main

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - uses: google-github-actions/auth@v1
        with:
          credentials_json: ${{ secrets.GCP_SA_KEY }}
      
      - uses: google-github-actions/setup-gcloud@v1
      
      - name: Build and Push
        run: |
          gcloud builds submit --tag gcr.io/${{ secrets.GCP_PROJECT_ID }}/nav-fi:latest
      
      - name: Deploy
        run: |
          gcloud run deploy nav-fi \
            --image gcr.io/${{ secrets.GCP_PROJECT_ID }}/nav-fi:latest \
            --region europe-west8
```

---

## 9. Troubleshooting

### Container Fails to Start

```bash
# Check startup logs
gcloud run services logs read nav-fi --region $REGION --limit=100

# Common issues:
# - Missing secrets: Check Secret Manager permissions
# - Database connection: Verify Cloud SQL proxy is enabled
# - Port mismatch: Ensure Nginx listens on port 8080
```

### Database Connection Errors

```bash
# Test Cloud SQL connection
gcloud sql connect nav-fi-db --user=navfi_user

# Verify service account has Cloud SQL Client role
gcloud projects add-iam-policy-binding $PROJECT_ID \
  --member="serviceAccount:SERVICE_ACCOUNT_EMAIL" \
  --role="roles/cloudsql.client"
```

### Migration Errors

Migrations run automatically via `docker-entrypoint.sh`. If they fail:

```bash
# View migration logs
gcloud run services logs read nav-fi --region $REGION --filter="Running database migrations"

# Manually execute migrations via Cloud Run Job (see container-commands.md)
```

---

## 10. Production Checklist

Before going live:

- [ ] Secrets configured in Secret Manager
- [ ] Google OAuth redirect URIs updated
- [ ] Custom domain mapped (if applicable)
- [ ] Database backups enabled
- [ ] Budget alerts configured
- [ ] Health endpoint responding (`/health`)
- [ ] Logs aggregation configured
- [ ] SSL/TLS certificate valid (automatic via Cloud Run)
- [ ] Performance tested (load testing)
- [ ] Error monitoring set up (e.g., Sentry)

---

## Useful Commands

```bash
# View service details
gcloud run services describe nav-fi --region $REGION

# Update environment variable
gcloud run services update nav-fi \
  --update-env-vars KEY=VALUE \
  --region $REGION

# Update secret
gcloud run services update nav-fi \
  --update-secrets=DATABASE_URL=nav-fi-db-url:latest \
  --region $REGION

# Delete service (careful!)
gcloud run services delete nav-fi --region $REGION
```

---

## Support Links

- [Cloud Run Documentation](https://cloud.google.com/run/docs)
- [Cloud SQL Proxy Guide](https://cloud.google.com/sql/docs/postgres/connect-run)
- [Secret Manager Best Practices](https://cloud.google.com/secret-manager/docs/best-practices)
- [Symfony Production Checklist](https://symfony.com/doc/current/deployment.html)
