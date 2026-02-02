# Analisi dello script `scripts/setup_secrets.sh`

Questo documento spiega passo passo il funzionamento dello script `scripts/setup_secrets.sh`.
In sintesi, questo script è un **wizard interattivo** progettato per creare o aggiornare i "segreti" (password, chiavi API) su **Google Cloud Secret Manager**, in modo che l'applicazione su Cloud Run possa leggerli in sicurezza senza averli scritti in chiaro nel codice o nel Dockerfile.

## 1. Inizializzazione
```bash
set -e
PROJECT_ID=$(gcloud config get-value project)
```
*   `set -e`: Istruisce lo script a fermarsi immediatamente se un comando fallisce (utile per evitare di procedere se qualcosa va storto).
*   **Recupero Project ID**: Legge automaticamente il progetto Google Cloud attualmente attivo nella CLI `gcloud`.
*   **Conferma utente**: Mostra un messaggio di benvenuto e chiede conferma (premendo INVIO) per procedere, evitando esecuzioni accidentali.

## 2. Generazione APP_SECRET
```bash
GEN_SECRET=$(openssl rand -base64 32)
```
*   **Generazione automatica**: Usa `openssl` per creare una stringa casuale sicura a 32 caratteri. Questa serve a Symfony per criptare sessioni e token CSRF.
*   **Creazione/Aggiornamento Secret**:
    *   Il comando `gcloud secrets create` prova a creare un nuovo segreto chiamato `nav-fi-app-secret`.
    *   Se il segreto esiste già, lo script usa `||` per eseguire `gcloud secrets versions add`, aggiungendo semplicemente una **nuova versione** al segreto esistente.
    *   Tutto questo avviene automaticamente senza input utente.

## 3. Configurazione DATABASE_URL
*   **Input Manuale**: Lo script chiede all'utente di incollare la stringa di connessione al database di produzione.
*   **Formato**: Il formato atteso per Cloud SQL è:
    `postgresql://USER:PASSWORD@/cloudsql/PROJECT:REGION:INSTANCE/DB_NAME`
*   **Logica Condizionale**:
    *   Se l'utente non inserisce nulla, il passaggio viene saltato.
    *   Se viene inserito un valore, viene salvato nel Secret Manager come `nav-fi-db-url` (creazione o aggiornamento versione).

## 4. Configurazione OAuth Google
Lo script chiede due valori specifici per il login con Google:
1.  **Client ID**: Salvato nel segreto `nav-fi-google-id`.
2.  **Client Secret**: Salvato nel segreto `nav-fi-google-secret`.

Come per il database, se i campi vengono lasciati vuoti, i passaggi vengono saltati.

## 5. Conclusione
Lo script stampa un messaggio di successo e ricorda che i valori salvati non saranno attivi sull'app finché non verrà eseguito un nuovo deploy su Cloud Run. Cloud Run è configurato per iniettare questi valori dal Secret Manager come variabili d'ambiente all'avvio del container.
