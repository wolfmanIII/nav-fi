# Nav-Fi³ Web – Guida Operativa (Referee & Player Manual)

> **Obiettivo**: Definire i flussi di lavoro standard per la gestione di campagne, navi ed economia.

## 1. Mappa delle Relazioni
Il sistema è gerarchico: l'**Utente** possiede una **Campagna**, la Campagna gestisce il tempo e ospita gli **Asset** (Navi). Ogni Asset ha il proprio libro mastro (**Ledger**) dove vengono registrati **Income** (Entrate) e **Cost** (Uscite).

## 2. Setup: Commissioning & Onboarding
1.  **Context Setup**: Assicurarsi che le tabelle di contesto (Leggi, Ruoli, Categorie) siano popolate tramite `app:context:import`.
2.  **Asset Registration**: Creare la nave definendo classe e specifiche tecniche (JSON).
3.  **Financial Setup**: Se la nave non è pagata interamente, attivare un **Mutuo** (`Mortgage`). La firma del mutuo genera ufficialmente il piano di ammortamento.

## 3. Ciclo Operativo di Missione

### Fase 1: Pianificazione & Context (Smart Forms)
Nav-Fi³ utilizza **Smart Forms** che si adattano al contesto. Quando si registra un'operazione:
1.  **Asset Selection**: Selezionare prima l'Asset (Nave). I dettagli finanziari e i limiti temporali verranno caricati dinamicamente.
2.  **Ledger Linking**: Se l'Asset ha già un conto collegato, il sistema lo blocca automaticamente per prevenire errori. Se è necessario un nuovo conto, appariranno i campi per la creazione rapida.

### Fase 2: Esecuzione e Spese (Cost & Logistics)
1.  **Vendor Management**: Durante la registrazione di un `Cost`, è possibile selezionare un Vendor (Compagnia) esistente o inserirne uno nuovo testualmente. In caso di nuovo inserimento, il sistema lo registrerà come entità permanente.
2.  **Trade & Inventory**: Gli acquisti di merci (`TRADE`) richiedono un link a un `FinancialAccount` attivo. La merce rimane in inventario finché non viene liquidata con un `Income` corrispondente.

### Fase 3: Incassi e Contratti (Income)
La registrazione di un `Income` segue la stessa logica XOR:
*   **Payer**: Può essere un Patron registrato o un nuovo Alias.
*   **Receiver**: È il conto dell'Asset che riceve i fondi.

## 4. Gestione HR e Salari
1.  **Setup**: Assegnare Crew all'Asset e configurare lo stipendio.
2.  **Temporal Trigger**: Il sistema calcola il pro-rata iniziale. I pagamenti successivi avvengono automaticamente ogni 28 giorni imperiali al variare della Data Sessione.

## 5. Chiusura Sessione (Temporal Advance & Sync)
Il Referee ha la responsabilità dell'integrità del log:
1.  **Avanzamento Data**: Quando la data della Campagna viene aggiornata, il sistema esegue la **Sincronizzazione Finanziaria**.
2.  **Post-Processing**: Transazioni `PENDING` diventano `POSTED`. In questa fase, i saldi di cassa degli Asset vengono aggiornati definitivamente.
3.  **Audit**: Eventuali correzioni a transazioni già postate devono essere effettuate tramite storni (`REVERSAL`), non modificando i record originali.## 6. Glossario UI
*   **Cyan (Abyss)**: Operatività, Liste, Dati tecnici.
*   **Emerald**: Flussi di cassa positivi, Saldi attivi.
*   **Amber**: Dati in attesa (Pending), Scadenze imminenti.
*   **Red**: Debito, Spese, Annullamenti (Void).
