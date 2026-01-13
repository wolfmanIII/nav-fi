# Nav-Fi Web – Analisi tecnica

Applicazione Symfony dedicata alla gestione di navi, equipaggi, contratti e mutui nel contesto del gioco di ruolo **Traveller**.

Questo documento descrive in modo discorsivo l’architettura attuale di Nav-Fi Web, le sue dipendenze, i componenti applicativi principali e alcuni punti di attenzione operativi.

## Stack e infrastruttura
- **Framework:** Symfony 7.3 (PHP ≥ 8.2), asset mapper, Stimulus, Twig, Tailwind 4 + DaisyUI per la UI (tema Abyss), Tom Select per le select con ricerca.
- **Security & MFA:** Integrazione `scheb/2fa-bundle` per Two-Factor Authentication (Google Authenticator/TOTP) e `knpuniversity/oauth2-client-bundle` per Google Login integration.
- **Date imperiali:** helper `ImperialDateHelper` + filtro Twig `imperial_date` per formattazione coerente `DDD/YYYY`.
- **Tom Select (integrazione):** inizializzato via controller Stimulus `tom-select`; asset JS/CSS caricati da `assets/vendor/tom-select/` per evitare importmap bare‑module.
- **Highlight.js:** usato per la formattazione dei JSON (session timeline Campaign) con asset locali in `assets/vendor/highlightjs/`.
- **Persistenza:** Doctrine ORM con PostgreSQL/MySQL/SQLite.
- **Admin:** EasyAdmin per le entità di contesto.
- **PDF:** wkhtmltopdf via KnpSnappy (binario da `WKHTMLTOPDF_PATH`), template contratti in `templates/pdf/contracts` e scheda nave in `templates/pdf/ship/SHEET.html.twig`.
- **Parametri day/year:** limiti configurabili via env (`APP_DAY_MIN/MAX`, `APP_YEAR_MIN/MAX`) e iniettati nei form IntegerType/ImperialDateType dedicati ai campi giorno/anno.
- **Tactical UI Components:** macro `_tooltip.html.twig` per pulsanti azioni uniformati; sistema di filtri `index_filters.html.twig` con grid layout reattivo e design "Tactical Search Terminal".
- **Stimulus form helpers:** controller `year-limit` applica il min anno derivato dallo `startingYear` della Campaign sulla Ship selezionata, con fallback ai limiti env.

