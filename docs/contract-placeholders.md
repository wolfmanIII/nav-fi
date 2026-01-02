## Contract placeholders – mappatura tecnica (escluso MORTGAGE)

Guida per collegare i placeholder dei template di contratto (`templates/contracts/*.html.twig`) alle entità del dominio. I campi non mappati restano da compilare per singola pratica. Il PDF del mutuo è escluso da questa mappatura.

### Entità e campi di riferimento
- **Ship**: `name`, `type/class`, `price`, `sessionDay/sessionYear` (cronologia di gioco).
- **Income**: `code` (ID pratica), `amount`, `signingDay/Year`, `paymentDay/Year`, `expirationDay/Year`, `cancelDay/Year`, `note`; FK verso `Ship`, `Company`, `IncomeCategory`, `LocalLaw`.
- **Company** (+ `CompanyRole`): `name`, `contact`, `signLabel`, ruolo/descrizione (`CompanyRole.code/description/shortDescription`).
- **LocalLaw**: `code`, `shortDescription`, `description`, `disclaimer`.
- **Crew** (opzionale per liste passeggeri/manifesti).

### Placeholder ricorrenti e mapping consigliato
- **Identificativi**: `{{CONTRACT_ID}}`, `{{DEAL_ID}}`, `{{RUN_ID}}`, `{{RECEIPT_ID}}`, `{{TICKET_ID}}`, `{{CLAIM_ID}}`, `{{SUBSIDY_ID}}`, `{{PRIZE_ID}}`, `{{SERVICE_ID}}`, `{{PROGRAM_REF}}`, `{{CASE_REF}}` → usa `Income.code` come ID pratica principale; gli altri restano campi liberi template-specifici.
- **Nave**: `{{VESSEL_NAME}}` (+ type/class se serve) → `Ship`. Per data di contesto di gioco usa `sessionDay/sessionYear`.
- **Controparti**: varianti `*_NAME`, `*_CONTACT`, `*_SIGN` (es. `CARRIER`, `SHIPPER`, `INSURER`, `AUTHORITY`, `BUYER`, `SELLER`, `PATRON`, `CONTRACTOR`, `CUSTOMER`, `PROVIDER`, `CAPTOR`, `SALVAGE_TEAM`, `AUTHORITY_OR_OWNER`, `PAYEE`, `PAYER`, ecc.) → `Company` (name/contact/signLabel) con ruolo da `CompanyRole`.
- **Rotte/luoghi**: `{{ORIGIN}}`, `{{DESTINATION}}`, `{{ROUTE}}`, `{{TRANSFER_POINT}}`, `{{LOCATION}}`, `{{SITE_LOCATION}}`, `{{EXCHANGE_LOCATION}}`, ecc. → dati operativi variabili per pratica; salva su `Income` (campi aggiuntivi o note) o inserisci a runtime.
- **Date timeline**: `{{DATE}}`, `{{START_DATE}}`, `{{END_DATE}}`, `{{PICKUP_DATE}}`, `{{DELIVERY_DATE}}`, `{{DISPATCH_DATE}}`, `{{ARRIVAL_DATE}}`, `{{DEPARTURE_DATE}}`, `{{SEIZURE_DATE}}`, `{{INCIDENT_DATE}}`, ecc. → riusa `Income.signingDay/Year`, `paymentDay/Year`, `expirationDay/Year`, `cancelDay/Year`; altre date restano specifiche del template.
- **Importi**: `{{PAY_AMOUNT}}`, `{{PAYMENT_TERMS}}`, `{{SUBSIDY_AMOUNT}}`, `{{PAYOUT_AMOUNT}}`, `{{INTEREST_EARNED}}`, `{{PRINCIPAL}}`, `{{UNIT_PRICE}}`, `{{TOTAL_PRICE}}`, `{{FARE_TOTAL}}`, `{{DEPOSIT}}`, `{{BONUS}}`, ecc. → `Income.amount` per l’importo principale; ulteriori valori sono campi liberi; valuta di default `{{CURRENCY}}` = `Cr`.
- **Termini legali/testo libero**: `{{LIABILITY_LIMIT}}`, `{{WARRANTY}}`, `{{EXPENSES_POLICY}}`, `{{CANCELLATION_TERMS}}`, `{{FAILURE_TERMS}}`, `{{REFUND_CHANGE_POLICY}}`, `{{NON_COMPLIANCE_TERMS}}`, `{{PROOF_REQUIREMENTS}}`, `{{REPORTING_REQUIREMENTS}}`, `{{RIGHTS_BASIS}}`, `{{DISPUTE_PROCESS}}`, `{{AS_IS_OR_WARRANTY}}`, `{{TRANSFER_CONDITION}}`, `{{CLAIM_WINDOW}}`, `{{CANCEL_RETURN_POLICY}}`, `{{AWARD_TRIGGER}}`, `{{DISPOSITION}}`, ecc. → rimangono campi di testo compilati per singolo contratto.
- **Quantità/descrizioni operative**: `{{CARGO_DESCRIPTION}}`, `{{CARGO_QTY}}`, `{{TOTAL_MASS}}`, `{{PACKAGE_COUNT}}`, `{{GOODS_DESCRIPTION}}`, `{{QTY}}`, `{{GRADE}}`, `{{BATCH_IDS}}`, `{{PRIZE_DESCRIPTION}}`, `{{RECOVERED_ITEMS_SUMMARY}}`, `{{SERVICE_TYPE}}`, `{{WORK_SUMMARY}}`, `{{MAIL_TYPE}}`, `{{SECURITY_LEVEL}}`, `{{SEAL_CODES}}`, `{{BAGGAGE_ALLOWANCE}}`, `{{EXTRA_BAGGAGE}}`, `{{CLASS_OR_BERTH}}`, ecc. → dati liberi per pratica (preferibilmente su `Income` o note).
- **Note**: `{{NOTES}}` → usa `Income.note` o campo libero del template.
- **Local Law**: aggiungi (se serve) placeholder espliciti `{{LOCAL_LAW_CODE}}`, `{{LOCAL_LAW_DESC}}`, `{{LOCAL_LAW_DISCLAIMER}}` legati a `Income.localLaw` (code/shortDescription/description/disclaimer).

