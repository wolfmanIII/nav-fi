# Analisi del Financial Core di Nav-Fi³

> **Ambito**: Design Memo e Logica Interna del Ledger.

Questo documento descrive il funzionamento del **Ledger Service** (Servizio Mastro), il vero motore differenziante di Nav-Fi³.

## 1. Filosofia: "Event-Driven Ledger"

A differenza di molti gestionali GDR che salvano solo "Crediti Totali", Nav-Fi³ separa l'**Intento** dalla **Realtà di Cassa**.

*   **Intento**: "Ho firmato un contratto per 1M Cr da incassare tra 2 settimane".
*   **Realtà**: Oggi in cassa ho 0. Tra 2 settimane avrò 1M.

Il sistema gestisce questa dicotomia tramite il concetto di **Time Cursor**.

## 2. Il Ledger (Entità `Transaction`)

Ogni `Transaction` è una riga immutabile (logicamente).

| Campo | Descrizione |
|Presso|---|
| `amount` | Valore decimale (BCMath). + Depositi, - Prelievi. |
| `status` | `PENDING` (Futuro), `POSTED` (Eseguito), `VOID` (Annullato). |
| `session_date` | Giorno/Anno Imperiale in cui l'evento accade. |
| `related_entity` | Link all'oggetto sorgente (Income ID #42, Cost ID #101). |

## 3. Logica "Time Cursor" (Viaggio nel Tempo)

Visto che in un GDR il tempo è fluido (si può tornare indietro per correggere un errore narrativo), il Ledger deve adattarsi.

### La Regola "Effective"
Una transazione influenza il saldo `Asset.credits` **SE E SOLO SE**:
1.  Non è `VOID`.
2.  `Transaction.date <= Campaign.currentDate`.

### Sincronizzazione (`LedgerService::processCampaignSync`)
Quando il Referee cambia la data della Campagna:

*   **Avanti nel Tempo (Time Advance)**:
    *   Il cursore si sposta in avanti.
    *   Il sistema cerca transazioni `PENDING` che sono state "superate" dal cursore.
    *   Le trasforma in `POSTED`.
    *   Somma l'importo al saldo dell'Asset.
    
*   **Indietro nel Tempo (Undo / Correction)**:
    *   Il cursore si sposta indietro.
    *   Il sistema cerca transazioni `POSTED` che ora si trovano nel "futuro".
    *   Le riporta a `PENDING`.
    *   Sottrae l'importo dal saldo (Storno).

## 4. Strategia di Reversal (Soft Delete)

Per garantire l'audit, non modifichiamo mai l'importo di una transazione già postata.
Se un utente modifica un Costo da 1000 Cr a 1200 Cr:

1.  Il sistema individua la transazione originale (+1000 Cr uscita).
2.  Crea una transazione di **Reversal**: -1000 Cr (ingresso fittizio per annullare l'uscita).
3.  Crea la nuova transazione corretta: +1200 Cr uscita.

Il saldo finale è corretto (-1200), ma lo storico mostra la correzione.

## 5. Entità Supportate

### A. Mortgage (Mutuo)
Le rate sono eventi manuali (`MortgageInstallment`). Quando registrate, creano un prelievo immediato.

### B. Income (Entrate)
Ciclo complesso:
*   **Deposito**: Transazione immediata (o alla signing date).
*   **Saldo**: Transazione `PENDING` programmata per la `paymentDate`.
*   **Penali**: (Roadmap) Possibilità di ridurre il saldo se la data di consegna effettiva > data promessa.

### C. Salary (Stipendi)
Il sistema calcola cicli di 28 giorni dall'assunzione. Genera transazioni periodiche.

## 6. Performance & Ottimizzazione

*   **Indici**: `idx_transaction_sync` è cruciale per la velocità del time travel.
*   **Chiusura Annuale (Fiscal Year Close)**: Funzionalità per "congelare" le transazioni di anni passati in una tabella storica (`transaction_archive`), lasciando nel ledger attivo solo un "Saldo Iniziale Anno X".

## 7. UX & "Voice of the Machine"

L'interfaccia comunica lo stato del ledger all'utente:
*   **"Temporal Reconciliation"**: Sync avvenuto con successo.
*   **"Ledger Integrity Event"**: Rilevata modifica manuale o reversal.
*   **"Causality Locked"**: Transazioni future in attesa.
*   **"Hard Deck Breach"**: Saldo negativo!
