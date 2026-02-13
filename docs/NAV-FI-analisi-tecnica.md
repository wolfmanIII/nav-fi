# Nav-Fi³ Web – Analisi Tecnica (Autorità Centrale)

> **Versione**: 3.1 (Navigation Update)
> **Ultimo Aggiornamento**: 2026-02-13

**Nav-Fi³** è una piattaforma applicativa web enterprise-grade (Symfony 7.4) finalizzata alla gestione contabile, tattica e logistica di campagne per il sistema di simulazione **Traveller**. Il sistema è progettato come un **Tactical Operating System (TOS)** con un motore finanziario double-entry e un sistema di cronologia basato su cursore temporale.

## 1. Stack Tecnologico & Infrastruttura

*   **Framework**: Symfony 7.4 (PHP 8.2+).
*   **Frontend**:
    *   **Core**: Twig + Symfony Asset Mapper.
    *   **JS**: Stimulus.js (controller per `year-limit`, `tom-select`, `imperial-date`, `salary-pro-rata`).
    *   **CSS**: Tailwind CSS 4 + DaisyUI (Tema "Abyss", dark mode nativa).
*   **Database**: Doctrine ORM (PostgreSQL primario).
    *   **BCMath**: Utilizzato per tutti i calcoli monetari (Cr/MCr) per evitare errori di virgola mobile.
*   **Security**: MFA (TOTP) + OAuth (Google Login). Ownership strict via Voters e controlli di integrità a livello di Campagna. Gestione errori standardizzata tramite `\Throwable` per la cattura di runtime error fatali.
*   **PDF Generation**: `KnpSnappyBundle` con `wkhtmltopdf` (Qt patched).

## 2. Architettura "Financial Core" (The Ledger)

Il sistema finanziario di Nav-Fi³ è stato consolidato in un'architettura **Stateless & Time-Aware** che garantisce l'integrità dei dati tramite tre layer di controllo.

### 2. Anatomia Finanziaria di un Asset
Il Financial Core 3.0 non è solo un registro di transazioni, ma un sistema gerarchico che modella la capacità economica di un'entità di gioco.

