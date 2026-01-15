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

### C. Integrazione Event-Driven
Sostituire l'accoppiamento diretto con Eventi Symfony per mantenere il sistema modulare.

#### 1. Eventi `Income`
- **Trigger**: Quando lo stato di `Income` passa a `SIGNED`.
- **Azione**: L'EventSubscriber chiama `LedgerService->deposit()`.
- **Logica**: Aggiunge l'`advance_payment` (se presente) o l'intero importo ai crediti della Nave.

#### 2. Eventi `Cost`
- **Trigger**: Quando un `Cost` viene creato/aggiornato.
- **Azione**: Se `paymentDate` <= `DataSessioneCorrente`, l'EventSubscriber chiama `LedgerService->withdraw()`.
- **Logica**: I costi vengono detratti immediatamente se sono nel "passato" rispetto alla sessione.

#### 3. Eventi `Mortgage`
- **Trigger**: Nuova azione specifica "Paga Rata".
- **Azione**: `LedgerService->withdraw()` -> `Mortgage->addInstallment()`.

### D. Miglioramenti Interfaccia Utente

#### 1. Sidebar / Header
- Mostrare **Crediti Correnti** (es. `Cr 4,500,000`) in evidenza accanto al nome della Nave.
- Codice colore: Verde (Positivo), Rosso (Negativo/Debito), Arancione (Fondi Bassi).

#### 2. Form dei Costi
- **Avviso**: "Attenzione: Fondi insufficienti. Saldo attuale: Cr 100. Costo: Cr 500."
- **Soft Block**: Permettere il debito ma mostrare un avviso visivo (stile Sci-Fi "CREDIT ALERT").

## 3. Piano di Implementazione (Prossimi Passi)

### Fase 1: Fondamenta
1.  [ ] Creare migrazione per `Ship.credits` e tabella `Transaction`.
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
