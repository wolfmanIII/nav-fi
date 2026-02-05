# Analisi Approfondita del Sistema: Nav-Fi Web

## 1. Visione d'Insieme
**Nav-Fi Web** (precedentemente ELaRA / Captain Log) è un Tactical Operating System (TOS) sviluppato in **Symfony 7.4** per la gestione di campagne GDR nel sistema **Traveller**. Il focus principale è la simulazione economica, logistica e tattica di una flotta stellare.

## 2. Architettura Tecnica Core
Il cuore del sistema è un motore finanziario **double-entry** e **time-aware**.

### 2.1 Financial Core (The Ledger)
*   **LedgerService**: Gestisce l'integrità del mastro. Ogni transazione è immutabile; le correzioni avvengono tramite storni (`REVERSAL`).
*   **Temporal Immutability**: Il sistema utilizza un "Time Cursor" (data sessione della Campagna). Le transazioni sono `PENDING` se future, `POSTED` se correnti/passate.
*   **BCMath**: Tutti i calcoli monetari (Cr/MCr) utilizzano la libreria BCMath per prevenire errori di floating point.
*   **Hybrid Entity Resolution**: Permette la creazione "on-the-fly" di banche e venditori durante l'inserimento di spese/entrate, riducendo l'attrito durante la sessione.

### 2.2 Automatismi e Eventi
*   **FinancialEventSubscriber**: Un orchestratore basato su eventi Doctrine che sincronizza automaticamente le entità di business (`Income`, `Cost`, `Mortgage`) con il Ledger tecnico.
*   **FinancialAutomationService**: Gestisce la generazione ciclica di rate del mutuo e stipendi (ogni 28 giorni imperiali), inclusa la logica pro-rata per i nuovi assunti.

### 2.3 Motore "The Cube" (Broker Engine)
*   **Generazione Deterministica**: Utilizza un sistema di Seed per generare opportunità di carico, passeggeri e contratti in modo coerente e riproducibile.
*   **Narrative Engine**: Genera descrizioni immersive per i patron e le missioni.
*   **Contract System 2.0 (In Sviluppo)**: Una proposta di overhaul per introdurre ricompense scalabili, rischi dinamici e "colpi di scena" narrativi.

### 2.4 Navigazione e Rotte
*   **Astrografia Digitale**: Integrazione con l'API di TravellerMap per dati UWP e coordinate stellari.
*   **Pathfinding**: Algoritmi A* e ottimizzazione TSP per il calcolo di rotte commerciali e salti multipli.

## 3. Stack Tecnologico
*   **Backend**: PHP 8.3, Symfony 7.4, PostgreSQL 16/18.
*   **Frontend**: Tailwind CSS 4 + DaisyUI (Tema "Abyss"), Stimulus.js (per logiche client-side come i locali temporali e pro-rata).
*   **Documentazione**: Generazione PDF tramite **Gotenberg** (Chromium-based), ottimizzata per il risparmio di inchiostro (80-85%).
*   **Sicurezza**: Supporto nativo per MFA (TOTP) e Google OAuth.

## 4. Stato del Progetto e Roadmap
Il progetto ha recentemente consolidato il modulo stipendi e ottimizzato la generazione PDF. La prossima fase sembra focalizzata sull'espansione del sistema dei contratti ("The Cube 2.0") per aumentare il realismo economico e la profondità narrativa.

## 5. Conclusione
Nav-Fi non è un semplice database di navi, ma un simulatore economico rigoroso che isola i dati per utente (Multi-tenancy) e garantisce una "audit trail" completa di ogni centesimo speso nello spazio profondo.
