# Feasibility Study: The Cube (TravellerMap Contract Broker)

> **Ambito**: Design Memo. Specifica i requisiti e l'architettura del futuro modulo "Contract Broker", definendo i principi di design e i limiti operativi.

## Design Philosophy: "The Hard Deck" (Guardrails & Determinism)

The Cube è un generatore di contenuto che impatta l'economia di gioco. Per evitare di rompere il bilanciamento della campagna o introdurre complessità ingestibile, il sistema segue questi principi non negoziabili:

### 1. Determinismo come Default
Ogni `BrokerSession` possiede un **Seed** fisso.
- A parità di Sector Data e Seed, la sequenza di generazione è identica.
- Questo garantisce testabilità, debuggabilità e previene il "save scumming" disonesto, ma permette il "replay" legittimo di una sessione tecnica.

### 2. Economia Tabellare (Standardized Pricing)
I payout seguono la tabella ufficiale (**Traveller Core Update 2022**), definita nel file di configurazione (`the_cube.yaml`) per flessibilità.
*Nota: Il costo scala con la distanza ed è inteso per "singolo salto" (single jump).*

**Pricing Table (Parsecs):**
| Dist | High   | Middle | Basic | Low   | Freight |
|------|--------|--------|-------|-------|---------|
| 1    | 9,000  | 6,500  | 2,000 | 700   | 1,000   |
| 2    | 14,000 | 10,000 | 3,000 | 1,300 | 1,600   |
| 3    | 21,000 | 14,000 | 5,000 | 2,200 | 2,600   |
| 4    | 34,000 | 23,000 | 8,000 | 3,900 | 4,400   |
| 5    | 60,000 | 40,000 | 14,000| 7,200 | 8,500   |
| 6    | 210,000| 130,000| 55,000| 27,000| 32,000  |

- **Modifiers**:
    - Risk Factors (Amber/Red Zone) applicati come moltiplicatori.
    - Trade Codes (Rich, Industrial) applicati come bonus fissi.
- **Mail**: Tariffa flat 25,000 Cr per container (5 ton), non scala con la distanza (All-or-Nothing).
- **Charter**: Prezzo base per Tonnellata di scafo (es. 900 Cr/ton per 2 settimane).
- **Trade**: Generazione "Offerte Commerciali" (Speculative Trade) con moltiplicatori di acquisto/vendita basati sui Trade Code.
- **Contract**: Missioni generiche (Patron) con pagamento forfettario (es. 1000 - 5000 Cr) e bonus opzionali.
- **Service**: Prestazioni professionali (es. Riparazioni, Training, Medical) pagate a forfait o a giornata.
- **Salvage**: Vendita "Diritti di Recupero" (Tip-off su relitti) o gestione operazioni di salvataggio.
- **Prize**: Gestione legale e vendita di scafi catturati (Prize Court brokerage).
- **Graceful Degradation**: Se mancano dati UWP, fallback a Distanza 1 e Risk 1.0.

### 3. Conversione Pulita (No Surprises)
- Il passaggio `BrokerOpportunity` -> `Income` è una trasformazione rigorosa.
- L'entità `Income` generata è indistinguibile da una creata a mano: categorie corrette (FREIGHT, PASSENGERS, MAIL), status `Draft`, date coerenti.
- Nessun dato "speciale" o "magico" nel JSON che alteri il comportamento del Ledger.

## Strategia Tecnica: "Local-First & Session"

### 1. Gestione Dati Settore (Disciplinata)
I dati dei settori vengono scaricati e versionati localmente.
- **Path**: `/data/sectors/{SectorName}_{Year}-{Month}.tab` (Naming normalizzato).
- **Locking**: Meccanismo di lock per evitare download concorrenti dello stesso settore.
- **Fallback**: Se il parser fallisce su una riga (dato corrotto), la riga viene saltata e loggata, senza fermare l'import.
- **Shared Source**: Routes e Cube condividono lo stesso storage path e lo stesso parser, nessuna implementazione parallela.

