# Nav-Fi³: Analisi Tecnica e Architetturale del Sistema

> **Versione Documento**: 1.1.0
> **Tipo**: Specifica Tecnica
> **Destinatari**: Engineering, DevOps, System Administrators

## 1. Introduzione e Obiettivi del Sistema

**Nav-Fi³** è una piattaforma applicativa web enterprise-grade, sviluppata su framework **Symfony 7.4**, finalizzata alla gestione contabile, tattica e logistica di campagne per il sistema di simulazione **Traveller**.

Il sistema è progettato come un **Tactical Operating System (TOS)**. A differenza dei tradizionali character sheet digitali, Nav-Fi³ implementa un motore finanziario double-entry (partita doppia), un sistema di cronologia basato su cursore temporale (Time Cursor) e un'interfaccia utente ottimizzata per l'operatività in tempo reale ("Command Deck").

### Obiettivi Primari
1.  **Integrità Dati**: Garantire la consistenza transazionale delle operazioni finanziarie (mutui, ammortamenti, costi operativi) attraverso un ledger immutabile.
2.  **Automazione dei Processi**: Automatizzare il calcolo di scadenze, interessi e stipendi basandosi sul calendario imperiale.
3.  **Supporto Decisionale**: Fornire metriche di solvibilità e proiezioni di budget per supportare le decisioni strategiche del tavolo di gioco.

---

## 2. Architettura del Software

### 2.1 Stack Tecnologico
*   **Backend Framework**: Symfony 7.4 (PHP 8.2+).
*   **Persistence Layer**: Doctrine ORM. Supporto primario per PostgreSQL; compatibilità mantenuta per MySQL e SQLite.
*   **Frontend Engine**: Twig, Symfony Asset Mapper, Stimulus.js per interazioni client-side.
*   **UI Framework**: Tailwind CSS 4 con libreria componenti DaisyUI (Tema personalizzato "Abyss" per ottimizzazione contrasto in ambienti low-light).
*   **Reportistica**: KnpSnappy integrato con `wkhtmltopdf` (patch Qt) per la generazione server-side di documentazione contrattuale in formato PDF.
*   **Sicurezza**: Symfony Security Bundle. Implementazione di 2FA (TOTP) e integrazione OAuth (Google) per l'autenticazione.

### 2.2 Pattern Architetturali
*   **Event-Driven Ledger**: disaccoppiamento tra logica di dominio e scrittura contabile. I controller emettono eventi di dominio (es. `MortgageInstallmentPaidEvent`), che vengono intercettati dal `LedgerService` per la generazione delle scritture contabili (`Transaction`).
*   **Temporal Cursor Pattern**: L'entità `Campaign` agisce come "source of truth" temporale. Le transazioni finanziarie vengono create con stato `PENDING` o `POSTED` in base alla relazione tra la loro data di competenza e la data corrente della campagna. Questo meccanismo consente operazioni di rettifica storica ("Time Travel") mantenendo la consistenza del saldo corrente.
*   **Service-Oriented Architecture (SOA)**: Logiche di business complesse (calcolo rotte, gestione ammortamenti) sono incapsulate in servizi dedicati (`RouteService`, `MortgageService`), mantenendo i controller leggeri (Thin Controllers).

---

## 3. Analisi dei Moduli Funzionali

### 3.1 Financial Core
Il modulo Financial Core costituisce il sottosistema critico dell'applicazione, gestendo la persistenza e l'elaborazione dei flussi economici.

*   **LedgerService**: Motore di scrittura contabile a partita doppia. Gestisce la creazione di `Transaction` collegate agli Asset.
*   **Entità Finanziarie**:
    *   `Mortgage`: Gestione complessa del debito a lungo termine, inclusa la pianificazione del piano di ammortamento su base annua (13 mensilità imperiali).
    *   `Income`: Gestione dei flussi in entrata. Supporta logiche di acconto (Deposit) e saldo finale (Balance), e gestione bonus contrattuali.
    *   `Cost`: Registrazione spese operative con categorizzazione granulare (Fuel, Life Support, Maintenance).
    *   `AnnualBudget`: Strumento di proiezione finanziaria. Aggrega i dati storici e previsionali per calcolare indici di solvibilità.