## Dominio applicativo
- **Campagne e sessioni:** `Campaign` con calendario di sessione (giorno/anno) e relazione 1–N con Ship; le date sessione mostrate nelle liste/PDF derivano dalla Campaign della nave.
- **Session timeline Campaign:** `CampaignSessionLog` registra ogni aggiornamento della session date con snapshot JSON (campaign + ships + log operativi) e lo mostra in pagina con highlight.
- **Navi e mutui:** `Ship`, `Mortgage`, `MortgageInstallment`, `InterestRate`, `Insurance`; il mutuo conserva `signingDay/Year` derivati dalla sessione della Campaign e `signingLocation` raccolta via modale al momento della firma. Il PDF del mutuo è generato da template dedicato; i piani usano 13 periodi/anno (esempio: 5 anni ⇒ 65 rate).
- **Dettagli nave strutturati:** `Ship.shipDetails` (JSON) con DTO/form (`ShipDetailsData`, `ShipDetailItemType`, `MDriveDetailItemType`, `JDriveDetailItemType`) per hull/drive/bridge e collezioni (weapons, craft, systems, staterooms, software). Il “Total Cost” dei componenti è calcolato lato client e salvato nel JSON, ma **non** modifica `Ship.price`.
- **Amendment nave:** `ShipAmendment` registra modifiche post‑firma con `patchDetails` (stessa struttura di `Ship.shipDetails`) e **Cost reference obbligatoria** (categorie SHIP_GEAR/SHIP_SOFTWARE). La data effetto viene derivata dalla payment date del Cost selezionato; la select Cost usa ricerca (Tom Select) e filtra cost già usati da altri amendment.
- **Rotte e waypoints:** `Route` lega Campaign + Ship e contiene `startHex/destHex` (auto‑seal dai waypoints), date start/dest, jumpRating/fuelEstimate (override opzionali). I waypoints (`RouteWaypoint`) includono `hex`, `sector` (nome o abbreviazione T5SS), `world`, `UWP`, `jumpDistance` e note. La mappa usa `https://travellermap.com/go/{sector}/{hex}` per aprire/embeddare la rotta.
- **Route math helper:** `RouteMathHelper` calcola distanze (hex grid) e fuel estimate secondo `docs/Traveller-Fuel-Management.md`; se un segmento supera il jump rating, la form segnala errore.
- **Lookup TravellerMap (on‑the‑fly):** servizio `TravellerMapSectorLookup` scarica `https://travellermap.com/data/{sector}` (cache 24h) e può restituire `world`/`UWP` per un `hex` (endpoint interno `/route/waypoint-lookup`).
- **Annual Budget per nave:** ogni budget è legato a una singola nave e aggrega ricavi (`Income`), costi (`Cost`) e rate del mutuo pagate nel periodo (start/end giorno/anno). Dashboard e grafico mostrano la timeline per nave con chiavi day/year normalizzate dal helper.
- **Equipaggio:** `Crew` con ruoli (`ShipRole`); la presenza di capitano è validata da validator dedicato.
- **Status crew e date:** `Crew.status` + date associate (Active/On Leave/Retired/MIA/Deceased) gestite via `ImperialDateType`. La UI mostra la data relativa allo status solo quando la ship è selezionata.
- **CostCategory / IncomeCategory:** tabelle di contesto per tipologie di spesa/entrata (code, description), con seeds JSON.
- **Cost detail items:** `Cost.detailItems` (JSON) alimenta l’amount calcolato (campo read‑only lato form) e la stampa PDF del Cost.
- **Company e CompanyRole:** controparti contrattuali usate da `Cost`, `Income` e `Mortgage`.
- **CompanyRole.shortDescription:** etichetta breve usata nelle select e nelle liste per rendere i ruoli immediati.
- **LocalLaw:** codice, descrizione breve e disclaimer giurisdizionale; referenziato da Cost, Income, Mortgage.
- **Income dettagliato per categoria:** relazioni 1–1 (Freight, Passengers, Contract, Trade, Subsidy, Services, Insurance, Interest, Mail, Prize, Salvage, Charter, ecc.) con campi specifici; le sottoform sono attivate da `IncomeDetailsSubscriber` in base a `IncomeCategory.code` (mappa opzionale consultabile in `ContractFieldConfig`). Lo status è **Draft/Signed** e viene impostato automaticamente dalla signing date (se completa → Signed).
- **Tracciamento utente:** FK `user` (nullable) su Ship, Crew, Mortgage, MortgageInstallment, Cost, Income, AnnualBudget e Company. Un listener Doctrine (`AssignUserSubscriber`) assegna l’utente loggato in `prePersist`.
- **Company cross-campaign:** le controparti (`Company`, `CompanyRole`, `LocalLaw`) vengono definite una volta e riutilizzate su più campagne; questo garantisce consistenza nei costi/entrate e nei PDF, tenendo separati contesto e sessione.

### Relazioni principali
- Campaign 1–N Ship (calendario di sessione condiviso).
- Ship 1–1 Mortgage (vincolo univoco: una nave ha al massimo un mutuo).
- Ship 1–N Crew, 1–N Cost, 1–N Income, 1–N AnnualBudget.
- Ship 1–N Route.
- Mortgage 1–N MortgageInstallment; ManyToOne InterestRate/Insurance; ManyToOne Company/LocalLaw.
- Crew N–M ShipRole.
- Cost ManyToOne CostCategory, Company, LocalLaw.
- Income ManyToOne IncomeCategory, Company, LocalLaw + 1–1 con tabella dettaglio categoria.
- CompanyRole 1–N Company.
- Route 1–N RouteWaypoint.

## Sicurezza e perimetro tattico
- **Autenticazione Multi-Livello:** Login standard Symfony integrato con **Two-Factor Authentication (2FA)** e supporto per **Google OAuth**.
- **User Ownership Isolation:** FK `user` obbligatoria su tutte le entità core (`Ship`, `Crew`, `Mortgage`, `Cost`, `Income`, `AnnualBudget`). Il sistema garantisce l'isolamento dei dati tramite:
  - **Voter dedicati** che verificano la proprietà prima di ogni operazione di edit/delete.
  - **Ownership Repositories** (`findOneForUser`, `findAllForUser`) che filtrano alla sorgente, impedendo l'accesso via ID enumeration (404 se l'entità non appartiene all'utente).
- **Session Protection:** CSRF abilitato su tutti i form e sessioni protette.
- **Localizzazione numerica:** `twig/intl-extra` formatta importi in liste e PDF secondo la locale richiesta.
- **Validazione day/year:** i form usano `IntegerType` e `DayYearLimits`; il min anno deriva dallo `startingYear` della Campaign della Ship selezionata (fallback `APP_YEAR_MIN`) ed è propagato lato client via Stimulus. Il validator `ImperialDateComplete` forza la compilazione completa day+year quando il campo è required.