### 2. Il concetto di "Broker Session"
La generazione è incapsulata in una sessione persistente.

**Flusso Operativo (MVP):**
1.  **Setup**: L'Arbitro crea sessione con seed (auto o manuale), *Settore*, *Origine*, *Range*.
2.  **Generate**: Il Cubo usa il seed per estrarre 5 candidate (ignorando i dati sporchi).
3.  **Selection**: L'Arbitro clicca "Keep" su quelle valide.
    - Le opportunità salvate vengono persistite su DB.
4.  **Convert**: "Accetta" un'opportunità salvata -> Visualizzazione Modal di Verifica.
5.  **Advanced Acceptance**:
    *   **Manual Timeline**: In fase di accettazione, l'utente può sovrascrivere la data di sessione con una data specifica di missione (es. *Pickup Date* per cargo, *Departure* per passeggeri).
    *   **Deadlines**: Supporto per scadenze contrattuali opzionali (`deadlineDay`/`Year`) per missioni di tipo `CONTRACT`.
    *   **Financial Execution**: Creazione `Income` (o `Cost` per Trade) con propagation automatica dei metadati. house

*Funzioni avanzate (Mission Board UI, Publish) rimandate post-MVP.*

## Architettura Software

### Entità
1.  **`BrokerSession`**:
    - `id`, `campaign_id`
    - `sector` (string), `originHex` (string), `range` (int)
    - `status` (Draft, Published, Archived)
    - `createdAt`
2.  **`BrokerOpportunity`**:
    - `id`, `session_id`
    - `summary` (string), `amount` (decimal)
    - `data` (JSON: details, cargo type, route complexity)
    - `status` (Proposed, Saved, Converted)

### Servizi
1.  **`TravellerMapSectorLookup` (Evolved)**:
    - Attualmente gestisce lookup effimeri.
    - Verrà esteso per gestire download/persistenza dei file `.tab` (sostituendo la cache semplice con storage su file versionati).
    - Servirà sia il modulo Route (lookup singolo) sia The Cube (batch generation).
2.  **`TheCubeEngine`**: Logica di business per generare opportunità casuali basate sui Trade Codes.
3.  **`BrokerService`**: Gestisce il flusso sessione (save opportunity, convert to income).



