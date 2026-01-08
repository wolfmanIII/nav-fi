   # Captain Log Web – Possibili miglioramenti

Documento di analisi tecnica con aree di miglioramento e funzionalità potenziali, basato sullo stato attuale del progetto.

## Priorità alte (impatto su sicurezza e coerenza)

1) **Ownership Campaign — risolto**
   - Stato: aggiunto `Campaign.user`, filtro per user in repository e controller.

2) **Validazione AnnualBudget (start/end) — risolto**
   - Stato: le query usano la funzione `parseDayYearFilter` in `AnnualBudgetRepository` e applicano `start >=` / `end <=` prima di restituire i risultati.
   - Beneficio: il filtro non restituisce più budget con range invertito, mantenendo il vincolo logico nel percorso di ricerca.

3) **Centralizzazione logica filtri/paginazione — implementato**
   - Stato: i controller Crew/Company/Ship/Cost/Income/AnnualBudget/Mortgage/Campaign ora delegano la costruzione delle pagine e l’estrazione dei filtri al nuovo servizio `ListViewHelper`.
   - Beneficio: un’unica sorgente di verità per il mapping `Request->filters`, la selezione della pagina e la creazione del payload di paginazione (current/total/pages/from/to).

## Qualità dati e performance

4) **Indici database mirati**
   - Entità: `Cost`, `Income`, `Mortgage`, `AnnualBudget`, `Crew`, `Ship`, `Company`.
   - Indici consigliati: `user_id`, `ship_id`, `campaign_id`, `income_category_id`, `cost_category_id`, `company_role_id`.
   - Beneficio: query filtrate/paginate più stabili sotto carico.

5) **Normalizzazione date imperiali**
   - Stato: molte query e filtri costruiscono chiavi day/year manualmente.
   - Soluzione: helper unico per:
     - parsing input (`DDD/YYYY`, solo `YYYY`);
     - confronto e normalizzazione;
     - serializzazione coerente nelle liste.

## UX / UI (qualità d’uso)

6) **Componente filtri riusabile**
   - Stato: i blocchi di filtro sono ripetuti nei template index.
   - Soluzione: partial Twig che standardizza label, layout e pulsanti (Search/Reset).

7) **Feedback su filtri attivi**
   - Stato: non è evidente quando un filtro è applicato.
   - Soluzione: badge “Filtered” o styling del fieldset se almeno un filtro è valorizzato.

8) **Placeholder operativi coerenti**
   - Azione: per filtri day/year usare placeholder uniformi (`Start >= Day/Year or Year`).

## Contratti e PDF

9) **ContractFieldConfig come fonte unica**
   - Stato: la form Income usa `IncomeDetailsSubscriber`, mentre `ContractFieldConfig` è una mappa parallela.
   - Soluzione: usare `ContractFieldConfig` come sorgente di verità per:
     - campi opzionali;
     - placeholder;
     - eventuale generazione di template/section.

10) **Tracciamento versione dei template**
   - Opzione: inserire una versione o hash nei PDF generati per auditing.

## Test e affidabilità

11) **Test funzionali minimi**
   - Scopo: proteggere i flussi principali.
   - Copertura consigliata:
     - filtro + paginazione (Crew/Ship/Cost/Income);
     - ownership e 404 su entità non dell’utente;
     - generazione PDF (smoke test).

## Possibili evoluzioni (opzionali)

12) **Workflow “Draft → Signed” per contratti Income**
   - Stato: il PDF esiste, ma manca un flag per stato contrattuale uniforme.
   - Valore: semplifica workflow di gioco (pre‑accordi vs contratti finali).

13) **Export operativo per sessione**
   - Generare un “session pack” (CSV/JSON) con crew, costs, incomes e budget di una singola ship.
   - Utile per logistica in sessione.
