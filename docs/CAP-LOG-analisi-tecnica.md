# Captain Log Web – Analisi tecnica

Applicazione Symfony dedicata alla gestione di navi, equipaggi e mutui nel contesto del gioco di ruolo **Traveller**.

Questo documento descrive in modo discorsivo l’architettura attuale di Captain Log Web, le sue dipendenze, i componenti applicativi principali e alcuni punti di attenzione operativi.

## Stack e infrastruttura
- **Framework:** Symfony 7.3 (PHP ≥ 8.2), asset mapper, Stimulus, Twig, Tailwind + DaisyUI per la UI.
- **Persistenza:** Doctrine ORM con PostgreSQL/MySQL/SQLite.
- **Admin:** EasyAdmin per le entità di contesto.
- **AI:** integrazione esterna “Elara” via HttpClient (chat e status).

## Dominio applicativo
- **Navi e mutui:** `Ship`, `Mortgage`, `MortgageInstallment`, `InterestRate`, `Insurance`, `ShipRole`.
- **Equipaggio:** `Crew` con ruoli multipli (`ShipRole`, es. CAP). Metodi helper `hasCaptain()`, `hasMortgageSigned()`.
- **CostCategory:** tabella di contesto per tipologie di spesa equipaggio (code, description).
- **Tracciamento utente:** `user` (FK nullable) su Ship, Crew, Mortgage, MortgageInstallment. Un listener Doctrine (`AssignUserSubscriber`) assegna l’utente loggato in `prePersist`.

### Relazioni principali
- Ship 1–N Mortgage, 1–N Crew.
- Mortgage 1–N MortgageInstallment; ManyToOne InterestRate/Insurance.
- Crew N–M ShipRole.
- CostCategory attualmente standalone (solo tabella di riferimento).

## Sicurezza e autorizzazioni
- **Autenticazione:** form login (`/login`), CSRF abilitato, provider User (email). Access control: dashboard e rotte protette richiedono ruolo USER/ADMIN.
- **Voter:** ShipVoter, CrewVoter, MortgageVoter vincolano l’accesso all’utente proprietario (`entity->getUser() === app.user`) e bloccano anonimi. Attenzione: entità legacy con `user` nullo verranno rifiutate.
- **Subscriber:** `AssignUserSubscriber` (annotazione `AsDoctrineListener` su `prePersist`) assegna l’utente corrente se mancante. Se la sessione è anonima, non interviene.
- **Filtro per ownership nei controller:** i controller Ship/Crew/Mortgage recuperano le entità tramite repository filtrando per `user` e restituiscono 404 se l’entity non appartiene all’utente loggato, come difesa in profondità rispetto ai voter.

## EasyAdmin
- Dashboard personalizzata (`templates/admin/dashboard.html.twig`) con card di link rapidi per:
  - Interest Rate, Insurance, Ship Role, Cost Category.
- CRUD dedicato per CostCategory (`CostCategoryCrudController`), e già presenti InterestRate/Insurance/ShipRole.

## Comandi e seeds di contesto
- **Export:** `php bin/console app:context:export --file=config/seed/context_seed.json`
- **Import:** `php bin/console app:context:import --file=config/seed/context_seed.json`
  - Trunca le tabelle di contesto (ship_role, insurance, interest_rate, cost_category) e reimporta da JSON.

## Integrazione AI/Elara
- Controller `ChatController`: chiama Elara via HttpClient (`/status/engine`, `/api/chat`, `/api/chat/stream`) con token/URL in env (`ELARA_API_TOKEN`, `ELARA_BASE_URL`). `max_redirects` impostato a 5 per gestire 302.
- Console AI (`/ai/console`) con stream SSE lato frontend (Stimulus).

## Persistenza e migrazioni
- Migrazioni versionate in `migrations/` (inclusa quella per `cost_category`).
- Campi monetari: `Ship.price` e importi mutuo sono `DECIMAL` a DB ma tipizzati come `float` in PHP; rischio di perdita di precisione nei calcoli finanziari (vedi Mortgage::calculate). Per maggiore accuratezza si suggerisce l’uso di value object Money/Decimal o `string` + `bc*`.

## Note operative e punti di attenzione
- **User null:** dati preesistenti senza `user` non supereranno i voter; valutare una migrazione di popolamento o un comportamento di fallback.
- **Filtri per utente:** liste e recuperi puntuali delle entità protette passano sempre da repository che filtrano per `user` e i controller restituiscono 404 se l’entità non appartiene all’utente corrente, riducendo il rischio di ID enumeration.
- **CSRF login:** configurato via form_login con CSRF abilitato; la configurazione CSRF stateless per `authenticate` è stata rimossa.
- **AI token/URL:** ora in `.env.local`; evitare hardcode. Verificare che la base URL punti al servizio corretto.
- **Dashboard:** card a sfondo scuro coerenti con tema EasyAdmin dark; testo “Apri” in azzurro.

## Checklist rapida di setup
1. Variabili env: `DATABASE_URL`, `ELARA_BASE_URL`, `ELARA_API_TOKEN`, `APP_SECRET`.
2. Dipendenze: `composer install`, frontend già con asset mapper/Stimulus.
3. Migrazioni: `php bin/console doctrine:migrations:migrate`.
4. Import seeds di contesto: `php bin/console app:context:import` (facoltativo).
5. Accesso admin: `/admin` con utente ROLE_ADMIN.

## Suggerimenti futuri
- Convertire importi finanziari a Money/Decimal per evitare drift.
- Aggiungere fallback per entità legacy senza user (es. assegnare all’utente corrente o bloccare con messaggio dedicato).
- Aggiungere test funzionali per login/CSRF, filtri per ownership e per i comandi di import/export di contesto.