### Configurazione
Struttura del file `config/packages/the_cube.yaml`:
```yaml
parameters:
    # Path where TravellerMap sector data (.tab) will be stored
    app.cube.sector_storage_path: '%kernel.project_dir%/var/data/sectors'

    # Economic Constants (Traveller Core Update 2022)
    app.cube.economy:
        # --- FREIGHT (Merci) ---
        # Costo per Tonnellata (Cr/dt) in base alla distanza del salto.
        # Es: Spostare 1 tonnellata a 2 parsec paga 1600 Cr.
        freight_pricing:
            1: 1000
            2: 1600
            3: 2600
            4: 4400
            5: 8500
            6: 32000
        
        # --- PASSENGERS (Passeggeri) ---
        # Costo del biglietto per singola persona (Cr/pax).
        # High: Lusso, include bagaglio e stasi opzionale.
        # Middle: Standard, cabina condivisa o piccola.
        # Basic: Essenziale, spazi comuni (introdotto in Core 2022).
        # Low: Crio-stasi (rischio morte/malattia).
        passenger_pricing:
            1: { high: 9000,   middle: 6500,   basic: 2000,  low: 700 }
            2: { high: 14000,  middle: 10000,  basic: 3000,  low: 1300 }
            3: { high: 21000,  middle: 14000,  basic: 5000,  low: 2200 }
            4: { high: 34000,  middle: 23000,  basic: 8000,  low: 3900 }
            5: { high: 60000,  middle: 40000,  basic: 14000, low: 7200 }
            6: { high: 210000, middle: 130000, basic: 55000, low: 27000 }
        
        # --- MODIFIERS (Variabili Ambientali) ---
        modifiers:
            risk_amber: 1.5      # Moltiplicatore per zone pericolose (Amber Zone)
            risk_red: 2.0        # Moltiplicatore per zone interdette (Red Zone)
            trade_bonus: 500     # Bonus fisso (Cr) se i Trade Codes matchano (es. Rich -> Industrial)
        
        # --- CHARTER (Noleggio Nave) ---
        # Si paga l'intero scafo per un periodo.
        charter:
            base_rate: 900       # Cr per tonnellata di scafo (es. nave 200t = 180k Cr)
            min_weeks: 2         # Durata minima standard del contratto
            
        # --- MAIL (Posta Prioritaria) ---
        # Contenitori sicuri, pagamento fisso "tutto o niente".
        mail:
            flat_rate: 25000     # Pagamento fisso per colpo (Cr)
            tons_per_container: 5 # Spazio richiesto per container
            max_containers_dice: '1d6' # Formula dadi per quanti container generare
            
        # --- TRADE (Speculazione) ---
        # Moltiplicatori per generare prezzi di mercato "fluttuanti".
        trade:
            # Se compri in un sistema con questi codici...
            purchase_modifiers:
                In: 0.8          # Industrial: Merce costa l'80% del base (Sconto 20%)
                Ag: 0.7          # Agricultural: Cibo costa il 70% del base
            # Se vendi in un sistema con questi codici...
            sale_modifiers:
                Rich: 1.2        # Rich: Pagano il 120% (Bonus 20%)
                Poor: 1.3        # Poor: Pagano il 130%
                
        # --- CONTRACT (Missioni) ---
        # Missioni generiche "Patron" (es. Scorta, Trasporto VIP, Sorveglianza).
        contract:
            base_reward_min: 1000  # Paga base minima
            base_reward_max: 5000  # Paga base massima
            bonus_chance: 0.3      # Probabilità di avere un bonus (30%)
            bonus_multiplier: 0.5  # Bonus vale il 50% della reward base
            
        # --- SERVICE (Servizi) ---
        # Prestazioni professionali (es. Riparazioni, Training, Medical, Intrattenimento)
        service:
            # Similar to Contracts but usually shorter term or skill-based
            base_reward_min: 500
            base_reward_max: 2000
            types: ['Maintenance', 'Training', 'Medical', 'Entertainment', 'Legal']
            
        # --- SALVAGE (Recupero) ---
        # Brokeraggio di diritti di salvataggio (Tip-off).
        salvage:
            base_finder_fee_min: 2000    # Costo per comprare la "dritta" (o valore della reward)
            base_finder_fee_max: 10000
            
        # --- PRIZE (Prede) ---
        # Commissioni legali per la gestione di navi catturate.
        prize:
            legal_fee_percent: 0.10      # Il broker prende il 10% del valore stimato per legalizzare la preda
```

## Esempio Interfaccia (Mockup)
- **Top Bar**: "Session: Spinward Marches @ 1910 [3/10 Saved]"
- **Left Col (The Stream)**: Nuove card generate. Bottoni: [Relay to Storage] [Discard].
- **Right Col (Storage)**: Lista contratti salvati.

## Esempio di Payload Generato
```json
{
  "type": "FREIGHT",
  "title": "Cryo-Storage Units to Rhylanor",
  "amount": 45000,
  "destination": "Rhylanor (2716)",
  "distance": 3,
  "cargo": {
      "tons": 15,
      "type": "Machinery"
  },
  "flavour": "Generated by The Cube based on [In] Industrial trade flow."
}
```

## Prossimi Passi (POC)
1.  Estendere l'esistente `TravellerMapSectorLookup` per testare il download/persistenza di "Spinward Marches".
2.  Implementare la formula della distanza esagonale (riusando `RouteMathHelper`).
3.  Creare un comando CLI `app:cube:test <sector> <hex> <range>` per verificare l'output JSON.