#### Gerarchia delle Entità
1.  **Asset (L'Entità)**: La nave, la base o il team. È il proprietario legale dei fondi.
2.  **Financial Account (Il Portafoglio)**: Collegato 1:1 all'Asset. Contiene il saldo corrente (`credits`) e il riferimento all'istituto bancario (`bank`).
3.  **Transaction (L'Evento)**: L'atomo del Ledger. Ogni transazione è legata a un Account e modifica il saldo solo quando diventa `POSTED`.

> [!IMPORTANT]
> **Stato vs Storia**
> Il `FinancialAccount` mantiene lo *Stato* (quanti soldi ci sono *adesso*), mentre il `Ledger` mantiene la *Storia* (perché e quando i soldi sono cambiati). Il `LedgerService` garantisce che lo Stato sia sempre la somma algebrica della Storia filtrata dal Time Cursor.

---

### 3. LedgerService: Core Engine
Il `LedgerService` è il motore di calcolo che governa il flusso monetario. A differenza dei sistemi contabili tradizionali, Nav-Fi³ gestisce il tempo in modo fluido tramite un **Time Cursor**:
*   **Temporal Immutability**: Ogni modifica a una transazione postata (data <= sessione) genera automaticamente una transazione di `REVERSAL` (storno) e l'emissione di una nuova voce correttiva.
*   **Lifecycle Sync (`processCampaignSync`)**: Al variare della data sessione della Campagna, il sistema scansiona il mastro:
    *   **Future -> Present (`PENDING` -> `POSTED`)**: I fondi vengono accreditati/addebitati nel saldo reale dell'Asset.
    *   **Present -> Future (`POSTED` -> `PENDING`)**: In caso di "viaggio nel tempo" all'indietro, le transazioni vengono stornate dai saldi e riportate in attesa.

### 4. Hybrid Entity Resolution (`FinancialAccountManager`)
Il sistema supporta l'inserimento rapido tramite un layer di risoluzione ibrida che minimizza l'attrito durante la sessione di gioco:
*   **Entity Linking Protocol**: Utilizza una logica XOR rigorosa. L'utente può collegare un'entità esistente (Company/FinancialAccount) OR inserirne una nuova testualmente.
*   **On-the-fly Creation**: Se viene inserito un nome nuovo (es. `bankName` o `vendorName`), il manager:
    1.  Crea la `Company` (con ruolo Bank o Vendor).
    2.  Crea il `FinancialAccount` collegato all'Asset.
    3.  Persiste il tutto atomicamente con la transazione principale.

### 5. Administrative Layer (`FinancialAccountController`)
Oltre alla creazione automatica, il sistema espone un’interfaccia di gestione dedicata per il controllo granulare dei conti:
*   **Manual Override**: Permette la creazione di account indipendenti o il ricollegamento manuale ad altri Asset.
*   **Balance Correction**: Consente l'aggiornamento del saldo nominale (`credits`) per correzioni amministrative dirette (fuori dal Ledger).

---

### 6. UI Integrity Layer (Stimulus.js)
Per superare i limiti del protocollo HTTP (campi disabilitati non inviati) e garantire la precisione, è stato implementato un layer di verifica:
*   **Auto-Recovery Protocol**: Se il JavaScript della form disabilita un campo (es. `financialAccount` quando l'Asset lo possiede già), il sistema recupera automaticamente l'associazione corretta lato server durante l'evento `POST_SUBMIT`.
*   **Strict XOR Validation**: Impedisce stati ambigui (es. selezionare un account esistente E scriverne uno nuovo). Errori di questo tipo vengono bloccati con `FormError` specifici.
*   **Smart Visibility Flow**: Utilizza Stimulus.js per gestire la **Progressive Disclosure**. I campi di creazione e gestione fondi appaiono solo dopo la selezione di un Asset valido, riducendo il rumore informativo.

## 3. Moduli Funzionali & Dominio

### 3.1. The Cube (Contract Broker)
Generatore deterministico di opportunità basato su **Seed** fisso e dati TravellerMap.
*   **Advanced Acceptance**: Supporto per sovrascrittura manuale della data missione (Pickup, Departure, Dispatch) e gestione scadenze (`deadline`).
*   **Security Context**: Filtro rigoroso degli Asset per Campagna di appartenenza e verifica ownership dell'opportunità prima dell'accettazione.
*   **Conversion Engine**: Trasformazione rigorosa dei DTO in entità `Income` o `Cost` con popolamento automatico dei dettagli ricchi.

### 3.2. Trade & Liquidation Protocol
*   **Inventory**: Gli acquisti via Cube (Trade) vengono isolati come `Cost` finché non sono venduti.
*   **Liquidation Mapping**: Conversione di un `Cost` (Acquisto) in `Income` (Vendita) con link diretto tramite `IncomeTradeDetails` per calcolo automatico di profitto/perdita.

### 3.3. Salary Management
*   **Ciclo Fisso**: 28 giorni imperiali.
*   **Pro-rata Logic**: Calcolo automatico del primo stipendio basato sui giorni trascorsi dall'attivazione del membro dell'equipaggio.
*   **Ledger Integration**: I pagamenti generano prelievi automatici allo scattare del ciclo temporale.

### 3.4. Mortgages (Mutui)
*   Piano di ammortamento a 13 periodi (mesi imperiali).
*   Relazione 1-to-1 con Ship, con generazione PDF del contratto ufficiale.

### 3.5. Navigation & Routes (TravellerMap Integration)
*   **Pathfinding (A*)**: Il `RouteOptimizationService` calcola percorsi ottimali tra esagoni considerando il `Jump Rating` e i dati astrografici.
*   **TSP Optimization**: Algoritmo per ordinare tappe multiple in una rotta complessa (Traveling Salesman Problem).
*   **Asynchronous Navigation HUD**: Sistema di viaggio gestito tramite API JSON e controller Stimulus (`route_travel_controller`), che permette attivazione e transito tra waypoint senza reload della pagina.
*   **Real Distance Calculation**: Il calcolo del `Jump Distance` è stato migrato da una stima teorica alla somma dei parsec reali tra i waypoint. Questo garantisce precisione nel calcolo del carburante stimato (`RouteMathHelper`).
*   **Temporal Sync**: Ogni salto registrato (Engagement/Transit) avanza automaticamente la data della Campagna di 7 giorni standard, sincronizzando l'HUD e la navbar in tempo reale.
*   **Performance Cache**: Implementata una cache a livello di richiesta (`$sectorCache`) per i lookup dei settori TravellerMap, riducendo l'overhead sulle chiamate AJAX per la visualizzazione delle zone (Red/Amber).
*   **TravellerMap API**: Lookup dinamico di UWP, nomi dei mondi e trade codes. Integrazione via iframe e Static Map API per la visualizzazione tattica.

### 3.6. Narrative System (The Cube Engine)
*   **Template Logic**: Generazione di descrizioni immersive tramite componenti dinamici (Patron, Ambizione, Opposizione, Urgenza).
*   **Hybrid Patrons**: Sistema che alterna Compagnie residenti nel database e Patron NPC generati casualmente (via `patron_alias`).
*   **Government-Aware Filtering**: Le opportunità generate sono filtrate in base al codice di governo del sistema stellare per garantire coerenza narrativa.

## 4. Interfaccia "Command Deck"
L'UI simula un'interfaccia diegetica per massimizzare l'immersione.
*   **Bento Grids**: Layout a blocchi densi per alta densità informativa.
*   **Bespoke Components**: Tooltip, badge e dashboard differenziate cromaticamente per modulo (Cyan per Operatività, Emerald per Finanza, Amber per Attenzione).

## 6. QA & Verification
Il sistema include una **Automated Verification Suite** (`ComprehensiveWorkflowTest.php`) che simula:
*   Generazione e accettazione di ogni tipo di missione.
*   Workflow completo di Trading (Buy -> Hold -> Sell).
*   Integrità del ledger e calcoli del Ledger.

## Appendice A: Tabelle Economiche (Update 2022)
I payout e i costi seguono gli standard ufficiali Traveller Core.

| Dist (PC) | High   | Middle | Basic | Low   | Freight |
|-----------|--------|--------|-------|-------|---------|
| 1         | 9,000  | 6,500  | 2,000 | 700   | 1,000   |
| 2         | 14,000 | 10,000 | 3,000 | 1,300 | 1,600   |
| 3         | 21,000 | 14,000 | 5,000 | 2,200 | 2,600   |
| 4         | 34,000 | 23,000 | 8,000 | 3,900 | 4,400   |
| 5         | 60,000 | 40,000 | 14,000| 7,200 | 8,500   |
| 6         | 210,000| 130,000| 55,000| 27,000| 32,000  |

*   **Mail**: Flat rate 25,000 Cr per container (5 ton).
*   **Charter**: 900 Cr per tonnellata di scafo (periodo 2 settimane).

## Appendice B: Codici di Riferimento
### Cost Categories
- `PERSONAL`, `CREW_GEAR`, `SHIP_GEAR`, `SHIP_SOFTWARE`, `SHIP_MAINT`, `SHIP_REPAIR`, `MEDICAL`, `TRAVEL`, `LEGAL`, `RECRUITMENT`.
### Income Categories
- `FREIGHT`, `PASSENGERS`, `MAIL`, `CHARTER`, `CONTRACT`, `TRADE`, `SALVAGE`, `PRIZE`, `SUBSIDY`, `SERVICES`, `INSURANCE`, `INTEREST`.
