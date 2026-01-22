#!/bin/bash
set -e

# ==============================================================================
# Script di deploy NAV-FI
# ==============================================================================

# 1. Configurazione - MODIFICA SE NECESSARIO
PROJECT_ID=$(gcloud config get-value project)
REGION="europe-west8"
SERVICE_NAME="nav-fi"
IMAGE_NAME="gcr.io/$PROJECT_ID/$SERVICE_NAME:latest"

echo "========================================================"
echo "🚀 NAV-FI DEPLOYMENT SYSTEM"
echo "========================================================"
echo "Project: $PROJECT_ID"
echo "Region:  $REGION"
echo "Service: $SERVICE_NAME"
echo "========================================================"

if [ -z "$PROJECT_ID" ]; then
    echo "❌ Error: No Google Cloud Project set."
    echo "   Run: gcloud config set project [YOUR_PROJECT_ID]"
    exit 1
fi

read -p "Are you sure you want to deploy to PRODUCTION? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "🚫 Deployment cancelled."
    exit 1
fi

# 2. Build e Push
echo ""
echo "📦 Building and Pushing Docker Image..."
echo "--------------------------------------------------------"
# Usa Google Cloud Build (niente Docker locale, più veloce)
gcloud builds submit --tag $IMAGE_NAME

# 3. Deploy
echo ""
echo "🚀 Deploying to Cloud Run..."
echo "--------------------------------------------------------"

# Nota: usiamo i segreti creati nella guida.
# Se non li hai ancora creati, questo step fallirà.
gcloud run deploy $SERVICE_NAME \
  --image $IMAGE_NAME \
  --platform managed \
  --region $REGION \
  --allow-unauthenticated \
  --port 8080 \
  --set-env-vars="APP_ENV=prod" \
  --set-secrets="APP_SECRET=nav-fi-app-secret:latest,DATABASE_URL=nav-fi-db-url:latest,GOOGLE_CLIENT_ID=nav-fi-google-id:latest,GOOGLE_CLIENT_SECRET=nav-fi-google-secret:latest" \
  --add-cloudsql-instances="$PROJECT_ID:$REGION:nav-fi-db"

echo ""
echo "✅ Deployment Complete!"
echo "   Your service should be live at the URL above."