### Placeholder per template principali (escluso MORTGAGE)
- **CHARTER**: CHARTER_ID, CHARTERER_NAME/CONTACT/SIGN, CARRIER_NAME/SIGN, VESSEL_NAME, DATE/START/END, AREA_OR_ROUTE, PURPOSE, MANIFEST_SUMMARY, PAYMENT_TERMS, DEPOSIT, EXTRAS, DAMAGE_TERMS, CANCELLATION_TERMS, NOTES.
- **SUBSIDY**: SUBSIDY_ID, AUTHORITY_NAME/CONTACT/SIGN, CARRIER_NAME/CONTACT/SIGN, PROGRAM_REF, VESSEL_NAME, ORIGIN→DESTINATION, START/END, SERVICE_LEVEL, SUBSIDY_AMOUNT, PAYMENT_TERMS, MILESTONES, REPORTING_REQUIREMENTS, NON_COMPLIANCE_TERMS, CANCELLATION_TERMS, PROOF_REQUIREMENTS, NOTES.
- **PRIZE**: PRIZE_ID, CAPTOR_NAME/CONTACT/SIGN, AUTHORITY_NAME/SIGN, VESSEL_NAME/CASE_REF/JURISDICTION, SEIZURE_LOCATION/DATE, LEGAL_BASIS, PRIZE_DESCRIPTION, ESTIMATED_VALUE, DISPOSITION, PRIZE_AWARD, PAYMENT_TERMS, SHARE_SPLIT, NOTES, AWARD_TRIGGER.
- **FREIGHT**: CONTRACT_ID, SHIPPER_NAME/CONTACT/SIGN, CARRIER_NAME/SIGN, VESSEL_NAME, ORIGIN→DESTINATION, PICKUP/DELIVERY, CARGO_DESCRIPTION/QTY/DECLARED_VALUE, PAYMENT_TERMS, LIABILITY_LIMIT, CANCELLATION_TERMS, NOTES.
- **SERVICES**: SERVICE_ID, CUSTOMER_NAME/CONTACT/SIGN, PROVIDER_NAME/SIGN, LOCATION, VESSEL_NAME/ID, SERVICE_TYPE, REQUESTED_BY, START/END, WORK_SUMMARY, PARTS_MATERIALS, RISKS, PAYMENT_TERMS, EXTRAS, TOTAL, LIABILITY_LIMIT, CANCELLATION_TERMS, NOTES.
- **PASSENGERS**: TICKET_ID, PASSENGER_NAMES/CONTACT/SIGN, CARRIER_NAME/SIGN, VESSEL_NAME, ORIGIN→DESTINATION, DEPARTURE/ARRIVAL, CLASS_OR_BERTH, QTY, BAGGAGE_ALLOWANCE/EXTRA_BAGGAGE, FARE_TOTAL, PAYMENT_TERMS, REFUND_CHANGE_POLICY, NOTES.
- **CONTRACT** (patron job): CONTRACT_ID, PATRON_NAME/CONTACT/SIGN, CONTRACTOR_NAME/SIGN, VESSEL_NAME, JOB_TYPE, LOCATION, OBJECTIVE, START/DEADLINE, SUCCESS_CONDITION, PAY_AMOUNT, PAYMENT_TERMS, BONUS, EXPENSES_POLICY, DEPOSIT, RESTRICTIONS, CONFIDENTIALITY_LEVEL, FAILURE_TERMS, CANCELLATION_TERMS, NOTES.
- **INTEREST**: RECEIPT_ID, PAYER_NAME/SIGN, PAYEE_NAME/CONTACT/SIGN, ACCOUNT_REF, INSTRUMENT, PRINCIPAL, INTEREST_RATE, START/END, CALC_METHOD, INTEREST_EARNED, NET_PAID, PAYMENT_TERMS, DISPUTE_WINDOW, NOTES.
- **MAIL**: RUN_ID, AUTHORITY_NAME/REF/CONTACT/SIGN, CARRIER_NAME/SIGN, VESSEL_NAME, ORIGIN→DESTINATION, DISPATCH/DELIVERY, MAIL_TYPE, PACKAGE_COUNT, TOTAL_MASS, SECURITY_LEVEL, SEAL_CODES, PAYMENT_TERMS, PROOF_OF_DELIVERY, LIABILITY_LIMIT, NOTES.
- **INSURANCE**: CLAIM_ID, INSURER_NAME/SIGN, INSURED_NAME/CONTACT/SIGN, POLICY_NUMBER, VESSEL_NAME, INCIDENT_REF/DATE/LOCATION/CAUSE/LOSS_TYPE, VERIFIED_LOSS, PAYOUT_AMOUNT, DEDUCTIBLE, COVERAGE_NOTES, PAYMENT_TERMS, ACCEPTANCE_EFFECT, SUBROGATION_TERMS, NOTES.
- **SALVAGE**: CLAIM_ID, SALVAGE_TEAM_NAME/CONTACT/SIGN, AUTHORITY_OR_OWNER_NAME/SIGN, CASE_REF, SITE_LOCATION, SOURCE, START/END, RECOVERED_ITEMS_SUMMARY, QTY_VALUE, HAZARDS, SALVAGE_AWARD, PAYMENT_TERMS, SPLIT_TERMS, RIGHTS_BASIS, AWARD_TRIGGER, DISPUTE_PROCESS, NOTES.
- **TRADE**: DEAL_ID, BUYER_NAME/CONTACT/SIGN, SELLER_NAME/CONTACT/SIGN, LOCATION, GOODS_DESCRIPTION, QTY, GRADE, BATCH_IDS, UNIT_PRICE, TOTAL_PRICE, PAYMENT_TERMS, DELIVERY_METHOD/DATE, TRANSFER_POINT/CONDITION, AS_IS_OR_WARRANTY/WARRANTY, CLAIM_WINDOW, CANCEL_RETURN_POLICY, NOTES.

