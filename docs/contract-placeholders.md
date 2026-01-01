## Contract placeholders – mappatura tecnica

Documento tecnico di riferimento per i placeholder presenti in `docs/contract-text` e il loro ancoraggio alle entità esistenti (`Ship`, `Income`) o alla futura anagrafica `Company`. I placeholder non mappati restano campi da compilare a runtime nella singola pratica.

### Placeholder ricorrenti e mapping consigliato

**Vessel/Ship**
- `{{VESSEL_NAME}}` (+ type/class se necessario) → `Ship` (name, type/class, price, mortgage status se usato).
- `{{AREA_OR_ROUTE}}`, `{{ORIGIN}}`, `{{DESTINATION}}`, `{{ROUTE}}`, `{{TRANSFER_POINT}}` → dati operativi della singola pratica; è preferibile gestirli su `Income`/contratto (non su `Ship`) perché variano per incarico.

**Date / timeline**
- `{{DATE}}`, `{{START_DATE}}`, `{{END_DATE}}`, `{{PICKUP_DATE}}`, `{{DELIVERY_DATE}}`, `{{DISPATCH_DATE}}`, `{{ARRIVAL_DATE}}`, `{{DEPARTURE_DATE}}`, `{{SEIZURE_DATE}}`, `{{INCIDENT_DATE}}`, ecc. → dati specifici della pratica. Su `Income` puoi riutilizzare `signingDay/Year`, `paymentDay/Year`, `expirationDay/Year`, `cancelDay/Year`; gli altri restano campi scenario-specifici.

**Importi e valuta**
- `{{CURRENCY}}` → default “Cr” o configurazione di base.
- `{{PAY_AMOUNT}}`, `{{PAYOUT_AMOUNT}}`, `{{TOTAL_PRICE}}`, `{{UNIT_PRICE}}`, `{{SUBSIDY_AMOUNT}}`, `{{INTEREST_EARNED}}`, `{{PRINCIPAL}}`, ecc. → importi della pratica; `Income.amount` può coprirne uno, altri richiedono campi dedicati o note.
- `{{PAYMENT_TERMS}}`, `{{DEPOSIT}}`, `{{LIABILITY_LIMIT}}`, `{{WARRANTY}}`, `{{EXPENSES_POLICY}}`, ecc. → termini testuali per il template (campi liberi o modello contratto dedicato).

**Controparti (nuova `Company`)**
- Ricorrenze: `{{CARRIER_NAME}}`, `{{SHIPPER_NAME}}`, `{{INSURER_NAME}}`, `{{AUTHORITY_NAME}}`, `{{BUYER_NAME}}`, `{{SELLER_NAME}}`, `{{PATRON_NAME}}`, `{{CONTRACTOR_NAME}}`, `{{CUSTOMER_NAME}}`, `{{PROVIDER_NAME}}`, `{{CAPTOR_NAME}}`, `{{SALVAGE_TEAM_NAME}}`, `{{AUTHORITY_OR_OWNER_NAME}}`, `{{PAYEE_NAME}}`, `{{PAYER_NAME}}`, ecc.
- Varianti contatto: `{{*_CONTACT}}`; varianti firma: `{{*_SIGN}}`.
- Proposta `Company`: `id`, `name`, `contact` (string), `role/type` (carrier/shipper/insurer/authority/buyer/seller/provider/...), `sign_label` (nome in firma), `notes/ref`.

**Ship/Crew**
- Crew non ha placeholder diretti, salvo liste nomi (`PASSENGER_NAMES`, `CAPTOR_*`, `SALVAGE_TEAM_NAME`); possono provenire da `Crew` se vuoi precompilare, altrimenti restano testo libero.

**ID pratiche/documenti**
- `{{CHARTER_ID}}`, `{{CONTRACT_ID}}`, `{{TICKET_ID}}`, `{{RUN_ID}}`, `{{PROGRAM_REF}}`, `{{SERVICE_ID}}`, `{{CASE_REF}}`, `{{CLAIM_ID}}`, `{{RECEIPT_ID}}`, `{{PRIZE_ID}}`, `{{DEAL_ID}}`, ecc. → codici pratica. `Income.code` può essere riutilizzato come `CONTRACT_ID`/`DEAL_ID`; gli altri restano campi liberi per template.

**Termini generali (testo libero)**
- `{{PAYMENT_TERMS}}`, `{{CANCELLATION_TERMS}}`, `{{DAMAGE_TERMS}}`, `{{NON_COMPLIANCE_TERMS}}`, `{{CANCEL_RETURN_POLICY}}`, `{{REFUND_CHANGE_POLICY}}`, `{{RESTRICTIONS}}`, `{{LEGAL_BASIS}}`, `{{RIGHTS_BASIS}}`, `{{DISPUTE_WINDOW}}`, `{{REPORTING_REQUIREMENTS}}`, `{{PROOF_REQUIREMENTS}}`, `{{STORAGE_FEES}}`, `{{SPLIT_TERMS}}`, `{{SHARE_SPLIT}}`, ecc. → campi “termini/note” specifici del template.

**Quantità/descrizioni operative**
- `{{MANIFEST_SUMMARY}}`, `{{CARGO_DESCRIPTION}}`, `{{CARGO_QTY}}`, `{{TOTAL_MASS}}`, `{{PACKAGE_COUNT}}`, `{{QTY}}`, `{{GOODS_DESCRIPTION}}`, `{{GRADE}}`, `{{BATCH_IDS}}`, `{{PRIZE_DESCRIPTION}}`, `{{RECOVERED_ITEMS_SUMMARY}}`, `{{WORK_SUMMARY}}`, `{{SERVICE_TYPE}}`, `{{MAIL_TYPE}}`, ecc. → testo libero per singola pratica.

**Note**
- `{{NOTES}}` ovunque: può usare `note` già presente (es. `Income.note`) o restare campo libero.

### Riutilizzo rapido
- **Ship**: `{{VESSEL_NAME}}` (e type/class se richiesto).
- **Income**: `code` → `CONTRACT_ID`/`DEAL_ID`; `amount` → uno degli importi; `signingDay/Year`, `paymentDay/Year`, `expirationDay/Year`, `cancelDay/Year`, `note`; campi operativi come `AREA_OR_ROUTE`/`ORIGIN`/`DESTINATION` possono vivere qui perché variabili per contratto.
- **Company**: copre tutte le varianti `*_NAME`, `*_CONTACT`, `*_SIGN` variando ruolo/type.
- **Generici**: `{{CURRENCY}}` (Cr), `{{PAYMENT_TERMS}}`, `{{START_DATE}}`, `{{END_DATE}}`, `{{NOTES}}`.

### Collocazione dei dati
- **Ship**: nome, tipo/class, prezzo, stato mortgage, session day/year.
- **Crew**: elenco nomi/ruoli (opzionale) per compilare liste passeggeri/manifesto.
- **Income**: ID/Code pratica, importo, date (firma/pagamento/scadenza/annullamento), note, e i dati operativi variabili per contratto (origini/destinazioni/rotte).
- **Company**: anagrafica controparti per riciclare `*_NAME`, `*_CONTACT`, `*_SIGN`.
- **Resto**: termini e descrizioni specifiche restano campi ad hoc per singolo template.
