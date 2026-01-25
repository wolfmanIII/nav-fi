# Nav-Fi³ Web – Analisi Tecnica (Autorità Centrale)

> **Versione**: 3.0 (Consolidated)
> **Ultimo Aggiornamento**: 2026-01-25

**Nav-Fi³** è una piattaforma applicativa web enterprise-grade (Symfony 7.4) finalizzata alla gestione contabile, tattica e logistica di campagne per il sistema di simulazione **Traveller**. Il sistema è progettato come un **Tactical Operating System (TOS)** con un motore finanziario double-entry e un sistema di cronologia basato su cursore temporale.

## 1. Stack Tecnologico & Infrastruttura

*   **Framework**: Symfony 7.4 (PHP 8.2+).
*   **Frontend**:
    *   **Core**: Twig + Symfony Asset Mapper.
    *   **JS**: Stimulus.js (controller per `year-limit`, `tom-select`, `imperial-date`, `salary-pro-rata`).
    *   **CSS**: Tailwind CSS 4 + DaisyUI (Tema "Abyss", dark mode nativa).
*   **Database**: Doctrine ORM (PostgreSQL primario).
    *   **BCMath**: Utilizzato per tutti i calcoli monetari (Cr/MCr) per evitare errori di virgola mobile.
*   **Security**: MFA (TOTP) + OAuth (Google Login). Ownership strict via Voters.
*   **PDF Generation**: `KnpSnappyBundle` con `wkhtmltopdf` (Qt patched).

## 2. Architettura "Financial Core" (The Ledger)

Il cuore del sistema è il **Ledger Service**, un motore di scrittura contabile che separa l'**Intento** dalla **Realtà di Cassa**.

### 2.1. Entità `Transaction`
Ogni riga del mastro è immutabile (logicamente) e definita da:
*   **Amount**: Valore decimale (BCMath). + Depositi, - Prelievi.
*   **Status**: 
    *   `PENDING`: Transazione futura (data > data sessione campagna).
    *   `POSTED`: Transazione effettiva (data <= data sessione).
    *   `VOID`: Transazione annullata.
*   **Temporal Mapping**: Collegata a `sessionDay`/`sessionYear` imperiali.

### 2.2. Logica "Time Cursor" (Viaggio nel Tempo)
Il tempo in Nav-Fi³ è fluido. Il `LedgerService` sincronizza il saldo `Asset.credits` basandosi sul cursore temporale della `Campaign`:
*   **Time Advance**: Transazioni `PENDING` -> `POSTED` (Rilascio fondi).
*   **Undo / Correction**: Transazioni `POSTED` -> `PENDING` (Storno fondi).

### 2.3. Strategia di Reversal (Soft Edit)
Per garantire l'audit, non modifichiamo mai l'importo di una transazione già postata. Se un valore cambia, il sistema genera una transazione di rettifica (uguale e contraria) e ne emette una nuova, mantenendo traccia della correzione.

## 3. Moduli Funzionali & Dominio

### 3.1. The Cube (Contract Broker)
Generatore deterministico di opportunità basato su **Seed** fisso e dati TravellerMap.
*   **Advanced Acceptance**: Supporto per sovrascrittura manuale della data missione (Pickup, Departure, Dispatch) e gestione scadenze (`deadline`).
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
