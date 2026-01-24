# NAV-FI Analisi Popolamento Dettagli Finanziari

## Obiettivo
Popolare i campi "morti" (NULL) delle entità `Income*Details` per arricchire l'immersione e la profondità simulativa, senza sovraccaricare l'utente.

## 1. Analisi Campi per Tipologia

### A. CONTRACT (`IncomeContractDetails`)
Campi chiave attualmente non utilizzati:
-   `successCondition`: Condizioni di vittoria (es. "Riporto illeso del VIP").
-   `failureTerms`: Penali (es. "Restituzione 50% anticipo", "Reputation Hit").
-   `confidentialityLevel`: Livello di segretezza (es. "Nessuna", "NDA Standard", "Kill-on-sight").
-   `expensesPolicy`: Rimborso spese (es. "All inclusive", "Carburante escluso").

**Strategia**:
-   Usare il **Tier** (Routine/Hazardous/Black Ops) per determinare `confidentialityLevel` e `failureTerms`.
-   Generare `successCondition` basato sul template narrativo.

### B. TRADE (`IncomeTradeDetails`)
Campi chiave non usati:
-   `transferPoint`: Dove avviene lo scambio fisico (es. "Molo 42", "Coordinate nello spazio profondo").
-   `transferCondition`: Condizioni merce (es. "FOB - Free On Board", "CIF - Cost, Insurance & Freight").
-   `warrantyText` / `asIsOrWarranty`: Garanzia (Visto e Piaciuto vs Garanzia 30 giorni).

**Strategia**:
-   Usare la **Legality** o il **Risk** per determinare la garanzia ("As Is" per merce illegale).
-   `transferPoint` generato da `NarrativeService::generateLocation()`.

### C. FREIGHT (`IncomeFreightDetails`)
Campi chiave non usati:
-   `liabilityLimit`: Limite responsabilità in caso di danno.
-   `cancellationTerms`: Penale se non si consegna in tempo.

**Strategia**:
-   Calcolare `liabilityLimit` come % del valore stimato del carico (es. 150% valore base).

### D. PASSENGER (`IncomePassengersDetails`)
Campi chiave non usati:
-   `baggageAllowance`: Franchigia bagaglio (es. "100kg", "Hand luggage only").
-   `refundChangePolicy`: Politica cancellazione (es. "Non-refundable").

**Strategia**:
-   Legare alla **Classe** (High/Middle/Low).
    -   High: "250kg + Pet", "Full Refund".
    -   Low: "15kg strict", "No Refund".

### E. MAIL (`IncomeMailDetails`)
Campi chiave non usati:
-   `securityLevel`: (es. "Diplomatic", "Standard", "Bulk").
-   `sealCodes`: Codici sigillo.

**Strategia**:
-   `securityLevel` basato sul valore/tonnellata.
-   `sealCodes` generati random (UUID breve o Hex).

## 2. Piano di Implementazione

### Aggiornamento `NarrativeService`
Aggiungere metodi per generare termini legali/contrattuali:
-   `generateLegalTerms(string $type, string $tier): array`
-   Restituisce un array con chiavi come `cancellation`, `liability`, `confidentiality`.

### Aggiornamento `OpportunityConverter`
Mappare i nuovi dati generati dal Cube (o calcolati al volo) nelle entità Details.

**Esempio Mapping PROPOSTO:**

```php
// In OpportunityConverter::createPassengersIncome
$details->setBaggageAllowance($opp->details['class'] === 'High' ? '500kg Personal Cargo' : '40kg Standard');
$details->setRefundChangePolicy($opp->details['class'] === 'High' ? 'Flexible' : 'Strict Non-Refundable');
```

## 3. Domanda per l'Utente
Vogliamo che questi dati siano generati casualmente ma deterministicamente (basati sul seed) nel `TheCubeEngine`, oppure calcolati "al volo" nel `OpportunityConverter` al momento dell'accettazione?
*Consiglio*: Calcolarli nel `OpportunityConverter` basandosi sul contesto (Tier, Classe, Tipo) è più semplice e sufficiente per il "colore", senza dover salvare tutto nel JSON dell'opportunità prima ancora che venga accettata.
