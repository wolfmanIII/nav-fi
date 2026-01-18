# Gestione dei Salari (Semplificata)

## Panoramica
La funzionalità "Gestione Salari" mira ad automatizzare il processo di pagamento per i membri dell'equipaggio attivi su un asset. Si integra con il sistema di ledger finanziario esistente per garantire compensi coerenti e basati su regole precise.

## Regole di Business

### 1. Eleggibilità e Struttura
- Un Salario può essere creato solo per un **Membro dell'Equipaggio** (Crew Member) che ha uno stato `Active` su un **Asset**.
- Il record del Salario è collegato direttamente solo al **Membro dell'Equipaggio**.
- L'**Asset** e la **Campagna** sono desumibili tramite la relazione esistente tra `Crew` e `Asset` (e tra `Asset` e `Campaign`). Pertanto, non verranno salvati come campi ridondanti nella tabella `Salary`.

#### Dettagli sulla Relazione Crew-Salary (1 a Molti)
È stata scelta una relazione **1 a Molti** (un Membro può avere più record di Salario) per le seguenti ragioni critiche:
1. **Storicità e Aumenti**: Permette di "chiudere" un contratto salariale e aprirne uno nuovo in caso di aumento o bonus ricorrente, senza perdere traccia di quanto pagato in precedenza con le vecchie tariffe.
2. **Cambi di Ruolo o Nave**: Se un membro viene riassegnato a un nuovo Asset o cambia specializzazione (Asset Role), è possibile creare un nuovo record salariale specifico per quella fase della sua carriera.
3. **Integrità Finanziaria**: Poiché i pagamenti nel ledger sono legati al record salariale, avere record distinti garantisce che la ricostruzione storica dei costi sia sempre accurata e immutabile.

### 2. Integrazione Finanziaria
- I salari sono misurati in **Crediti Imperiali (Cr)**.
- Ogni pagamento deve essere registrato automaticamente nel **Ledger Finanziario** (ledger_transaction).
- I pagamenti sono trattati come **Prelievi** (addebiti) dal saldo crediti dell'Asset associato al membro dell'equipaggio.

### 3. Regole Cronologiche e Ciclo Fisso
- **Ciclo di Paga Standard**: In linea con l'analisi tecnica, il ciclo è fissato a **28 giorni** (standard imperiale). Il campo `paydayCycle` è stato rimosso per garantire uniformità e semplicità di calcolo.
- **Data del Primo Pagamento**: Selezionata manualmente dall'utente (First Payment Date).
- **Calcolo del Pro-rata (Primo Stipendio)**:
    - Il sistema suggerisce automaticamente l'importo pro-rata nel form di creazione/modifica.
    - **Formula**: `(Salario Mensile / 28) * (Giorni totali trascorsi dall'attivazione del Crew alla data di primo pagamento)`.
    - Esempio: Se un membro inizia al giorno 10 e la prima paga è al giorno 28:
        - Giorni lavorati: (28 - 10) = 18 giorni.
        - Primo stipendio suggerito: `(Salario / 28) * 18`.
    - I pagamenti successivi saranno standard ogni 28 giorni per l'importo mensile intero.

## Architettura Tecnica

### 1. Entità: `Salary`
- **id**: Chiave Primaria (REF: SAL-id).
- **crew**: Relazione ManyToOne verso `Crew`.
- **amount**: DECIMAL (15, 2) - Salario mensile.
- **firstPaymentDay / firstPaymentYear**: Data di inizio del ciclo di pagamenti.
- **status**: Stringa (Active, Suspended, Completed).

### 2. Entità: `SalaryPayment`
Gestisce lo storico dei pagamenti effettuati.
- **id**: Chiave Primaria.
- **salary**: Relazione ManyToOne verso `Salary`.
- **paymentDay / paymentYear**: Data effettiva del pagamento.
- **amount**: DECIMAL (15, 2).
- **transaction**: Gestito via `relatedEntityId` nel Ledger (Transaction).

## Esperienza Utente & Uniformità
Il modulo segue i rigorosi standard UI/UX del progetto Nav-Fi:
- **Index View (Cyan Theme)**: Tutte le liste finanziarie usano il tema Primary/Cyan per le azioni e l'Hero, garantendo coerenza tra i vari ledger.
- **REF Badge**: Ogni record è identificato dal badge `REF: SAL-{{ id }}`.
- **Cascading Filters**: Il form di creazione permette di filtrare i membri dell'equipaggio selezionando prima la Campagna e poi l'Asset.
- **Sincronizzazione Icone**: L'icona del modulo (`credits.html.twig`) è sincronizzata tra Sidebar, Hero e pulsanti d'azione (Add/Empty State).
- **Automation UI**: Recalcolo in tempo reale del suggerimento pro-rata tramite Stimulus controller dedicato (`salary_controller.js`).