## Tactical UI Architecture (v2.0.x)
- **Grid Strategy:** Utilizzo di grid layout dinamici per adattare la visualizzazione di tabellari complessi a formati a piena larghezza, garantendo spazio per la telemetria finanziaria.
- **Search Terminal Pattern:** I filtri delle liste (`index_filters`) adottano un design "terminale" con background `bg-slate-950/40`, bordi `cyan-500/20` e labeling ad alto contrasto.
- **Macro Standardization:** La macro `_tooltip.html.twig` centralizza il rendering di icone e azioni, forzando contenitori `inline-flex` per eliminare artefatti visivi e garantire centratura perfetta.
- **Nav-Ops Pagination:** Paginazione custom che unisce estetica monospaced e terminologia Traveller per un'esperienza coerente col bridge di comando.

## EasyAdmin
- Dashboard personalizzata (`templates/admin/dashboard.html.twig`) con card di link rapidi per le entità di contesto (InterestRate, Insurance, ShipRole, CostCategory, IncomeCategory, CompanyRole, LocalLaw, Company).
- CRUD dedicati per le tabelle di contesto e per Company.

## Comandi e seeds di contesto
- **Export:** `php bin/console app:context:export --file=config/seed/context_seed.json`
- **Import:** `php bin/console app:context:import --file=config/seed/context_seed.json`
  - Trunca e ricarica le tabelle di contesto (ship_role, insurance, interest_rate, cost_category, income_category, company_role, local_law).


## Contratti e PDF
- Template HTML Twig in `templates/pdf/contracts` per le principali categorie di Income; i placeholder sono documentati in `docs/contract-placeholders.md`.
- Servizio `PdfGenerator` basato su KnpSnappy/wkhtmltopdf per stampare i contratti Income, il mutuo e la scheda nave; percorso binario configurato in `config/packages/knp_snappy.yaml` via env. Le versioni dei template sono centralizzate in `config/template_versions.php` e richiamate nei PDF.
- I campi opzionali delle sottoform Income sono determinati dal codice categoria e mostrati solo se richiesti (form dinamiche con event subscriber).
- Le date nei PDF e nelle liste sono formattate `DDD/YYYY` tramite `ImperialDateHelper` e filtro Twig `imperial_date`.
- Nel PDF del mutuo l’elenco crew è filtrato: esclusi `Missing (MIA)`/`Deceased` e inclusi solo membri con `activeDate >= signingDate`.
- Nel PDF nave la sezione “Amendments Log” mostra code, titolo, data effetto e Cost ref + amount.
- Le amendment sono disponibili solo se il mutuo è firmato e vengono gestite in pagina dedicata (`/ship/{id}/amendments/new`).

## Persistenza e migrazioni
- Migrazioni versionate in `migrations/` (inclusa quella per `cost_category`).
- Campi monetari: `Ship.price` è `DECIMAL` a DB e tipizzato `string` in PHP. La logica di calcolo mutuo usa BCMath e normalizza gli importi a stringa per evitare errori di accumulo; resta consigliato mantenere importi a tipo esatto (string + `bc*` o integer di “crediti” con fattore 100) per coerenza end-to-end.

## Note operative e punti di attenzione
- **User null:** dati preesistenti senza `user` non supereranno i voter; valutare una migrazione di popolamento o un comportamento di fallback.
- **Filtri per utente:** liste e recuperi puntuali delle entità protette passano sempre da repository che filtrano per `user` e i controller restituiscono 404 se l’entità non appartiene all’utente corrente, riducendo il rischio di ID enumeration.
- **Ricerca e paginazione:** le principali liste hanno filtri (testo, categoria, ship, campaign) e paginazione 10 elementi per pagina; la UI usa un componente Twig dedicato (`templates/components/pagination.html.twig`).
- **CSRF login:** configurato via form_login con CSRF abilitato; la configurazione CSRF stateless per `authenticate` è stata rimossa.
- **Dashboard:** card a sfondo scuro coerenti con tema EasyAdmin dark; testo “Apri” in azzurro.
- **PDF/wkhtmltopdf:** assicurarsi che il binario sia disponibile e che l’opzione `enable-local-file-access` resti abilitata per caricare asset locali nei PDF.
- **Form giorno/anno:** limiti validativi configurati via env; aggiornare `.env.local` in base al calendario imperiale usato al tavolo. Il datepicker imperiale ha pulsanti mese e un tasto Clear che svuota il giorno mantenendo l’anno. In UI/PDF il formato è uniforme via `imperial_date`.
- **Sessione campagne:** sessionDay/sessionYear vive su Campaign; le Ship mostrano i valori ereditati. Migrazioni legacy potrebbero aver popolato le Ship: mantenerle allineate se si rimuovono i campi.
- **Workflow Crew:** l’assegnazione da lista “unassigned” imposta `status=Active` e `activeDay/Year` alla session date; lo sgancio della ship azzera status (salvo `Missing (MIA)`/`Deceased`) e le date Active/On Leave/Retired. L’elenco “unassigned” esclude Missing/Deceased.
- **Ship details JSON:** il form salva blocchi strutturati; se si altera la struttura, valutare migrazioni o normalizzazioni per non perdere dati.
- **Amendment e Cost reference:** un amendment richiede un Cost con payment date valorizzata; la selezione esclude cost già collegati ad altri amendment per evitare duplicazioni.

