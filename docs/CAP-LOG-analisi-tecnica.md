# Captain Log Web – Analisi tecnica

Applicazione Symfony dedicata alla gestione di navi, equipaggi, contratti e mutui nel contesto del gioco di ruolo **Traveller**.

Questo documento descrive in modo discorsivo l’architettura attuale di Captain Log Web, le sue dipendenze, i componenti applicativi principali e alcuni punti di attenzione operativi.

## Stack e infrastruttura
- **Framework:** Symfony 7.3 (PHP ≥ 8.2), asset mapper, Stimulus, Twig, Tailwind + DaisyUI per la UI.
- **Persistenza:** Doctrine ORM con PostgreSQL/MySQL/SQLite.
- **Admin:** EasyAdmin per le entità di contesto.
- **PDF:** wkhtmltopdf via KnpSnappy (binario da `WKHTMLTOPDF_PATH`), template contratti in `templates/pdf/contracts` e scheda nave in `templates/pdf/ship/SHEET.html.twig`.
- **Parametri day/year:** limiti configurabili via env (`APP_DAY_MIN/MAX`, `APP_YEAR_MIN/MAX`) e iniettati nei form IntegerType/ImperialDateType dedicati ai campi giorno/anno.
- **Stimulus form helpers:** controller `year-limit` applica il min anno derivato dallo `startingYear` della Campaign sulla Ship selezionata, con fallback ai limiti env.

## Dominio applicativo
- **Campagne e sessioni:** `Campaign` con calendario di sessione (giorno/anno) e relazione 1–N con Ship; le date sessione mostrate nelle liste/PDF derivano dalla Campaign della nave.
- **Navi e mutui:** `Ship`, `Mortgage`, `MortgageInstallment`, `InterestRate`, `Insurance`; il mutuo conserva `signingDay/Year` derivati dalla sessione della Campaign e `signingLocation` raccolta via modale al momento della firma. Il PDF del mutuo è generato da template dedicato; i piani usano 13 periodi/anno (esempio: 5 anni ⇒ 65 rate).
- **Dettagli nave strutturati:** `Ship.shipDetails` (JSON) con DTO/form (`ShipDetailsData`, `ShipDetailItemType`, `MDriveDetailItemType`, `JDriveDetailItemType`) per hull/drive/bridge e collezioni (weapons, craft, systems, staterooms, software). Il “Total Cost” dei componenti è calcolato lato client e salvato nel JSON, ma **non** modifica `Ship.price`.
- **Annual Budget per nave:** ogni budget è legato a una singola nave e aggrega ricavi (`Income`), costi (`Cost`) e rate del mutuo pagate nel periodo (start/end giorno/anno). Dashboard e grafico mostrano la timeline per nave.
- **Equipaggio:** `Crew` con ruoli (`ShipRole`); la presenza di capitano è validata da validator dedicato.
- **CostCategory / IncomeCategory:** tabelle di contesto per tipologie di spesa/entrata (code, description), con seeds JSON.
- **Company e CompanyRole:** controparti contrattuali usate da `Cost`, `Income` e `Mortgage`.
- **LocalLaw:** codice, descrizione breve e disclaimer giurisdizionale; referenziato da Cost, Income, Mortgage.
- **Income dettagliato per categoria:** relazioni 1–1 (Freight, Passengers, Contract, Trade, Subsidy, Services, Insurance, Interest, Mail, Prize, Salvage, Charter, ecc.) con campi specifici; le sottoform sono attivate in base a `IncomeCategory.code`.
- **Tracciamento utente:** FK `user` (nullable) su Ship, Crew, Mortgage, MortgageInstallment, Cost, Income, AnnualBudget e Company. Un listener Doctrine (`AssignUserSubscriber`) assegna l’utente loggato in `prePersist`.

### Relazioni principali
- Campaign 1–N Ship (calendario di sessione condiviso).
- Ship 1–1 Mortgage (vincolo univoco: una nave ha al massimo un mutuo).
- Ship 1–N Crew, 1–N Cost, 1–N Income, 1–N AnnualBudget.
- Mortgage 1–N MortgageInstallment; ManyToOne InterestRate/Insurance; ManyToOne Company/LocalLaw.
- Crew N–M ShipRole.
- Cost ManyToOne CostCategory, Company, LocalLaw.
- Income ManyToOne IncomeCategory, Company, LocalLaw + 1–1 con tabella dettaglio categoria.
- CompanyRole 1–N Company.

