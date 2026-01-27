# Changelog

Tutte le modifiche notevoli a questo progetto saranno documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/it/1.0.0/),
e questo progetto aderisce al [Semantic Versioning](https://semver.org/lang/it/).

## [Unreleased]

### Sicurezza/Refactoring
- **Randomness Engine**: Sostituito `mt_srand()` (Global scope) con `Random\Engine\Xoshiro256StarStar` (Object scope). Garantisce generazione deterministica isolata per ogni sessione del *Cube* senza impattare lo stato globale del PHP.
- Refactoring di tutti i generatori procedurali (`Trade`, `Freight`, `Passenger`, `Mail`, `Contract`, `Narrative`) per accettare dependency injection del `Randomizer`.

## [1.2.0] - 2026-01-19

### Aggiunto
- **Modulo Gestione Salari**: Sistema completo per la gestione dei pagamenti dell'equipaggio.
  - Entità `Salary` e `SalaryPayment`.
  - Logica Pro-rata automatica con controller Stimulus.
  - Ciclo standard di 28 giorni (Impero).
  - Integrazione diretta con il Ledger Finanziario.
- **Branding Aggiornato**: Nuova terminologia "Command Deck" e "Open Systems".

### Modificato
- Refactoring UI Navbar e Footer per maggiore immersività.
- Migliorata visibilità tooltip nella pagina Asset Edit (hero section).
- Ottimizzazione performance Database (indici su Transaction e Ledger).

### Corretto
- Fix critico watermark PDF: rimosso placeholder `{{WATERMARK}}` da Mortgage, Cost e Asset PDF.
- Ripristinata animazione "spin" sulle icone radar e pulse effects.

## [1.1.0] - 2026-01-15

### Aggiunto
- File `VERSION` dedicato per tracciare la versione del progetto
- Guida completa "Financial Core User Guide" (`/guide/financial-core`)
  - Sezione "When to Use" per campagne vs oneshot
  - Documentazione "Date Management & Chronological Verification"
  - Workflow pratici e best practices
- Link alla guida nella homepage (sezione Financial Core)
- Link alla guida nella sidebar (sotto Financial Core → Credits & Debt)
- Badge "Chronological Verification" in tutte le pagine edit/index dei moduli finanziari
- Nota esplicativa "Mission can be an entire campaign or a single adventure" nei modal
- Colonna centrale "Signing location & Date" nelle tabelle firme dei contratti PDF
- Script di ottimizzazione PDF in `scripts/pdf-optimization/`
- README completo in italiano per script ottimizzazione PDF

### Modificato
- **Template PDF ottimizzati per stampa** (tutti i 15 template)
  - Sfondi bianchi puri (#ffffff)
  - Card con colori leggerissimi (#f9fafb)
  - Bordi sottili (1px) grigi chiari
  - Tabelle con header grigio chiaro (#f3f4f6)
  - Righe alternate per leggibilità
  - Rimozione gradienti (ship/SHEET)
  - **Risparmio inchiostro stimato: 80-85%**
- Badge "Chronological Verification" da stile info (cyan) a warning (amber)
- Allineamento tabelle firme: supporto flessibile per 2 o 3 colonne
  - 2 colonne: sinistra, destra
  - 3 colonne: sinistra, **centro**, destra
- Versioni template PDF aggiornate da v1.0 a v1.1
- Rimosso `overflow-hidden` dall'hero di ship/edit per tooltip visibili
- Label link campagne: "Review Clock" → "Mission Details & Clock"
- Documentazione `operations_flow.html.twig` e `operations_checklist.html.twig`
  - Rimossi riferimenti temporali ("now", "transitioned to")
  - Presentata soft validation come funzionalità nativa

### Corretto
- Tooltip pulsante PDF in ship/edit ora completamente visibile
- CSS tabelle firme con selettore intelligente `:nth-child(2):nth-last-child(2)`
- Attributi Stimulus preservati nei form (year-limit controller)

### Rimosso
- 36 file di backup (.backup, .bak2, .bak4) dai template PDF

## [1.0.0] - Data precedente

### Aggiunto
- Versione iniziale del progetto Nav-Fi
- Sistema di gestione navi, equipaggi, contratti e mutui
- Moduli Financial Core (Mortgage, Income, Costs, Annual Budget)
- Template PDF per contratti
- Sistema di autenticazione con MFA e Google OAuth
- Dashboard Command Deck
- Integrazione TravellerMap

---

## Legenda

- **Aggiunto** - per nuove funzionalità
- **Modificato** - per modifiche a funzionalità esistenti
- **Deprecato** - per funzionalità che saranno rimosse nelle prossime versioni
- **Rimosso** - per funzionalità rimosse
- **Corretto** - per bug fix
- **Sicurezza** - in caso di vulnerabilità