## Flussi operativi principali

1. **Setup campagna e contesto:** definire cataloghi (InterestRate, Insurance, ShipRole, CostCategory, IncomeCategory, CompanyRole, LocalLaw), creare la Campaign con calendario imperiale e registrare le ships (incluso `shipDetails` JSON).
2. **Gestione equipaggio:** associare crew alle ship, assegnare ruoli e capitano via modal Stimulus (`role-selector`), supportare filtri per crew non assegnati e paginazione unificata.
   - Lo status e la data relativa diventano obbligatori solo quando la ship è selezionata.
   - La lista “unassigned” esclude Missing/Deceased e l’assegnazione imposta Active + session date.
3. **Mutui, costi e income:** la firma del mutuo avviene con `signingDay/Year` dalla Campaign; costi e entrate hanno `Company`/`LocalLaw` cross-campaign e utilizzano `ImperialDateType` + PDF builder per stampare contratti e schede bianche.
4. **Amendment nave firmata:** se la nave ha mutuo firmato, le modifiche ai componenti passano tramite `ShipAmendment` con `patchDetails` (stessa struttura di `shipDetails`) e **Cost reference obbligatoria** (SHIP_GEAR/SHIP_SOFTWARE); la data effetto deriva dalla payment date del Cost.
5. **Annual budget per nave:** aggregare income, cost e rate in 13 periodi annui, validare `start <= end`, formattare le date con `ImperialDate` e rappresentare il bilancio sulla UI e nei PDF.
6. **Routes e navigazione:** creare rotte con waypoints sector+hex, calcolare jumpDistance e fuel estimate, e aprire la mappa TravellerMap via `/go/{sector}/{hex}` per verifiche rapide.
7. **UX e riferimenti:** tooltip e badge uniformati (vedi `docs/tooltip-guidelines.md`), sidebar e checklist enfatizzano il flusso "Campaign first → Ships/Crew → Companies → Cost/Income/Mortgage/Budget/Routes".

## UX e documentazione

- Sidebar e homepage enfatizzano le fasi chiave (campagna, shipyard, mortgage, budget) con badge, tooltip e icone scalabili (`icons/crew.html.twig` con `dim`).
- Le guide `docs/*` coprono calendari imperiali (`traveller_imperial_calendar.md`), snippet contratti (`contract-placeholders.md`) e la documentazione operativa (`NAV-FI-miglioramenti.md`).
- Il documento `docs/tooltip-guidelines.md` definisce come standardizzare tooltip/badge e preparare un macro `templates/_tooltip.html.twig`.
- Ogni documento viene aggiornato contestualmente al codice (setup calcoli mutuo, PDF, Stimulus `imperial-date`, macros per `year-limit`).

## Rischi aperti e prossimi passi

1. **Tooltip & icone riutilizzabili:** implementare un macro Twig (es. `_tooltip.html.twig`) per uniformare i tooltip e ridurre la duplicazione di HTML e copy.
2. **Test funzionali:** automatizzare i flussi critici (filtri/paginazione, ownership, PDF, login) per coprire i casi di uso del tavolo di gioco.
3. **Documentazione vivente:** mantenere aggiornati README e doc `NAV-FI-*` con i nuovi workflow (calendar, cross-campaign, ImperialDateType, tooltip macro).
4. **Contesto multi-utente:** verificare l’impatto del filtro `Campaign.user` su eventuali scenari condivisi e considerare un sistema di Ownership più granulare o un’associazione esplicita per le ship condivise.

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
