# Nav-Fi Web
(formerly ELaRA / Captain Log)

Applicazione **Symfony 7.4** per la gestione di navi, equipaggi, contratti e mutui, pensata per il gioco di ruolo **Traveller**. Include area amministrativa EasyAdmin, PDF per i contratti e comandi di import/export dei dati di contesto. Ogni Annual Budget è agganciato a una singola nave e ne aggrega entrate, costi e rate del mutuo.

## Caratteristiche principali

### Command Deck (Homepage)
- **Bento Grid Dashboard**: Interfaccia "Command Deck" ad alta densità con stato della flotta e feed tattico.
- **Design System "Nav-Fi"**: Evoluzione dell'interfaccia "Nav-Ops" basata su Tailwind e DaisyUI, con palette cromatica dedicata (Abyss/Cyan/Teal/Emerald/Amber) differenziata per modulo.
- **Tactical Sidebar**: Accesso rapido a moduli operativi (Checklist, Mission Flow) e link importanti.
- **Responsive Animations**: Micro-interazioni e gradienti dinamici per un look "sci-fi interface".

### Security & Access Control
- **Perimeter Defense (MFA)**: Supporto nativo per Two-Factor Authentication (TOTP).
- **External Uplink (Google OAuth)**: Login rapido tramite account Google.
- **Ownership Lockdown**: Sistema granulare che isola i dati (Ship, Crew, Financials) sull’utente proprietario.

### Financial Core & Asset Management (3.0)
- **Time-Aware Ledger Service**: Advanced synchronization between mission dates and fund availability (`PENDING` vs `POSTED`).
- **Temporal Immutability**: Strict audit log with `REVERSAL` protocols for historical corrections.
- **Form Intelligence Layer**: Progressive disclosure and Smart Visibility driven by Asset context.
- **Hybrid Entity Resolution**: Seamless on-the-fly creation of Vendors and Financial Accounts with XOR strictness.
- **Total Asset Liability**: Integrated mortgage management with amortization schedules and insurance tracking.
- **Annual Projections**: Real-time aggregation of cashflow and financial health charts.

### Operations & Navigation
- **Mission Control**: Calendario di sessione centralizzato (`DDD/YYYY`) che propaga le date a tutti i moduli.
- **Navigational Routes**:
    - Calcolo di rotte tramite waypoints (Hex/Sector T5SS).
    - **Fuel Math**: Stima carburante basata su tonnellaggio scafo e rating (`Hull * 0.1 * Rating * Jumps`).
    - **Jump Logic**: Calcolo automatico distanze e validazione limiti drive.
    - **Interactive Map**: Integrazione dinamica con TravellerMap (iframe senza reload).
- **Crew Registry**: Gestione status equipaggio (Active, MIA, Deceased).

## Requisiti
- PHP 8.2+
- Composer
- Database supportato da Doctrine (PostgreSQL/MySQL/SQLite)

## PDF Generation (Gotenberg)
La generazione PDF utilizza **Gotenberg**, un container Docker che espone un'API HTTP per la conversione HTML→PDF tramite Chromium.

### Avvio del container
```bash
docker compose up -d gotenberg
```

### Verifica funzionamento
```bash
# Health check
curl http://localhost:3000/health

# Test generazione PDF
php bin/console app:test:pdf
```

### Configurazione
Il servizio `GotenbergPdfGenerator` usa la variabile d'ambiente `GOTENBERG_ENDPOINT` (default: `http://localhost:3000`).

Per produzione (Cloud Run), deployare Gotenberg come servizio separato e configurare:
```env
GOTENBERG_ENDPOINT=https://gotenberg-service-url.run.app
```

### Immagini nei PDF
I template PDF usano `{{ asset_data_uri('img/...') }}` per incorporare le immagini come base64, evitando problemi di accesso a URL locali da parte di Gotenberg.

## Configurazione
1. Installa le dipendenze:
   ```bash
   composer install
   ```
2. Interfaccia grafica, Tailwind, Typography e DaisyUI
   #### Installare nvm (nodejs version manager)
   ```bash
   curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.1/install.sh | bash
   ```
   #### Aggiornare il proprio profilo utente, file .bash_profile o .bashrc nella propria home directory
   ```bash
   export NVM_DIR="$([ -z "${XDG_CONFIG_HOME-}" ] && printf %s "${HOME}/.nvm" || printf %s "${XDG_CONFIG_HOME}/nvm")"
   [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
   ```
   #### Ricaricare la configurazione della shell
   ```bash
   source ~/.bashrc
   ```
   #### Installare nodejs e i plugin aggiuntivi per Tailwind
   ```bash
   nvm install --lts
   npm init
   npm install -D @tailwindcss/typography
   npm i -D daisyui@latest
   ```
3. Installa Tom Select (usato per la select con ricerca):
   ```bash
   npm install tom-select
   ```