### 3.2 Asset Management
Gestione del ciclo di vita delle entità principali (Navi, Basi, Veicoli).

*   **Identità e Stato**: L'entità `Ship` incapsula non solo dati tecnici (Hull, Drive rating), ma anche lo stato giuridico e finanziario.
*   **Shipment Details**: I dati tecnici dettagliati sono serializzati in formato JSON (`shipDetails`) per garantire flessibilità nello schema dati senza richiedere migrazioni DB per ogni variazione di specifica.
*   **Amendment Tracking**: Sistema di versionamento delle modifiche tecniche. Ogni variante (es. upgrade armamenti) è collegata a un `Cost` specifico, garantendo la tracciabilità economica delle evoluzioni tecniche.

### 3.3 Human Resources (Crew & Roster)
Gestione del personale e delle gerarchie di bordo.

*   **Gestione Stato**: Macchina a stati finiti per la gestione del personale (`Active`, `MIA`, `Deceased`, `Retired`).
*   **Payroll System**: Servizio automatizzato per il calcolo e la generazione delle scritture di stipendio, basato su ciclicità di 28 giorni.
*   **Assegnazione**: Logica di assegnazione crew-to-asset con validazione dei ruoli.

### 3.4 Navigation & Operations
Modulo di supporto logistico e tattico.

*   **Route Calculation**: Integrazione API REST con **TravellerMap** per la validazione delle coordinate (Sector/Hex) e il calcolo vettoriale delle distanze (Parsec).
*   **Session Audit Log**: Sistema di logging immutabile per ogni variazione della data di campagna (`Session Timeline`), permettendo audit completi sulle attività svolte in specifiche finestre temporali.

---

## 4. Procedure Operative Standard (SOP)

### 4.1 Ciclo Mensile (Monthly Processing)
La procedura standard per la chiusura del mese contabile prevede:
1.  **Budget Review**: Analisi dell'entità `AnnualBudget` per identificazione passività imminenti.
2.  **Debt Servicing**: Elaborazione automatica e registrazione delle rate di `MortgageInstallment`.
3.  **Payroll Execution**: Generazione dei record `SalaryPayment` per tutto il personale con stato `Active`.
4.  **Maintenance Logging**: Registrazione delle spese di manutenzione ordinaria e straordinaria.
5.  **Revenue Recognition**: Contabilizzazione delle entrate (`Income`) maturate nel periodo.
6.  **Temporal Advance**: Aggiornamento della data `Campaign`. Il `LedgerService` esegue il flush delle transazioni da `PENDING` a `POSTED`.

### 4.2 Commissioning (Nuova Acquisizione)
Workflow per l'inserimento di un nuovo Asset:
1.  Inizializzazione entità `Ship` (Dati anagrafici, Classe).
2.  Definizione specifiche tecniche via `ShipDetails`.
3.  Attivazione contratto di finanziamento (`Mortgage`), se applicabile.
4.  Provisioning equipaggio iniziale (`Crew` onboarding).
5.  Registrazione costi di start-up (Provisioning Cost).

---

## 5. Roadmap Evolutiva

### Stato Attuale
Il sistema è in fase di produzione stabile (v1.x). L'architettura garantisce robustezza e integrità referenziale dei dati superiori alle soluzioni basate su fogli di calcolo.

### Interventi Prioritari
*   **Ottimizzazione Mobile**: Refactoring delle view finanziarie complesse per migliorare la fruibilità su viewport ridotti (tablet/mobile).
*   **Onboarding UX**: Implementazione di wizard guidati per configurazione iniziale Referee.
*   **Business Intelligence**: Sviluppo di dashboard analitiche avanzate per visualizzazione trend debitori e proiezioni di cassa a lungo termine.

---

## 6. Glossario Tecnico

*   **Imperial Date**: Standard di datazione formato `DDD-YYYY` (Giorno 001-365, Anno).
*   **BCMath**: Libreria PHP per calcoli matematici a precisione arbitraria, utilizzata per tutte le operazioni monetarie (Cr/MCr) per evitare errori di virgola mobile.
*   **Hex**: Coordinate spaziali esadecimali (formato `XXYY`) utilizzate per il mapping dei settori.
*   **Jump-Rating**: Indice intero che rappresenta la capacità di salto in Parsec dell'unità (J-1 a J-6).
