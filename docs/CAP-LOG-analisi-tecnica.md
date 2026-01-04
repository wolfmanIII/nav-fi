# Captain Log Web – Analisi tecnica

Applicazione Symfony dedicata alla gestione di navi, equipaggi, contratti e mutui nel contesto del gioco di ruolo **Traveller**.

Questo documento descrive in modo discorsivo l’architettura attuale di Captain Log Web, le sue dipendenze, i componenti applicativi principali e alcuni punti di attenzione operativi.

## Stack e infrastruttura
- **Framework:** Symfony 7.3 (PHP ≥ 8.2), asset mapper, Stimulus, Twig, Tailwind + DaisyUI per la UI.
- **Persistenza:** Doctrine ORM con PostgreSQL/MySQL/SQLite.
- **Admin:** EasyAdmin per le entità di contesto.
- **AI:** integrazione esterna “Elara” via HttpClient (chat e status).
- **PDF:** wkhtmltopdf via KnpSnappy (binario da `WKHTMLTOPDF_PATH`), template contratti in `templates/contracts` e scheda nave in `templates/pdf/ship/SHEET.html.twig`.
- **Parametri day/year:** limiti configurabili via env (`APP_DAY_MIN/MAX`, `APP_YEAR_MIN/MAX`) e iniettati nei form NumberType dedicati ai campi giorno/anno.

## Dominio applicativo
- **Campagne e sessioni:** `Campaign` con calendario di sessione (giorno/anno) e relazione 1–N con Ship; le date sessione mostrate nelle liste/PDF derivano dalla Campaign di appartenenza.
- **Navi e mutui:** `Ship`, `Mortgage`, `MortgageInstallment`, `InterestRate`, `Insurance`, `ShipRole`; la scheda nave esporta un PDF dedicato.
- **Dettagli nave strutturati:** campo JSON `shipDetails` su Ship con DTO/form (`ShipDetailsData`, `ShipDetailItemType`) per drive/hull/bridge e collezioni (weapons, craft, systems, staterooms, software); usato anche nel PDF nave.
- **Annual Budget per nave:** ogni budget è legato a una singola nave e aggrega ricavi (`Income`), costi (`Cost`) e rate del mutuo pagate nel periodo impostato (start/end giorno/anno). Dashboard e grafico mostrano l’andamento temporale Income/Cost.
- **Equipaggio:** `Crew` con ruoli multipli (`ShipRole`, es. CAP). Metodi helper `hasCaptain()`, `hasMortgageSigned()`.
- **CostCategory / IncomeCategory:** tabelle di contesto per tipologie di spesa/entrata (code, description) con seeds JSON.
- **Company e CompanyRole:** controparti contrattuali con `signLabel`; usate da Cost/Income/Mortgage.
- **LocalLaw:** codice, descrizione breve e disclaimer giurisdizionale; referenziato da Cost, Income, Mortgage.
- **Income dettagliato per categoria:** relazioni 1–1 (Freight, Passengers, Contract, Trade, Subsidy, Services, Insurance, Interest, Mail, Prize, Salvage, Charter, ecc.) con campi specifici; i form aggiungono le sottoform in base a `IncomeCategory.code`.
- **Tracciamento utente:** `user` (FK nullable) su Ship, Crew, Mortgage, MortgageInstallment, Cost, Income, AnnualBudget. Un listener Doctrine (`AssignUserSubscriber`) assegna l’utente loggato in `prePersist`.

### Relazioni principali
- Ship 1–1 Mortgage (vincolo univoco: una nave ha al massimo un mutuo).
- Ship 1–N Crew, 1–N Cost, 1–N Income.
- Mortgage 1–N MortgageInstallment; ManyToOne InterestRate/Insurance.
- Crew N–M ShipRole.
- AnnualBudget ManyToOne Ship (uno per finestra temporale/nave).
- CompanyRole 1–N Company; Company/LocalLaw ManyToOne su Cost/Income/Mortgage.
- Income 1–1 con ciascuna tabella di dettaglio categoria.

## Sicurezza e autorizzazioni
- **Autenticazione:** form login (`/login`), CSRF abilitato, provider User (email). Access control: dashboard e rotte protette richiedono ruolo USER/ADMIN.
- **Voter:** ShipVoter, CrewVoter, MortgageVoter, CostVoter, IncomeVoter, AnnualBudgetVoter vincolano l’accesso all’utente proprietario (`entity->getUser() === app.user`) e bloccano anonimi. Attenzione: entità legacy con `user` nullo verranno rifiutate. ShipVoter include permesso dedicato per il calendario sessione.
- **Subscriber:** `AssignUserSubscriber` (annotazione `AsDoctrineListener` su `prePersist`) assegna l’utente corrente se mancante. Se la sessione è anonima, non interviene.
- **Filtro per ownership nei controller:** i controller protetti recuperano le entità tramite repository filtrando per `user` e restituiscono 404 se l’entità non appartiene all’utente loggato, come difesa in profondità rispetto ai voter.
- **Localizzazione numerica:** `twig/intl-extra` formatta importi in liste e PDF secondo la locale richiesta, inclusi i PDF nave e mutuo.