4. Copia gli asset di Tom Select nella cartella `assets/vendor/tom-select` così vengono caricati localmente (JS e CSS sono usati da Stimulus e Tailwind senza importmap bare-module):
   ```bash
   mkdir -p assets/vendor/tom-select
   cp node_modules/tom-select/dist/js/tom-select.complete.min.js assets/vendor/tom-select/
   cp node_modules/tom-select/dist/css/tom-select.css assets/vendor/tom-select/
   ```
5. Installa Highlight.js e copia lo stile `github-dark`, scaricando anche la libreria pre-compilata:
   ```bash
   npm install highlight.js
   npm run copy-libs
   ```
5. Crea `.env.local` con le variabili minime:
   ```env
   APP_ENV=dev
   APP_SECRET=changeme
   DATABASE_URL="postgresql://user:pass@127.0.0.1:5432/nav_fi_web?serverVersion=16&charset=utf8"
   APP_DAY_MIN=1
   APP_DAY_MAX=365
   APP_YEAR_MIN=0
   APP_YEAR_MAX=6000
   # Gotenberg (opzionale, default: http://localhost:3000)
   GOTENBERG_ENDPOINT=http://localhost:3000
   ```
5. Esegui le migrazioni:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```
6. Crea un utente (se sono presenti i comandi appositi, ad es. `app:user:create`) e assegna il ruolo necessario (`ROLE_ADMIN` per l’area /admin).

## Comandi utili
- Esporta dati di contesto (InterestRate, Insurance, ShipRole, CostCategory, IncomeCategory, CompanyRole, LocalLaw):
  ```bash
  php bin/console app:context:export --file=config/seed/context_seed.json
  ```
- Importa dati di contesto (trunca e ricarica le tabelle di contesto):
  ```bash
  php bin/console app:context:import --file=config/seed/context_seed.json
  ```
- Dump del database (Postgres):
  ```bash
  php bin/console app:db:dump
  # solo dati
  php bin/console app:db:dump --data-only --file=var/backup/nav_fi.dump
  ```
- Import del database (Postgres):
  ```bash
  php bin/console app:db:import --file=var/backup/nav_fi.dump
  # ripulisce e reimporta
  php bin/console app:db:import --clean --file=var/backup/nav_fi.dump
  ```
  Nota: il dump/import gestisce solo i dati. Esegui le migration **prima** dell’import per ricreare PK/FK/unique/index, oppure usa un dump schema+data.

- **Esecuzione Test (Automated Verification Suite)**:
  ```bash
  bin/phpunit tests/Functional/ComprehensiveWorkflowTest.php
  ```
  La suite verifica l'intero ciclo di vita di missioni e trading.

## Avvio
- Server di sviluppo Symfony:
  ```bash
  symfony server:start
  ```
  oppure
  ```bash
  php -S 127.0.0.1:8000 -t public
  ```
- Area admin: `https://127.0.0.1:8000/admin`
- Login: `https://127.0.0.1:8000/login`

## Docker

```bash
export $(cat .env.local | grep -v ^# | xargs)
docker build \
  --build-arg APP_SECRET=$APP_SECRET \
  --build-arg DATABASE_URL=$DATABASE_URL \
  --build-arg GOOGLE_CLIENT_ID=$GOOGLE_CLIENT_ID \
  --build-arg GOOGLE_CLIENT_SECRET=$GOOGLE_CLIENT_SECRET \
  -t nav-fi .

docker run \
  --add-host=host.docker.internal:host-gateway \
  -e DATABASE_URL=$DATABASE_URL_DOCKER \
  -e APP_SECRET=$APP_SECRET \
  -e GOOGLE_CLIENT_ID=$GOOGLE_CLIENT_ID \
  -e GOOGLE_CLIENT_SECRET=$GOOGLE_CLIENT_SECRET \
  -p 8080:8080 \
  nav-fi
```

## Note
- Il calendario di sessione (giorno/anno) è centralizzato sulla Campaign; le Ship ne ereditano la visualizzazione nelle liste e nei PDF.
- La form dettagli nave salva un JSON (`shipDetails`) e alimenta la stampa PDF della scheda nave.
- Le liste (Ship, Crew, Mortgage, MortgageInstallment, Cost, Income, AnnualBudget) sono filtrate sull’utente proprietario; il salvataggio assegna automaticamente l’utente loggato.
- Le entità di contesto (InterestRate, Insurance, ShipRole, CostCategory, IncomeCategory, CompanyRole, LocalLaw) sono gestite via EasyAdmin o via comandi di import/export.
- I contratti Income sono tipizzati per categoria con dettagli dedicati e possono essere stampati in PDF tramite i template in `templates/pdf/contracts`.
- I campi anno usano `IntegerType` con min derivato dalla Campaign associata alla Ship selezionata; il controller Stimulus `year-limit` aggiorna dinamicamente il limite min per i dropdown nave.
- Per un’analisi tecnica di alto livello (tech stack, entità, flussi e prossimi passi) consulta `docs/NAV-FI-analisi-tecnica.md`.
