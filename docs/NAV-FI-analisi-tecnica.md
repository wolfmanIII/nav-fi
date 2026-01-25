# Nav-Fi³ Web – Analisi Tecnica (Aggiornata)

> **Versione**: 2.2
> **Ultimo Aggiornamento**: 2026-01-25

Applicazione Symfony dedicata alla gestione di navi, equipaggi, contratti e mutui nel contesto del gioco di ruolo **Traveller**.

## 1. Stack Tecnologico & Infrastruttura

*   **Framework**: Symfony 7.4 (PHP 8.2+).
*   **Frontend**:
    *   **Core**: Twig + Asset Mapper.
    *   **JS**: Stimulus.js per la dinamica (controller `year-limit`, `tom-select`, `imperial-date`).
    *   **CSS**: Tailwind CSS 4 + DaisyUI (Tema "Abyss", dark mode nativa).
*   **Database**: Doctrine ORM. Supporto primario PostgreSQL, compatibile con MySQL/SQLite.
    *   *Indici*: Ottimizzati su `Transaction` (`idx_transaction_sync`, `idx_transaction_chronology`) per performance del Ledger.
*   **Security**:
    *   **MFA**: `scheb/2fa-bundle` per TOTP (Google Authenticator).
    *   **OAuth**: `knpuniversity/oauth2-client-bundle` per Google Login.
    *   **Ownership**: Voter strict. Ogni entità `Asset`, `Mortgage`, `Financial` è segregata per `User` proprietario.
*   **PDF Generation**: `KnpSnappyBundle` wrapper di `wkhtmltopdf` (Qt patched).
    *   Template in `templates/pdf/contracts/` e `templates/pdf/ship/`.
    *   Configurazione binario via ENV `WKHTMLTOPDF_PATH`.

## 2. Architettura "Financial Core" (Ledger Service)

Il cuore del sistema è il servizio di contabilità a partita doppia.

### 2.1. Entità `Transaction`
È l'unica fonte di verità per i saldi.
*   **Amount**: `DECIMAL(15,2)` (gestito via BCMath come stringa PHP).
*   **Status**:
    *   `PENDING`: Transazione futura (data > data sessione campagna).
    *   `POSTED`: Transazione effettiva (data <= data sessione).
    *   `VOID`: Transazione annullata.
*   **Time Travel**: Il campo `sessionDay`/`sessionYear` sulla transazione viene confrontato con il "Cursore Temporale" della Campagna.

### 2.2. Service `LedgerService`
Gestisce la logica di business:
*   **`deposit()` / `withdraw()`**: Metodi atomici per creare transazioni.
*   **`processCampaignSync(Campaign $campaign)`**: Metodo critico richiamato al cambio data della campagna.
    *   *Forward*: Pending -> Posted (Rilascia fondi).
    *   *Backward*: Posted -> Pending (Storna fondi - Undo temporale).
*   **`reverseTransactions()`**: Strategia di "Soft Edit". Invece di modificare una transazione passata, il sistema crea una transazione di rettifica (uguale e contraria) e ne emette una nuova.

### 2.3. Trade Liquidation Protocol house
Implementa il workflow di acquisto e vendita merce:
*   **Inventory (Unsold Goods)**: Gli acquisti via Cube (Trade) vengono isolati finché non viene registrata una vendita corrispondente.
*   **Liquidation Mapping**: Conversione di un `Cost` (Acquisto) in un `Income` (Vendita) con link diretto tra le entità tramite `IncomeTradeDetails`.
*   **Financial Balance**: Sincronizzazione automatica tra quantità venduta e stato del carico (Sold/Hold).

## 3. Dominio Applicativo & Logiche Chiave

### 3.1. Campaign & Time Cursor
*   L'entità `Campaign` agisce come "Master Clock".
*   `CampaignSessionLog`: Snapshot JSON immutabile creato a ogni cambio data.
*   Le navi ereditano la data dalla loro campagna assegnata.

### 3.2. Asset (Ship) & Components
*   **ShipDetails (JSON)**: Specifica tecnica flessibile. M-Drive, J-Drive, Power Plant, Weapons.
*   **Amendments**: Modifiche post-varo. Ogni amendment è legato a un `Cost` (es. "Acquisto Torretta laser"). Questo lega la modifica tecnica all'uscita di cassa.

### 3.3. Contrattualistica (Income/Cost)
*   **Categorie**: Gestite via tabelle di contesto (`IncomeCategory`, `CostCategory`).
*   **Dettagli JSON**: Ogni categoria ha campi specifici (es. `Freight` ha 'cargo_tonnage', `Passage` ha 'passenger_count').
*   **Stato**: `Draft` -> `Signed`. La firma blocca (logicamente) i dati principali e abilita la stampa PDF.

### 3.4. Mutui (Mortgage)
*   Relazione 1-to-1 con Ship.
*   Piano di ammortamento a 13 periodi (mesi imperiali).
*   Stampa PDF ufficiale del contratto di mutuo con clausole e garanti (`Company`).

### 3.5. Navigazione
*   **Routes**: Sequenze di Waypoint (Sector/Hex).
*   **TravellerMap**: Integrazione API. Coordinate sincronizzate. Link diretti alla mappa tattica.
*   **Fuel Math**: Calcolo stimato del consumo in base al Jump Rating e Tonnellaggio.

## 4. Interfaccia "Tactical Bridge"
L'UI non segue i canoni standard di un pannello admin, ma simula un'interfaccia diegetica.
*   **Bento Grids**: Layout a blocchi densi per massimizzare le informazioni a schermo.
*   **Macro `_tooltip.html.twig`**: Standardizzazione visuale delle azioni.
*   **Feedback**: Messaggi utente in stile "Voice of the Machine" (es. "Ledger Integrity Verified").

## 5. Deployment & Operatività
*   **Docker**: Container `php:8.2-fpm` + Nginx.
*   **Env Variables**:
    *   `APP_DAY_MIN`/`MAX` (es. 1-365).
    *   `WKHTMLTOPDF_PATH`.
*   **Comandi Console**:
    *   `app:context:import`: Carica i dati di base (leggi, ruoli, equipaggiamento standard).
    *   `app:fiscal-close`: (Opzionale) Archiviazione transazioni anno fiscale precedente.

## 6. QA & Verification house
Il sistema include una **Automated Verification Suite** (`ComprehensiveWorkflowTest.php`) che simula:
*   Generazione e accettazione opportunità (Freight, Pax, Mail, Contract).
*   Selezione manuale delle date di missione (Pickup, Departure).
*   Ciclo di trading completo (Purchase -> Hold -> Sale).
*   Integrità del ledger e bilanciamenti cassa. house
