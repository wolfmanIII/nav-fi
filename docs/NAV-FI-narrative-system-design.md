# NAV-FI Design Sistema Narrativo 2.0 (The Cube)

## 1. Obiettivo
Espandere il **NarrativeService** per generare contratti ricchi di atmosfera e varietà.
**Focus Critico**: Gestione ibrida dei Patron (Compagnie registrate vs NPC non registrati) e filtro geografico (Settore).

## 2. Nuova Struttura Dati (`the_cube.yaml`)
### 2.1 Nuove Dimensioni
-   **Locations (Luoghi)**: Ambientazioni per incontri (es. "Bar malfamato", "Yacht in orbita").
-   **Time Constraints (Urgenza)**: Motivi di fretta (es. "Ispezione imminente").
-   **Opposition**: Avversari (es. "Pirati", "Dogana").

### 2.2 Template "Mad-Libs"
Generazione frasi dinamiche:
`"[Patron] necessita [Action] su [Target] presso [Location]. [TimeConstraint]."`

## 3. Strategia Patrons Ibrida & Settoriale

### 3.1 Il Problema "Company ID"
L'utente ha segnalato che nel DB `company_id` su `Income` potrebbe essere `NOT NULL`.
**Soluzione Tecnica**:
1.  Generare una **Migrazione** per garantire esplicitamente che `company_id` sia `NULLABLE`.
2.  Aggiungere il campo `patron_alias` (string, nullable) su `Income`.
3.  **Logica**:
    -   Se si seleziona una Company: `company_id` valorizzato, `patron_alias` azzerato (NULL).
    -   Se si usa un Patron NPC: `company_id` è NULL, `patron_alias` contiene il nome (es. "Shady Fixer").

### 3.2 Filtro Geografico (Settore/Sottosettore)
Le Compagnie operano in zone specifiche.
**Modifica DB**: Aggiungere campi `sector` e `subsector` (string, null) all'entità `Company`.
**Logica di Selezione**:
1.  Il `NarrativeService` riceve le coordinate della Sessione (Settore/Esagono).
2.  Cerca nel DB le `Company` che operano in quel Settore.
3.  Se trovate, le usa con priorità (50%).
4.  Se non trovate (o per varietà), usa i Patron generici dallo YAML.

## 4. Implementazione Tecnica

### 4.1 Schema Update
-   `Company`: `+sector (string)`, `+subsector (string)`.
-   `Income`: `CHANGE company_id` to nullable (se necessario), `+patron_alias (string)`.

### 4.2 Aggiornamento Logic (`NarrativeService`)
-   Iniezione `CompanyRepository`.
-   Metodo `getAvailablePatrons(string $sector)`: Ritorna mix di Entity (Company) e Stringhe (YAML).

### 4.3 Aggiornamento YAML
-   Popolare liste `locations`, `opposition`, `time_constraints`.

## 5. UI Integration
-   **Accettazione Contratto**:
    -   Spiegare chiaramente all'utente che se non seleziona una Compagnia (ma usa il Patron NPC), il contratto sarà "Non Registrato" (nessun link a Company).
    -   Il campo `patron_alias` verrà mostrato nelle viste di dettaglio dell'Income.
