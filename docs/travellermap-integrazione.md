# Integrazione TravellerMap – Piano tecnico dettagliato

Obiettivo: tracciare e visualizzare rotte Traveller dentro Captain Log, sfruttando TravellerMap (embed e API) mantenendo coerenza con Campaign/Ship e con la UI sci-fi esistente.

> Nota: questa proposta separa ciò che è fattibile **senza API ufficiali** (link/iframe) da ciò che richiede **verifica sulla documentazione di TravellerMap**.
> Quando avremo accesso alla doc ufficiale, confermeremo endpoint, parametri, limiti e CORS.

## Modello dati
- **Route** (nuova entità)
  - `id` (int/uuid), `code` (uuid v7 per coerenza con altre entità visibili in UI)
  - `name` (string), `description` (text, opzionale)
  - `campaign` (ManyToOne Campaign, opzionale ma se ship ha campaign dovrebbe ereditare quella)
  - `ship` (ManyToOne Ship, **required**: una Ship può avere molte Route)
  - `plannedAt` (datetime immutable, default now)
  - `notes` (text, opzionale)
  - `startHex` (string, opzionale: es. "1234"), `destHex` (string, opzionale)
  - `jumpRating` (int, opzionale: override della ship se mancante)
  - `fuelEstimate` (decimal/string, opzionale: calcolato da helper)
  - Relazione 1–N con RouteWaypoint

- **RouteWaypoint** (nuova entità)
  - `id` (int/uuid), `order` (int)
  - `hex` (string, es. "1234")
  - `world` (string, opzionale – può provenire da lookup API)
  - `uwp` (string, opzionale – da lookup API)
  - `jumpDistance` (float/int, opzionale: parse da differenza esadecimale tra hex contigui)
  - `notes` (text, opzionale)
  - ManyToOne Route (una Route ha molti Waypoint; cascade persist, orphan removal)

- **Ship jump data**
  - Opzione A: aggiungere `jumpRating` e `fuelCapacity` su Ship (o in `shipDetails` JSON) per calcolo fuel.
  - Opzione B: tenere il calcolo lato Route con campo `jumpRating` e `fuelPerJump` inserito a mano dall’utente.

## Funzioni lato backend
- **Repository/Query**: Route per Campaign/Ship filtrate su user (coerente con altri repo), con join su waypoint ordinati.
- **Controller**: CRUD Route + azione dettaglio con embed mappa e tabelle waypoint.
- **DTO/Form**:
  - `RouteType`: campi base + select Campaign (obbligatoria) + Ship filtrata per Campaign/user; start/dest hex; jumpRating; fuelEstimate (opzionale); note.
  - `RouteWaypointType`: hex, world, uwp, jumpDistance (calcolabile), notes, ordine gestito da CollectionType (drag & drop JS opzionale).
- **Calcolo jump/fuel** (helper service):
  - Data una lista di hex (stringa 4 cifre), calcolare distanza Manhattan esadecimale tra waypoint consecutivi (convertire in base 16 a coordinate (col,row)).
  - Stimare fuel: `fuel = sum(distanzaSegmento) * fuelPerJumpUnit` oppure se si usa rating jumpN, validare che ogni segmento <= jumpRating.
  - Validazione: segnalare segmento > jumpRating con errore form.

## Integrazione TravellerMap
### Cosa possiamo fare sicuramente (senza API ufficiali)
- Link esterni a TravellerMap con parametri `p`/`jump`/`path` (da confermare) per aprire la mappa in una nuova scheda.
- Iframe embed con URL di TravellerMap per mostrare una vista statica del settore/hex.
- Route planner lato UI: il percorso è costruito localmente e passato a TravellerMap come query string.

### Cosa richiede verifica ufficiale
- Endpoint JSON per lookup (world, UWP, trade codes, stellar data).
- Endpoint static map (PNG/JPG) con marker/linee.
- Politiche CORS e rate limit per chiamate client-side.

- **Embed (semplice, zero API key)**:
  - Iframe con URL `https://travellermap.com/?p=<sector>/<hex>` o `.../jump?p=<hex>&j=<jump>` se disponibile.
  - Link "Apri su TravellerMap" generato da controller: costruire query string con `s=<startHex>&d=<destHex>` o `jump=<rating>` se supportato.

