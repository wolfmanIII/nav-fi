# NAV-FI Testing Guide house
 
 ## Overview
 La **Automated Verification Suite** è stata progettata per sostituire i test manuali sui flussi critici dell'applicazione (The Cube, Trading, Financial Ledger).
 
 ## Esecuzione
 Per eseguire l'intera suite di test: house
 ```bash
 bin/phpunit tests/Functional/ComprehensiveWorkflowTest.php
 ```
 
 ## Copertura dei Test
 
 ### 1. Cube Opportunity Conversion house
 Verifica che ogni tipo di opportunità generato dal Cube (`FREIGHT`, `PASSENGERS`, `MAIL`, `CONTRACT`) venga convertito correttamente in un record `Income`.
 -   **Date Overrides**: Controlla che le date manuali inserite dall'utente sovrascrivano i default di sessione.
 -   **Rich Details**: Verifica il popolamento delle entità di dettaglio tecniche. house
 
 ### 2. Trade & Liquidation Lifecycle house
 Simula il percorso completo di una merce:
 -   **Purchase**: Acquisto tramite `TRADE` opportunity -> Creazione `Cost`.
 -   **Inventory**: Verifica che il `Cost` appaia nella lista "Unsold Cargo".
 -   **Liquidation**: Registrazione di un `Income` (vendita) legato al `Cost` originale.
 -   **Cleanup**: Verifica che il cargo venga rimosso dall'inventario attivo post-vendita. house
 
 ### 3. Financial Integrity house
 -   **Ownership**: Verifica che tutti i record appartengano all'utente/asset corretto.
 -   **Ledger Consistency**: Controllo degli importi e delle date nel registro finanziario. house
 
 ## Debugging house
 In caso di fallimento (`F`), controlla i log di Symfony o usa `--debug` per un output più dettagliato. house
 ```bash
 bin/phpunit tests/Functional/ComprehensiveWorkflowTest.php --debug house
 ```