## Sicurezza e autorizzazioni
- **Autenticazione:** form login (`/login`), CSRF abilitato, provider User (email). Access control su rotte principali con ruolo USER/ADMIN.
- **Voter:** Ship/Crew/Mortgage/Cost/Income/AnnualBudget/Company vincolano l’accesso all’owner (`entity->getUser() === app.user`) e bloccano anonimi. Entità legacy senza `user` vengono rifiutate.
- **Subscriber:** `AssignUserSubscriber` (Doctrine `prePersist`) assegna l’utente corrente se mancante.
- **Filtro per ownership nei controller:** accesso alle entità tramite repository `findOneForUser`/`findAllForUser`, con 404 se l’utente non coincide (difesa in profondità oltre ai voter).
- **Localizzazione numerica:** `twig/intl-extra` formatta importi in liste e PDF secondo la locale richiesta.
- **Validazione day/year:** i form usano `IntegerType` e `DayYearLimits`; il min anno deriva dallo `startingYear` della Campaign della Ship selezionata (fallback `APP_YEAR_MIN`) ed è propagato lato client via Stimulus.

## EasyAdmin
- Dashboard personalizzata (`templates/admin/dashboard.html.twig`) con card di link rapidi per le entità di contesto (InterestRate, Insurance, ShipRole, CostCategory, IncomeCategory, CompanyRole, LocalLaw, Company).
- CRUD dedicati per le tabelle di contesto e per Company.

## Comandi e seeds di contesto
- **Export:** `php bin/console app:context:export --file=config/seed/context_seed.json`
- **Import:** `php bin/console app:context:import --file=config/seed/context_seed.json`
  - Trunca e ricarica le tabelle di contesto (ship_role, insurance, interest_rate, cost_category, income_category, company_role, local_law).


## Contratti e PDF
- Template HTML Twig in `templates/contracts` per le principali categorie di Income; i placeholder sono documentati in `docs/contract-placeholders.md`.
- Servizio `PdfGenerator` basato su KnpSnappy/wkhtmltopdf per stampare i contratti Income, il mutuo e la scheda nave; percorso binario configurato in `config/packages/knp_snappy.yaml` via env.
- I campi opzionali delle sottoform Income sono determinati dal codice categoria e mostrati solo se richiesti (form dinamiche con event subscriber).

## Persistenza e migrazioni
- Migrazioni versionate in `migrations/` (inclusa quella per `cost_category`).
- Campi monetari: `Ship.price` è `DECIMAL` a DB e tipizzato `string` in PHP. La logica di calcolo mutuo usa BCMath e normalizza gli importi a stringa per evitare errori di accumulo; resta consigliato mantenere importi a tipo esatto (string + `bc*` o integer di “crediti” con fattore 100) per coerenza end-to-end.

## Note operative e punti di attenzione
- **User null:** dati preesistenti senza `user` non supereranno i voter; valutare una migrazione di popolamento o un comportamento di fallback.
- **Filtri per utente:** liste e recuperi puntuali delle entità protette passano sempre da repository che filtrano per `user` e i controller restituiscono 404 se l’entità non appartiene all’utente corrente, riducendo il rischio di ID enumeration.
- **CSRF login:** configurato via form_login con CSRF abilitato; la configurazione CSRF stateless per `authenticate` è stata rimossa.
- **Dashboard:** card a sfondo scuro coerenti con tema EasyAdmin dark; testo “Apri” in azzurro.
- **PDF/wkhtmltopdf:** assicurarsi che il binario sia disponibile e che l’opzione `enable-local-file-access` resti abilitata per caricare asset locali nei PDF.
- **Form giorno/anno:** limiti validativi configurati via env; aggiornare `.env.local` in base al calendario imperiale usato al tavolo. Il datepicker imperiale ha pulsanti mese e un tasto Clear che svuota il giorno mantenendo l’anno.
- **Sessione campagne:** sessionDay/sessionYear vive su Campaign; le Ship mostrano i valori ereditati. Migrazioni legacy potrebbero aver popolato le Ship: mantenerle allineate se si rimuovono i campi.
- **Ship details JSON:** il form salva blocchi strutturati; se si altera la struttura, valutare migrazioni o normalizzazioni per non perdere dati.

## Checklist rapida di setup
1. Variabili env: `DATABASE_URL`, `APP_SECRET`, `APP_DAY_MIN/MAX`, `APP_YEAR_MIN/MAX`.
2. Dipendenze: `composer install`, frontend già con asset mapper/Stimulus.
3. Migrazioni: `php bin/console doctrine:migrations:migrate`.
4. Import seeds di contesto: `php bin/console app:context:import` (facoltativo).
5. Accesso admin: `/admin` con utente ROLE_ADMIN.

## Suggerimenti futuri
- Gestire importi finanziari con tipo esatto anche per la valuta fittizia (string + BCMath o integer di crediti) per evitare drift, allineando anche `Ship.price`.
- Aggiungere fallback per entità legacy senza user (es. assegnare all’utente corrente o bloccare con messaggio dedicato).
- Aggiungere test funzionali per login/CSRF, filtri per ownership e per i comandi di import/export di contesto.