- **API Lookup (opzionale, con rete)**:
  - Endpoint TravellerMap JSON: `https://travellermap.com/api/sector/<sector>/hex/<hex>` o varianti (verificare doc ufficiale: https://travellermap.com/doc/api).
  - Service `TravellerMapClient` (HTTP client Symfony) con base URL configurabile via env, con flag di rete (disabilitabile se sandbox).
  - Cache locale (FilesystemAdapter) per ridurre chiamate; timeout basso (es. 5s) e fallback silenzioso in caso di errore.
  - Dati estratti: `world` name, `UWP`, trade codes, stellar data. Salvare su waypoint (non vincolante).

- **Static map overlay (facoltativo)**:
  - Se API static map disponibile: generare URL immagine con markers su hex dei waypoint; salvare URL in Route o rigenerarla on demand.
  - Alternativa: costruire un link con parametri `?options=...&path=HEX1,HEX2,...` se supportato.

## Frontend/UI
- **Dettaglio Campaign**: nuova scheda/box "Routes" con tabella rotte (nome, ship, start→dest, jumps, azioni) e pulsante "Add route".
- **Dettaglio Route**:
  - Hero stile sci-fi (coerente con altre pagine) con icona route (da creare, es. nav beacon).
  - Sezione embed mappa (iframe responsive, border, note sul link esterno), pulsante "Open on TravellerMap".
  - Tabella waypoint (inner-bordered-table) con ordine, hex, world, UWP, jump dist, note; CollectionType con add/remove e riordinamento (Stimulus drag & drop opzionale).
  - Card "Jump & fuel" con rating, fuel stimato, validazioni (segmento > jumpRating evidenziato).

- **Stimulus/JS**:
  - Controller per Collection waypoint (riordino, add/remove) riusando pattern già usati per shipDetails.
  - Controller opzionale per highlight segmenti che superano jumpRating.
  - year-limit non necessario sulle rotte, ma mantenere coerenza di gradient/badge.

## Sicurezza e ownership
- Rotte e waypoint legati a Ship (Ship 1→N Route; Route 1→N Waypoint); filtrare per utente corrente in repository come le altre entità; voter Route opzionale (seguire pattern ShipVoter).
- La Campaign è derivata dalla Ship (se presente) per coerenza con year-limit e calendario; validare che route.ship.campaign == route.campaign se la si imposta.

## Migrazioni e seed
- Migrazione Doctrine per Route + RouteWaypoint (FK a Campaign e Ship, cascade on delete su waypoint).
- Nessun seed richiesto inizialmente.

## PDF/Export (facoltativo)
- Aggiungere PDF "Route sheet" simile a ship sheet: overview campagna/ship, tabella waypoint, link TravellerMap.

## Config/env
- Parametro `TRAVELLERMAP_BASE_URL` (default `https://travellermap.com`), `TRAVELLERMAP_API_ENABLED` boolean.
- Se rete limitata, prevedere fallback: solo link/iframe statici senza chiamate API.

## Roadmap suggerita
1. Creare entità Route + RouteWaypoint + migrazione; repository filtrati per user.
2. Form Route + Waypoint collection; validazione jumpRating/segmenti lato PHP.
3. Controller/CRUD rotte e vista dettaglio con tabella waypoint e link TravellerMap (senza API). Icona route.
4. Embed mappa (iframe) e link "Open on TravellerMap" con start/dest.
5. (Opzionale) Service HTTP TravellerMap per lookup world/UWP + cache; UI che precompila world/uwp se trovati.
6. (Opzionale) PDF "Route" e/or static map URL.

## Note di stile e UX
- Header e tabelle coerenti con gradienti scuri già in uso; icona route in `templates/icons`.
- Campi hex: input text uppercase con validazione regex `^[0-9A-F]{4}$`.
- Mostrare badge "Jump limit exceeded" se un segmento supera `jumpRating`.
- Se Ship ha `campaign`, default route.campaign = ship.campaign per coerenza con year-limit.

## Checklist di verifica (doc ufficiale TravellerMap)
- Endpoint e parametri URL per mostrare percorso (path/jump/zoom/scale).
- Endpoint JSON per lookup world/UWP e formato risposta.
- Endpoint static map (PNG/JPG) e limiti dimensioni/uso.
- CORS e rate limit per chiamate browser.
- Policy di caching e attribution richiesto.
