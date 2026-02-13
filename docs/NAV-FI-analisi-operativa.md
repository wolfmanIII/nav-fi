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

*   **Receiver**: È il conto dell'Asset che riceve i fondi.

### Fase 4: Navigazione Tattica e Viaggio (Navigation HUD)
Nav-Fi³ offre un sistema di navigazione asincrona per gestire il movimento tra sistemi:
1.  **Initiate Nav-Link**: Dalla pagina di dettaglio della rotta, attivando il link di navigazione si inizializza la sequenza di viaggio. L'interfaccia si trasforma in un HUD tattico.
2.  **Execute Transit**: Il transito tra waypoint (Engagement/Transit/Retrace) avviene senza ricaricare la pagina. 
3.  **Temporal Auto-Sync**: Ogni salto effettuato avanza automaticamente l'orologio della campagna di **7 giorni**, aggiornando istantaneamente la navbar di sistema.
4.  **Real Distance Log**: Le distanze visualizzate sono calcolate in parsec reali tra le coordinate dei sistemi, garantendo che le stime del carburante siano accurate per ogni tratta.

### 4. Procedure Operative (SOP)

#### SOP-FIN-01: Inizializzare la Finanza di una Nave
Esistono due modalità per gestire l'apertura dei conti finanziari dell'Asset.

##### A. Metodo Tattico (Manuale - Raccomandato per Audit)
1.  **Registrazione Asset**: Creare l'Asset (es. "Beowulf") dalla pagina Asset.
2.  **Creazione Conto**: Accedere alla pagina **Financial Accounts** (link in sidebar).
3.  **Nuovo Conto**: Cliccare su "New". 
    - Selezionare l'Asset "Beowulf".
    - Selezionare una banca esistente o scriverne una nuova in `Bank Name`.
    - Inserire il saldo iniziale (`credits`).
4.  **Conferma**: Il conto apparirà nella lista e sarà immediatamente utilizzabile per Mutui e Spese.

##### B. Metodo Rapido (Automatico - Smart Forms)
1.  **Azione Diretta**: Creare un **Mortgage**, un **Income** o un **Cost**.
2.  **Risoluzione Ibrida**: Nella sezione Ledger, selezionare l'Asset "Beowulf".
3.  **Creazione on-the-fly**: Invece di selezionare un conto esistente, compilare i campi "Bank // Alias" e "New Bank Name".
4.  **Auto-Settle**: Al salvataggio della form, il sistema creerà automaticamente il `FinancialAccount` e lo collegherà all'Asset.

> [!TIP]
> **Dove sono i miei soldi?**
> Se i crediti non appaiono nell'Asset, controlla il **Time Cursor**: se la data della transazione è nel futuro rispetto alla data sessione, i soldi sono `PENDING` (non ancora disponibili). Puoi verificare tutti i saldi nella pagina **Financial Account Index**.

---

## 5. Gestione HR e Salari
1.  **Setup**: Assegnare Crew all'Asset e configurare lo stipendio.
2.  **Temporal Trigger**: Il sistema calcola il pro-rata iniziale. I pagamenti successivi avvengono automaticamente ogni 28 giorni imperiali al variare della Data Sessione.

## 6. Chiusura Sessione (Temporal Advance & Sync)
Il Referee ha la responsabilità dell'integrità del log:
1.  **Avanzamento Data**: Quando la data della Campagna viene aggiornata, il sistema esegue la **Sincronizzazione Finanziaria**.
2.  **Post-Processing**: Transazioni `PENDING` diventano `POSTED`. In questa fase, i saldi di cassa degli Asset vengono aggiornati definitivamente.
3.  **Audit**: Eventuali correzioni a transazioni già postate devono essere effettuate tramite storni (`REVERSAL`), non modificando i record originali.

## 7. Glossario UI
*   **Cyan (Abyss)**: Operatività, Liste, Dati tecnici.
*   **Emerald**: Flussi di cassa positivi, Saldi attivi.
*   **Amber**: Dati in attesa (Pending), Scadenze imminenti.
*   **Red**: Debito, Spese, Annullamenti (Void).
