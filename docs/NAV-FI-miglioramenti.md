# Nav-Fi Web – Possibili miglioramenti

Documento di analisi tecnica con aree di miglioramento e funzionalità potenziali, basato sullo stato attuale del progetto.

## Priorità alte (impatto su sicurezza e coerenza)

1) **Ownership Campaign [DONE]**
   - Stato: aggiunto `Campaign.user`, filtro per user in repository e controller.

2) **Validazione AnnualBudget (start/end) [DONE]**
   - Stato: le query usano la funzione `parseDayYearFilter` in `AnnualBudgetRepository` e applicano `start >=` / `end <=` prima di restituire i risultati.
   - Beneficio: il filtro non restituisce più budget con range invertito, mantenendo il vincolo logico nel percorso di ricerca.

3) **Centralizzazione logica filtri/paginazione — implementato**
   - Stato: i controller Crew/Company/Ship/Cost/Income/AnnualBudget/Mortgage/Campaign ora delegano la costruzione delle pagine e l’estrazione dei filtri al nuovo servizio `ListViewHelper`.
   - Beneficio: un’unica sorgente di verità per il mapping `Request->filters`, la selezione della pagina e la creazione del payload di paginazione (current/total/pages/from/to).

## Qualità dati e performance

4) **Indici database mirati [DONE]**
   - Entità: `Cost`, `Income`, `Mortgage`, `AnnualBudget`, `Crew`, `Ship`, `Company`, `Campaign`, `ShipAmendment`.
   - Indici consigliati: `user_id`, `ship_id`, `campaign_id`, `income_category_id`, `cost_category_id`, `company_role_id`, `cost_id`.
   - Note: per `Cost` valutare anche indice su `payment_day/payment_year` se si filtrano date in liste o report.
   - Beneficio: query filtrate/paginate più stabili sotto carico.

5) **Normalizzazione date imperiali [DONE]**
   - Stato: introdotto `ImperialDateHelper` per parsing (`DDD/YYYY` o solo `YYYY`), normalizzazione e serializzazione coerente.
   - Usato in:
     - controller/servizi che calcolano chiavi day/year (AnnualBudget chart, filtri);
     - formattazione uniforme in UI/PDF tramite filtro Twig `imperial_date`.
   - Beneficio: formato date consistente ovunque (liste, PDF, grafici) e riduzione di logica duplicata.

## UX / UI (qualità d’uso)

6) **Componente filtri riusabile [DONE]**
   - Stato: creato il partial `templates/components/index_filters.html.twig` usato da tutte le index. Raffinato con stile "Tactical Search Terminal" in v2.0.5.
   - Beneficio: layout coerente per label, griglie e pulsanti Search/Reset, con minori duplicazioni e look sci-fi integrato.

7) **Feedback su filtri attivi [DONE]**
   - Stato: badge “STATUS_FILTERED” (Amber) mostrato nel legend della terminal bar quando almeno un filtro è valorizzato.
   - Beneficio: i filtri attivi sono evidenti a colpo d’occhio nel contesto bridge.

8) **Placeholder operativi coerenti [DONE]**
   - Stato: placeholder uniformi applicati ai filtri day/year e campi di ricerca testo (es. `TITLE_DATA`, `TEMPORAL_START`).

9) **Select con ricerca per Cost ref in Amendments [DONE]**
   - Stato: la select Cost degli Ship Amendments usa Tom Select con ricerca e filtri su SHIP_GEAR/SHIP_SOFTWARE. Raffinato con stile tactical in v2.0.x.

## Contratti e PDF

10) **ContractFieldConfig come fonte unica [DONE]**
   - Stato: `ContractFieldConfig` guida i campi opzionali e i placeholder per i dettagli Income.
   - Beneficio: configurazione unica per campi mostrati/placeholder, con coerenza tra form e logica.

11) **Tracciamento versione dei template [DONE]**
   - Soluzione: mappa centralizzata in `config/template_versions.php` + helper Twig `template_version()`.

## Test e affidabilità

12) **Test funzionali minimi [DONE]**
   - Scopo: proteggere i flussi principali.
   - Copertura consigliata:
     - filtro + paginazione (Crew/Ship/Cost/Income);
     - ownership e 404 su entità non dell’utente;
     - generazione PDF (smoke test).

## Possibili evoluzioni (opzionali)

13) **Workflow “Draft → Signed” per contratti Income [DONE]**
   - Stato: introdotto `status` su Income con valori Draft/Signed.
   - Beneficio: distingue pre‑accordi e contratti finali in modo uniforme.

14) **Export operativo per sessione**
   - Generare un “session pack” (CSV/JSON) con crew, costs, incomes e budget di una singola ship.
   - Utile per logistica in sessione.
