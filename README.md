# Captain Log Web

Applicazione Symfony 7.3 per la gestione di navi, equipaggi, contratti e mutui, pensata per il gioco di ruolo **Traveller**. Include area amministrativa EasyAdmin, PDF per i contratti e comandi di import/export dei dati di contesto. Ogni Annual Budget è agganciato a una singola nave e ne aggrega entrate, costi e rate del mutuo.

## Caratteristiche principali

### Tactical Bridge Interface (v2.0.x)
- **Design System "Nav-Ops"**: Interfaccia ad alto contrasto basata su Tailwind 4 e DaisyUI, con palette cromatica dedicata (Abyss/Cyan/Teal) e animazioni responsive "scan-line".
- **Tactical Search Terminals**: Filtri di ricerca centralizzati per i registri finanziari con labeling tecnico (`VESSEL_NAV`, `CAT_ID`, `DEBT_NAME`) e background semi-trasparente per una migliore leggibilità.
- **Nav-Ops Pagination**: Sistema di navigazione tra i record con terminologia operativa (`LOG_SECTOR`, `TOTAL_RECORDS`) e pulse animato.
- **Horizontal Data Architecture**: Ottimizzazione del layout a piena larghezza per i moduli complessi (Annual Budget, Mortgage), massimizzando lo spazio per telemetry e grafici.

### Security & Access Control
- **Perimeter Defense (MFA)**: Supporto nativo per Two-Factor Authentication (TOTP) con QR Code e gestione dispositivi autorizzati.
- **External Uplink (Google OAuth)**: Integrazione sicura tramite Google Cloud Console per login rapido tramite account ufficiali.
- **Ownership Lockdown**: Sistema granulare di Voter e Repository pattern che isola i dati (Ship, Crew, Financials) sull'utente proprietario, restituendo 404 in caso di tentata violazione di perimetro (ID enumeration).

### Financial Core & Asset Management
- **Vessel Liability (Mortgage)**: Gestione mutui uno-a-uno con piani di ammortamento a 13 periodi (calendario Traveller), tassi variabili e assicurazioni. Firma contrattuale legata alla sessione della Campaign.
- **Full-Spectrum Ledger**: Tracciamento di entrate (`Income`) e uscite (`Cost`) con dettagli per categoria (Freight, Mail, Trade) e status dinamico **Draft/Signed**.
- **Annual Projections**: Aggregazione automatica di cashflow per nave, con visualizzazione grafica della timeline finanziaria.
- **Strategic Amendments**: Registro delle modifiche strutturali alla nave post-firma, collegate obbligatoriamente a un Cost reference per tracciabilità economica.

### Operations & Navigation
- **Campaign Sync**: Calendario di sessione centralizzato (`DDD/YYYY`) che propaga le date a tutti i moduli collegati e genera una sessione timeline con log JSON delle modifiche.
- **Navigational Routes**: Calcolo di rotte tramite waypoints (Hex/Sector T5SS) con stima carburante (RouteMathHelper) e integrazione cartografica TravellerMap.
- **Crew Registry**: Gestione status equipaggio (Active, MIA, Deceased) con assegnazione automatica alla sessione di bordo e validazione ruoli.

## Requisiti
- PHP 8.2+
- Composer
- wkhtmltopdf disponibile e referenziato via variabile `WKHTMLTOPDF_PATH` (vedi `config/packages/knp_snappy.yaml`)
- Database supportato da Doctrine (PostgreSQL/MySQL/SQLite)

## KnpSnappy e wkhtmltopdf (patched Qt)
- Configurazione binario: `config/packages/knp_snappy.yaml` legge `%env(WKHTMLTOPDF_PATH)%` (default suggerito `/usr/local/bin/wkhtmltopdf`).
- Installazione wkhtmltopdf patchato (Ubuntu 22.04/Jammy):
  ```bash
  sudo apt-get update
  sudo apt-get install -y wget xfonts-75dpi

  cd ~
  wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-2/wkhtmltox_0.12.6.1-2.jammy_amd64.deb

  sudo dpkg -i wkhtmltox_0.12.6.1-2.jammy_amd64.deb
  ```
- Verifica:
  ```bash
  wkhtmltopdf --version
  ```
  Deve mostrare `wkhtmltopdf 0.12.6.1 (with patched qt)`.

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
5. Installa Highlight.js e copia lo stile `github-dark` nella cartella degli asset per renderlo importabile da Tailwind:
   ```bash
   npm install highlight.js
   mkdir -p assets/vendor/highlightjs
   cp node_modules/highlight.js/styles/github-dark.css assets/vendor/highlightjs/
   ```
5. Crea `.env.local` con le variabili minime:
   ```env
   APP_ENV=dev
   APP_SECRET=changeme
   DATABASE_URL="postgresql://user:pass@127.0.0.1:5432/captain_log_web?serverVersion=16&charset=utf8"
   APP_DAY_MIN=1
   APP_DAY_MAX=365
   APP_YEAR_MIN=0
   APP_YEAR_MAX=6000
   # wkhtmltopdf
   WKHTMLTOPDF_PATH="/usr/local/bin/wkhtmltopdf"
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
  php bin/console app:db:dump --data-only --file=var/backup/captain_log.dump
  ```
- Import del database (Postgres):
  ```bash
  php bin/console app:db:import --file=var/backup/captain_log.dump
  # ripulisce e reimporta
  php bin/console app:db:import --clean --file=var/backup/captain_log.dump
  ```
  Nota: il dump/import gestisce solo i dati. Esegui le migration **prima** dell’import per ricreare PK/FK/unique/index, oppure usa un dump schema+data.

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

## Note
- Il calendario di sessione (giorno/anno) è centralizzato sulla Campaign; le Ship ne ereditano la visualizzazione nelle liste e nei PDF.
- La form dettagli nave salva un JSON (`shipDetails`) e alimenta la stampa PDF della scheda nave.
- Le liste (Ship, Crew, Mortgage, MortgageInstallment, Cost, Income, AnnualBudget) sono filtrate sull’utente proprietario; il salvataggio assegna automaticamente l’utente loggato.
- Le entità di contesto (InterestRate, Insurance, ShipRole, CostCategory, IncomeCategory, CompanyRole, LocalLaw) sono gestite via EasyAdmin o via comandi di import/export.
- I contratti Income sono tipizzati per categoria con dettagli dedicati e possono essere stampati in PDF tramite i template in `templates/pdf/contracts`.
- I campi anno usano `IntegerType` con min derivato dalla Campaign associata alla Ship selezionata; il controller Stimulus `year-limit` aggiorna dinamicamente il limite min per i dropdown nave.
- Per un’analisi tecnica di alto livello (tech stack, entità, flussi e prossimi passi) consulta `docs/CAP-LOG-analisi-tecnica.md`.