### Collocazione dati consigliata
- **Ship**: VESSEL_NAME (+ type/class), session day/year.
- **Income**: ID pratica, importo, date (firma/pagamento/scadenza/annullamento), note, dati operativi variabili (origini/destinazioni/rotte) se si vuole centralizzarli.
- **Company**: tutte le controparti `*_NAME/CONTACT/SIGN` + ruolo (CompanyRole) e signLabel.
- **LocalLaw**: giurisdizione e disclaimer (`LOCAL_LAW_*`).
- **Crew**: opzionale per liste passeggeri/manifesti.
- **Altri placeholder**: termini legali, descrizioni cargo/servizio, policy, restrizioni, ecc. restano campi liberi per singolo template.

### Come gestire i campi opzionali di Income per categoria di contratto
I template hanno molti campi opzionali; per non mostrare tutto sempre:
- Tieni i campi extra su `Income` come nullable (aggiungi solo quelli richiesti dai contratti).
- Definisci una mappa “campi per categoria” (es. `IncomeCategory.code` → elenco di campi opzionali).
- In `IncomeType` usa un event subscriber (`PRE_SET_DATA` / `PRE_SUBMIT`) per aggiungere al form solo i campi previsti dalla categoria selezionata; gli altri non compaiono.
- Usa validation groups per categoria, così validi solo i campi mostrati.
- Se serve in EasyAdmin, applica la stessa logica in `configureFields()` o con un form type riusabile.
