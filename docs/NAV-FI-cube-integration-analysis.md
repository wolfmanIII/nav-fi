# Analisi Integrazione Finanziaria NAV-FI (The Cube)

## 1. Sintesi Esecutiva
Questo documento analizza l'integrazione tra **The Cube** (Generazione Opportunità) e il **Sistema Finanziario** (Gestione Asset).
L'obiettivo è convertire in modo fluido le *Broker Opportunities* (effimere) in entità finanziarie reali (*Income*, *Cost*, *Transaction*) collegate a specifici Asset (Navi/Compagnie), con focus particolare sul flusso multistadio del **Commercio (Trade)**.

## 2. Panoramica Entità Core
-   **BrokerOpportunity**: Contratto potenziale transitorio. Status: `PROPOSED` -> `CONVERTED`.
-   **Asset**: Il contenitore finanziario (es. Astronave). Traccia i `credits`.
-   **Income**: Ricavo realizzato. Collegato a un Asset. Sottotipi: `Freight`, `Passenger`, `Mail`, `Trade` (Vendita).
-   **Cost**: Spesa realizzata. Collegata a un Asset. Sottotipi: `Fuel`, `Repairs`, `Trade` (Acquisto).
-   **Transaction**: Voce di libro mastro che registra il delta effettivo di crediti.

## 3. Flusso Contratti Standard (Freight, Passenger, Mail, Mission)
Queste opportunità rappresentano lavori specifici con un pagamento garantito (o condizionale) al completamento.

### 3.1 Logica di Conversione
**Trigger**: L'utente accetta il contratto su `show.html.twig`.
**Input**: `BrokerOpportunity` + `Asset Target`.

1.  **Creazione**: Crea nuova entità `Income`.
    -   `status`: `Signed` (Attivo ma non ancora pagato) o `Draft`.
    -   `asset_id`: Asset selezionato.
    -   `amount`: Dall'Opportunità.
    -   `details`: Mappati dai dati dell'Opportunità (vedi Tabella di Mappatura).
2.  **Cambio Stato**: Imposta lo status dell'Opportunità a `CONVERTED`.
3.  **Persistenza**: `Income` salvato nel DB.

### 3.2 Impatto Finanziario
-   **Immediato**: Nessun cambio di crediti.
-   **Al Completamento**: L'utente segna l'Income come "Pagato" -> Crea una `Transaction` (+Credits) -> Aggiorna il Saldo Asset.

## 4. Flusso Commercio Speculativo (La "Rotta Commerciale")
Il commercio è unico perché implica un **Flusso in Uscita** iniziale (Acquisto) e un **Flusso in Entrata** ritardato (Vendita), con le merci che risiedono nel carico della nave nel frattempo.

### 4.1 Il Problema
Convertire un'opportunità di Trade direttamente in `Income` è errato perché:
1.  Dobbiamo prima pagare per acquisire le merci.
2.  L'importo dell'`Income` (Vendita) è speculativo/ignoto finché non raggiungiamo il mercato di destinazione.

### 4.2 Soluzione Proposta: Ciclo di Vita "Trade Run"

#### Fase 1: Acquisizione (Il Costo)
**Trigger**: L'utente accetta l'Opportunità di Trade.
**Azione**:
1.  **Validazione**: Verifica se l'Asset ha crediti sufficienti per il `buy_price`.
2.  **Creazione**: Crea entità `Cost`.
    -   `category`: `Trade / Cargo Purchase`.
    -   `amount`: `buy_price`.
    -   `details`: `{ resource: "Elettronica", quantity: 5, target_market: "Regina" }`.
    -   `status`: `Paid` (Deduzione immediata).
3.  **Transazione**: Crea `Transaction` (-Credits). Aggiorna Saldo Asset.
4.  **Inventario**: (Opzionale/Futuro) Aggiunge "Elettronica" al manifesto dell'Asset.

#### Fase 2: Trasporto (Il Carico)
Le merci sono effettivamente "A Bordo".
-   *Rappresentazione UI*: Il `Cost` è visibile nel libro mastro. L'Opportunità è `CONVERTED`.
-   *Tracker*: Potremmo aver bisogno di un'entità `TradeRun` o taggare il `Cost` con uno status "In Transit".

#### Fase 3: Liquidazione (Il Reddito)
**Trigger**: L'utente arriva a Destinazione e vende il carico.
**Azione**:
1.  L'utente seleziona l'elemento Trade "In Transito" (o crea nuovo Income collegato).
2.  **Creazione**: Crea entità `Income`.
    -   `category`: `Trade / Speculative Sale`.
    -   `amount`: Prezzo realizzato (tirato a destinazione).
    -   `reference_cost`: Link al Costo di Acquisto originale (per calcolo Profitto/Perdita).
3.  **Transazione**: Crea `Transaction` (+Credits). Aggiorna Saldo Asset.

## 5. Piano Tecnico di Implementazione

### 5.1 Tabella Mappatura Dati

| Tipo Opportunità | Entità Target | Mappatura Campi Chiave | Effetti Collaterali |
| :--- | :--- | :--- | :--- |
| **FREIGHT** | `Income` -> `FreightDetails` | `tons` -> `cargoTons`, `dest` -> `destination` | Nessuno |
| **PASSENGER** | `Income` -> `PassengerDetails` | `pax` -> `passengers`, `class` -> `class` | Nessuno |
| **MAIL** | `Income` -> `MailDetails` | `tons` -> `tons`, `containers` -> `cnt` | Nessuno |
| **CONTRACT** | `Income` -> `ContractDetails` | `summary` -> `description`, `patron` -> `patron` | Nessuno |
| **TRADE** | **COST** (Acquisto) | `buy_price` -> `amount`, `resource` -> `note` | Deduce Crediti immediatamente |

### 5.2 Modifiche Richieste
1.  **BrokerService**: Aggiungere `convertOpportunity(Opportunity, Asset)`.
    -   Deve distinguere la logica basata sul `type`.
    -   Per `TRADE`: Chiama `createTradePurchase`.
    -   Per Altri: Chiama `createServiceIncome`.
2.  **UI**:
    -   Aggiungere modale "Seleziona Asset" all'Accettazione.
    -   Per Accettazione Trade: Avviso "Questo dedurrà X Crediti. Procedere?".
3.  **Entità**:
    -   Assicurare che `Income` e `Cost` possano collegarsi a `BrokerOpportunity` (opzionale, per storico) o salvare la `Signature` è sufficiente.

## 6. Casi Limite & Rischi
-   **Fondi Insufficienti**: L'utente accetta Trade ma ha 0 Cr. -> Il sistema deve bloccare o permettere "Debito" (Override manuale).
-   **Mismatch Tipo Asset**: L'Asset è una "Base" (Stazione) ma accetta "Freight"? Permettere per ora, ma la UI dovrebbe avvisare.
-   **Accettazione Parziale**: Splittare un contratto? Fuori scope per la V1.
