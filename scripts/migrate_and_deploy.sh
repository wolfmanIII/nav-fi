#!/bin/bash
set -e

# ==============================================================================
# NAV-FI: MIGRAZIONE E DEPLOY
# ==============================================================================
# Questo script gestisce il ciclo completo professionale:
# 1. Build Immagine
# 2. Aggiornamento Job di Migrazione
# 3. Esecuzione Migrazione (Job)
# 4. Deploy Applicazione

PROJECT_ID=$(gcloud config get-value project)
REGION="europe-west8"
SERVICE_NAME="nav-fi"
JOB_NAME="nav-fi-migrate"
IMAGE_NAME="gcr.io/$PROJECT_ID/$SERVICE_NAME:latest"

echo "========================================================"
echo "🚀 NAV-FI: MIGRATE & DEPLOY SYSTEM"
echo "========================================================"

# 1. Build e Push
echo ""
echo "📦 1. Building Docker Image..."
gcloud builds submit --tag $IMAGE_NAME

# 2. Aggiorna/Crea Job di Migrazione
echo ""
echo "🛠️  2. Updating Migration Job..."
# Usiamo --force per aggiornare il job esistente o crearlo se manca
# Nota: La sintassi 'jobs update' fallisce se il job non esiste, quindi proviamo prima update, se fallisce facciamo create.
# Ma 'jobs deploy' (beta) non esiste ancora stabilmente con la stessa sintassi dei servizi.
# Approccio robusto: delete e create, oppure update. Proviamo l'approccio idempotente 'update' e se fallisce 'create'.

if gcloud run jobs describe $JOB_NAME --region $REGION > /dev/null 2>&1; then
    COMMAND="update"
else
    COMMAND="create"
fi

gcloud run jobs $COMMAND $JOB_NAME \
  --image $IMAGE_NAME \
  --region $REGION \
  --command "php" \
  --args "bin/console","doctrine:migrations:migrate","--no-interaction","--allow-no-migration" \
  --set-env-vars="APP_ENV=prod" \
  --set-secrets="DATABASE_URL=nav-fi-db-url:latest" \
  --add-cloudsql-instances="$PROJECT_ID:$REGION:nav-fi-db" \
  --max-retries 0 \
  --task-timeout 10m

# 3. Esegui Migrazione
echo ""
echo "🗄️  3. Executing Database Migrations..."
gcloud run jobs execute $JOB_NAME --region $REGION --wait

echo "✅ Database Migrations Complete."

# 4. Deploy Applicazione
echo ""
echo "🚀 4. Deploying Application..."
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
echo "✅ DEPLOYMENT COMPLETE & SECURE"
