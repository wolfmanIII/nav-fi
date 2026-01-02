# Captain Log Web

Applicazione Symfony 7.3 per la gestione di navi, equipaggi, contratti e mutui, pensata per il gioco di ruolo **Traveller**. Include area amministrativa EasyAdmin, PDF per i contratti e comandi di import/export dei dati di contesto. Ogni Annual Budget è agganciato a una singola nave e ne aggrega entrate, costi e rate del mutuo.

## Caratteristiche principali
- Navi, equipaggi, ruoli di bordo e mutui (rate, tassi, assicurazioni) con vincolo uno-a-uno nave↔mutuo.
- Tipologie di spesa equipaggio (`CostCategory`) e anagrafiche di contesto (InterestRate, Insurance, ShipRole, CompanyRole, LocalLaw, IncomeCategory).
- Company e CompanyRole come controparti contrattuali; LocalLaw per giurisdizione e disclaimer.
- Entrate e costi legati alla nave con dettagli per categoria (es. Freight, Contract): form dinamiche e PDF contrattuali generati con wkhtmltopdf.
- Tracciamento dell’utente proprietario su Ship, Crew, Mortgage, MortgageInstallment, Cost, Income e budget; i voter bloccano l’accesso se l’utente non coincide.
- Annual Budget per nave: calcolo riepilogativo di ricavi, costi e rate annuali del mutuo, più grafico temporale Income/Cost.
- Dashboard EasyAdmin personalizzata e CRUD dedicati alle entità di contesto.
- Comandi di export/import JSON per ripristinare rapidamente i dati di contesto.
- Console AI per inoltrare domande a un backend esterno (Elara) tramite HttpClient.
- I controller e i repository filtrano le entità sull’utente proprietario restituendo 404 se non corrispondono, per difesa in profondità oltre ai voter.
- I calcoli del mutuo usano BCMath e importi normalizzati a stringa per evitare drift tipici dei float.

## Requisiti
- PHP 8.2+
- Composer
- wkhtmltopdf disponibile a `/usr/local/bin/wkhtmltopdf` (vedi `config/packages/knp_snappy.yaml`)
- Database supportato da Doctrine (PostgreSQL/MySQL/SQLite)

## Configurazione
1. Installa le dipendenze:
   ```bash
   composer install
   ```
2. Crea `.env.local` con le variabili minime:
   ```env
   APP_ENV=dev
   APP_SECRET=changeme
   DATABASE_URL="mysql://user:pass@127.0.0.1:3306/captain_log_web?serverVersion=8.0"
   APP_DAY_MIN=1
   APP_DAY_MAX=365
   APP_YEAR_MIN=0
   APP_YEAR_MAX=6000

   # Backend AI esterno (Elara)
   ELARA_BASE_URL="https://127.0.0.1:8080"
   ELARA_API_TOKEN="inserisci-il-token"

   # wkhtmltopdf
   WKHTMLTOPDF_PATH="/usr/local/bin/wkhtmltopdf"
   ```
3. Esegui le migrazioni:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```
4. Crea un utente (se sono presenti i comandi appositi, ad es. `app:user:create`) e assegna il ruolo necessario (`ROLE_ADMIN` per l’area /admin).

## Comandi utili
- Esporta dati di contesto (InterestRate, Insurance, ShipRole, CostCategory, IncomeCategory, CompanyRole, LocalLaw):
  ```bash
  php bin/console app:context:export --file=config/seed/context_seed.json
  ```
- Importa dati di contesto (trunca e ricarica le tabelle di contesto):
  ```bash
  php bin/console app:context:import --file=config/seed/context_seed.json
  ```

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
- Console AI: `https://127.0.0.1:8000/ai/console`

## Note
- Le liste (Ship, Crew, Mortgage, MortgageInstallment, Cost, Income, AnnualBudget) sono filtrate sull’utente proprietario; il salvataggio assegna automaticamente l’utente loggato.
- Le entità di contesto (InterestRate, Insurance, ShipRole, CostCategory, IncomeCategory, CompanyRole, LocalLaw) sono gestite via EasyAdmin o via comandi di import/export.
- I contratti Income sono tipizzati per categoria con dettagli dedicati e possono essere stampati in PDF tramite i template in `templates/contracts`.
