#!/bin/bash
set -e

# ==============================================================================
# NAV-FI SECRETS WIZARD
# ==============================================================================
# Questo script ti aiuta a caricare i "valori veri" su Google Cloud Secret Manager.
# Cloud Run userà questi valori al posto di quelli "finti" del Dockerfile.

PROJECT_ID=$(gcloud config get-value project)

echo "========================================================"
echo "🔐 CONFIGURAZIONE SEGRETI PER: $PROJECT_ID"
echo "========================================================"
echo "Questo script creerà/aggiornerà i segreti su Google Secret Manager."
echo "Premi INVIO per confermare o CTRL+C per uscire."
read -r

# ------------------------------------------------------------------------------
# 1. APP_SECRET
# ------------------------------------------------------------------------------
echo ""
echo "🔹 1. APP_SECRET (Symfony Secret)"
GEN_SECRET=$(openssl rand -base64 32)
echo "   Generato automaticamente: $GEN_SECRET"
echo -n "$GEN_SECRET" | gcloud secrets create nav-fi-app-secret --data-file=- --project="$PROJECT_ID" --replication-policy="automatic" 2>/dev/null || \
echo -n "$GEN_SECRET" | gcloud secrets versions add nav-fi-app-secret --data-file=- --project="$PROJECT_ID"
echo "   ✅ Salvato in 'nav-fi-app-secret'"

# ------------------------------------------------------------------------------
# 2. DATABASE_URL
# ------------------------------------------------------------------------------
echo ""
echo "🔹 2. DATABASE_URL (Connessione Postgres)"
echo "   Formato Cloud SQL: postgresql://USER:PASSWORD@/cloudsql/PROJECT:REGION:INSTANCE/DB_NAME"
echo "   Esempio: postgresql://navfi_user:MioSegreto@/cloudsql/$PROJECT_ID:europe-west8:nav-fi-db/nav_fi_web"
echo ""
read -p "   Incolla la tua DATABASE_URL di produzione: " REAL_DB_URL

if [ -z "$REAL_DB_URL" ]; then
    echo "   ⚠️  Saltato (nessun valore inserito)"
else
    echo -n "$REAL_DB_URL" | gcloud secrets create nav-fi-db-url --data-file=- --project="$PROJECT_ID" --replication-policy="automatic" 2>/dev/null || \
    echo -n "$REAL_DB_URL" | gcloud secrets versions add nav-fi-db-url --data-file=- --project="$PROJECT_ID"
    echo "   ✅ Salvato in 'nav-fi-db-url'"
fi

# ------------------------------------------------------------------------------
# 3. GOOGLE OAUTH
# ------------------------------------------------------------------------------
echo ""
echo "🔹 3. GOOGLE CLIENT ID"
read -p "   Incolla il Client ID: " G_CLIENT_ID
if [ ! -z "$G_CLIENT_ID" ]; then
    echo -n "$G_CLIENT_ID" | gcloud secrets create nav-fi-google-id --data-file=- --project="$PROJECT_ID" --replication-policy="automatic" 2>/dev/null || \
    echo -n "$G_CLIENT_ID" | gcloud secrets versions add nav-fi-google-id --data-file=- --project="$PROJECT_ID"
    echo "   ✅ Salvato in 'nav-fi-google-id'"
fi

echo ""
echo "🔹 4. GOOGLE CLIENT SECRET"
read -p "   Incolla il Client Secret: " G_CLIENT_SECRET
if [ ! -z "$G_CLIENT_SECRET" ]; then
    echo -n "$G_CLIENT_SECRET" | gcloud secrets create nav-fi-google-secret --data-file=- --project="$PROJECT_ID" --replication-policy="automatic" 2>/dev/null || \
    echo -n "$G_CLIENT_SECRET" | gcloud secrets versions add nav-fi-google-secret --data-file=- --project="$PROJECT_ID"
    echo "   ✅ Salvato in 'nav-fi-google-secret'"
fi

echo ""
echo "========================================================"
echo "🎉 CONFIGURAZIONE COMPLETATA"
echo "========================================================"
echo "Ora quando farai 'gcloud run deploy', Cloud Run prenderà questi valori"
echo "dal Secret Manager e li inietterà nell'applicazione."
