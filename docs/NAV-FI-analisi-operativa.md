# Nav-Fi³ Web – Guida Operativa (Referee & Player Manual)

> **Obiettivo**: Definire i flussi di lavoro standard per la gestione di campagne, navi ed economia.

## 1. Mappa delle Relazioni
Il sistema è gerarchico: l'**Utente** possiede una **Campagna**, la Campagna gestisce il tempo e ospita gli **Asset** (Navi). Ogni Asset ha il proprio libro mastro (**Ledger**) dove vengono registrati **Income** (Entrate) e **Cost** (Uscite).

## 2. Setup: Commissioning & Onboarding
1.  **Context Setup**: Assicurarsi che le tabelle di contesto (Leggi, Ruoli, Categorie) siano popolate tramite `app:context:import`.
2.  **Asset Registration**: Creare la nave definendo classe e specifiche tecniche (JSON).
3.  **Financial Setup**: Se la nave non è pagata interamente, attivare un **Mutuo** (`Mortgage`). La firma del mutuo genera ufficialmente il piano di ammortamento.

## 3. Ciclo Operativo di Missione

### Fase 1: Pianificazione (The Cube)
1.  Inizializzare una **Broker Session** nel sistema di destinazione.
2.  Generare e salvare le opportunità interessanti.
3.  **Accettazione Avanzata**: In fase di firma, selezionare l'Asset responsabile e impostare la data reale di missione (es. *Pickup Date* per cargo).

### Fase 2: Trading e Speculazione
1.  **Acquisto**: Accettare un'opportunità di tipo `TRADE` deduce immediatamente i fondi dall'Asset.
2.  **Inventory**: Le merci acquistate rimangono visibili nella lista "Unsold Cargo" dell'Asset.
3.  **Vendita/Liquidazione**: Al mercato di destinazione, registrare la vendita legandola all'acquisto originale. Il sistema calcola profitto/perdita e libera lo spazio in magazzino.

### Fase 3: Esecuzione e Spese
Registrare spese operative (Fuel, Berthing, Repairs) come `Cost`. Ogni operazione genera una transazione nel ledger che impatta il saldo basandosi sulla data corrente della campagna.

## 4. Gestione HR e Salari
1.  **Reclutamento**: Assegnare Crew all'Asset con stato `Active`.
2.  **Configurazione Paga**: Creare un record `Salary` per il membro dell'equipaggio.
3.  **First Payment**: Impostare la data del primo pagamento. Il sistema suggerisce un importo pro-rata basato sui giorni effettivamente lavorati fino a quella data.
4.  **Automazione**: Ogni 28 giorni dal primo pagamento, il sistema genererà automaticamente un prelievo per lo stipendio mensile.

## 5. Chiusura Sessione (Temporal Advance)
1.  Il Referee avanza la data della Campagna.
2.  **Sincronizzazione**: Al salvataggio, il sistema:
    *   Posta i pagamenti in sospeso (Income/Stipendi).
    *   Ricalcola i saldi degli Asset.
    *   Verifica eventuali stati di insolvenza ("Hard Deck Breach").

## 6. Glossario UI
*   **Cyan (Abyss)**: Operatività, Liste, Dati tecnici.
*   **Emerald**: Flussi di cassa positivi, Saldi attivi.
*   **Amber**: Dati in attesa (Pending), Scadenze imminenti.
*   **Red**: Debito, Spese, Annullamenti (Void).
