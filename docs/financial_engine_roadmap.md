# Roadmap Motore Finanziario & Logica di Business

## 1. Definizione del Problema (Il "Diario di Bordo Passivo")
Attualmente l'applicazione registra i dati finanziari (Costi, Entrate) ma non li rende operativi.
- **Nessun "Portafoglio"**: Le entità `Ship` mancano di un saldo crediti (`credits`). Il denaro è teorico.
- **Nessuna Conseguenza**: Firmare un contratto di `Income` vantaggioso non incrementa i fondi disponibili.
- **Nessuna Verifica**: Gli utenti possono pagare Costi che non possono permettersi.

## 2. Architettura Proposta: Il Sistema Ledger Attivo

### A. Modifiche Schema Database

#### 1. Entità: `Ship`
Aggiungere un nuovo campo per tracciare gli asset liquidi correnti.
```php
#[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0.00'])]
private ?string $credits = '0.00';
```

#### 2. Entità: `Transaction` (Nuova)
Un registro immutabile semplificato per tracciare tutte le modifiche al portafoglio della Nave.
- `id`
- `ship_id`
- `amount` (+/- decimal)
- `description` (es. "Income: Chtr-101", "Acquisto Carburante")
- `date` (ImperialDate: giorno/anno)
- `related_entity_id` (polimorfico o riferimenti semplici)
- `related_entity_type` (Income, Cost, Mortgage)

### B. Core Services

#### 1. `LedgerService`
L'autorità centrale per la logica monetaria.
- `deposit(Ship $ship, Money $amount, string $source)`
- `withdraw(Ship $ship, Money $amount, string $reason)`
- `getBalance(Ship $ship): Money`
- `recalculateBalance(Ship $ship)`: Riproduce tutte le transazioni per garantire l'integrità.

### C. Integrazione Event-Driven (Modello Pending vs Posted)
Il sistema determina se una transazione è **CONFERMATA** (incide sul saldo) o **PIANIFICATA** (previsione) basandosi sulla Data della Sessione.

#### 1. Regola Aurea Temporale
- **Data Operazione <= Data Sessione**: Transazione **POSTED**. `Ship.credits` viene aggiornato immediatamente.
- **Data Operazione > Data Sessione**: Transazione **PENDING**. Nessun effetto sul saldo, visibile solo come budget.

#### 2. Eventi `Income` & `Cost`
- **Trigger**: Creazione/Modifica entità.
- **Azione**: `FinancialEventSubscriber` confronta la data dell'operazione con `Campaign.sessionDate`.
  - Se *Passato/Presente*: Chiama `LedgerService->deposit()` o `withdraw()`.
  - Se *Futuro*: Non fa nulla (o crea un record "Previsione" se necessario per UI avanzate).
  - **Nota**: Se un utente modifica una data spostandola dal Futuro al Passato, il sistema deve "realizzare" la transazione retroattivamente.

#### 3. Processor Avanzamento Tempo ("The Daily Batch")
Quando la Data Sessione avanza (es. l'Arbitro clicca "Next Day" o "End Week"):
- **Azione**: Il sistema cerca tutte le entità (`Cost`, `Income`, `Mortgage Installment`) con data compresa tra la *Vecchia Session Date* e la *Nuova Session Date*.
- **Logica**: Per ogni entità trovata, esegue la transazione finanziaria corrispondente ("Realizza" le spese pianificate).
- **Automazione**: Calcola ratei giornalieri (Stipendi, Life Support) e li addebita automaticamente.

### D. Miglioramenti Interfaccia Utente

#### 1. Sidebar / Header
- Mostrare **Crediti Correnti** (es. `Cr 4,500,000`) in evidenza accanto al nome della Nave.
- Codice colore: Verde (Positivo), Rosso (Negativo/Debito), Arancione (Fondi Bassi).

#### 2. Form dei Costi
- **Avviso**: "Attenzione: Fondi insufficienti. Saldo attuale: Cr 100. Costo: Cr 500."
- **Soft Block**: Permettere il debito ma mostrare un avviso visivo (stile Sci-Fi "CREDIT ALERT").

## 3. Piano di Implementazione (Prossimi Passi)

### Fase 1: Fondamenta
1.  [x] Creare migrazione per `Ship.credits` e tabella `Transaction`.
2.  [ ] Implementare `LedgerService` (Deposito/Prelievo base).
3.  [ ] Aggiungere visualizzazione `credits` al layout principale (`base.html.twig`).

### Fase 2: Automazione
4.  [ ] Creare classi `IncomeEvents` e `CostEvents`.
5.  [ ] Implementare `FinancialEventSubscriber`.
6.  [ ] Collegare il cambio di stato `Income` al subscriber.

### Fase 3: Funzionalità Avanzate
7.  [ ] Implementare bottone/azione pagamento rata `Mortgage`.
8.  [ ] Aggiungere vista "Storico Transazioni" per la Nave.
9.  [ ] Aggiungere "Cashflow Proiettato" (Entrate Future - Costi Futuri).

## 4. Note Tecniche
- **Precisione**: Usare sempre `bcmath` o una libreria Money per i calcoli. Mai float.
- **Concorrenza**: Usare locking database (pessimistico) quando si aggiorna `Ship.credits` per evitare race conditions se più utenti gestiscono una nave.