## EasyAdmin
- Dashboard personalizzata (`templates/admin/dashboard.html.twig`) con card di link rapidi per le entità di contesto (InterestRate, Insurance, ShipRole, CostCategory, IncomeCategory, CompanyRole, LocalLaw, Company).
- CRUD dedicati per le tabelle di contesto e per Company.

## Comandi e seeds di contesto
- **Export:** `php bin/console app:context:export --file=config/seed/context_seed.json`
- **Import:** `php bin/console app:context:import --file=config/seed/context_seed.json`
  - Trunca e ricarica le tabelle di contesto (ship_role, insurance, interest_rate, cost_category, income_category, company_role, local_law).

## Integrazione AI/Elara
- Controller `ChatController`: chiama Elara via HttpClient (`/status/engine`, `/api/chat`, `/api/chat/stream`) con token/URL in env (`ELARA_API_TOKEN`, `ELARA_BASE_URL`). `max_redirects` impostato a 5 per gestire 302.
- Console AI (`/ai/console`) con stream SSE lato frontend (Stimulus).

## Contratti e PDF
- Template HTML Twig in `templates/contracts` per le principali categorie di Income; i placeholder sono documentati in `docs/contract-placeholders.md`.
- Servizio `PdfGenerator` basato su KnpSnappy/wkhtmltopdf per stampare i contratti Income, il mutuo e la scheda nave; percorso binario configurato in `config/packages/knp_snappy.yaml` via env.
- I campi opzionali delle sottoform Income sono determinati dal codice categoria e mostrati solo se richiesti (form dinamiche con event subscriber).

## Persistenza e migrazioni
- Migrazioni versionate in `migrations/` (inclusa quella per `cost_category`).
- Campi monetari: `Ship.price` è `DECIMAL` a DB ma tipizzato `float` in PHP (potenziale drift). La logica di calcolo mutuo ora usa BCMath e normalizza gli importi a stringa per evitare errori di accumulo; resta consigliato migrare gli importi a tipo esatto (string + `bc*` o integer di “crediti” con fattore 100) per coerenza end-to-end.

## Note operative e punti di attenzione
- **User null:** dati preesistenti senza `user` non supereranno i voter; valutare una migrazione di popolamento o un comportamento di fallback.
- **Filtri per utente:** liste e recuperi puntuali delle entità protette passano sempre da repository che filtrano per `user` e i controller restituiscono 404 se l’entità non appartiene all’utente corrente, riducendo il rischio di ID enumeration.
- **CSRF login:** configurato via form_login con CSRF abilitato; la configurazione CSRF stateless per `authenticate` è stata rimossa.
- **AI token/URL:** ora in `.env.local`; evitare hardcode. Verificare che la base URL punti al servizio corretto.
- **Dashboard:** card a sfondo scuro coerenti con tema EasyAdmin dark; testo “Apri” in azzurro.
- **PDF/wkhtmltopdf:** assicurarsi che il binario sia disponibile e che l’opzione `enable-local-file-access` resti abilitata per caricare asset locali nei PDF.
- **Form giorno/anno:** limiti validativi configurati via env; aggiornare `.env.local` in base al calendario imperiale usato al tavolo.
- **Sessione campagne:** sessionDay/sessionYear vive su Campaign; le Ship mostrano i valori ereditati. Migrazioni legacy potrebbero aver popolato le Ship: mantenerle allineate se si rimuovono i campi.
- **Ship details JSON:** il form salva blocchi strutturati; se si altera la struttura, valutare migrazioni o normalizzazioni per non perdere dati.

## Checklist rapida di setup
1. Variabili env: `DATABASE_URL`, `ELARA_BASE_URL`, `ELARA_API_TOKEN`, `APP_SECRET`, `APP_DAY_MIN/MAX`, `APP_YEAR_MIN/MAX`.
2. Dipendenze: `composer install`, frontend già con asset mapper/Stimulus.
3. Migrazioni: `php bin/console doctrine:migrations:migrate`.
4. Import seeds di contesto: `php bin/console app:context:import` (facoltativo).
5. Accesso admin: `/admin` con utente ROLE_ADMIN.

## Suggerimenti futuri
- Gestire importi finanziari con tipo esatto anche per la valuta fittizia (string + BCMath o integer di crediti) per evitare drift, allineando anche `Ship.price`.
- Aggiungere fallback per entità legacy senza user (es. assegnare all’utente corrente o bloccare con messaggio dedicato).
- Aggiungere test funzionali per login/CSRF, filtri per ownership e per i comandi di import/export di contesto.
