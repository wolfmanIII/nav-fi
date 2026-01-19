# Nav-Fi³ Technical Reference Data

Questo documento raccoglie i codici di riferimento e le costanti utilizzate per le entità di contesto dell'applicazione.
Questi valori sono utilizzati nei Seed e nella logica di business.

## 1. Cost Categories (`cost_category`)

Codici utilizzati per classificare le spese della nave.

- `PERSONAL`: Personal cost
- `CREW_GEAR`: Crew gear cost
- `SHIP_GEAR`: Ship gear cost (Used in Ship Amendments)
- `SHIP_SOFTWARE`: Ship software cost (Used in Ship Amendments)
- `SHIP_MAINT`: Ship maintenance cost
- `SHIP_REPAIR`: Ship repair cost
- `MEDICAL`: Medical cost
- `TRAVEL`: Travel cost
- `PENALTY`: Penalty cost
- `MORTGAGE_PENALTY`: Mortgage penalty cost
- `LEGAL`: Legal cost
- `RECRUITMENT`: Recruitment cost

## 2. Income Categories (`income_category`)

Codici utilizzati per classificare le entrate. Alcune categorie attivano sotto-form specifici (vedi `IncomeDetailsSubscriber`).

- `FREIGHT`: Cargo transport revenue (paid freight contracts).
- `PASSENGERS`: Passenger fares (tickets, berths, passage fees).
- `MAIL`: Mail contract revenue (official postal/courier runs).
- `CHARTER`: Charter hire income (ship rented/contracted as a whole).
- `CONTRACT`: Mission/contract payouts (patron jobs, bounties, assignments).
- `TRADE`: Trading profit (speculative trade margin, buy/sell spread).
- `SALVAGE`: Salvage and recovery income (wrecks, derelicts, recovered goods).
- `PRIZE`: Prize money / captured cargo or ship awards (legal seizure).
- `SUBSIDY`: Subsidies and grants (e.g., subsidized route payments).
- `SERVICES`: Service fees (refueling, repairs, towing, escort services, etc.).
- `INSURANCE`: Insurance payouts (claims, compensation for losses).
- `INTEREST`: Interest and investment income (financial returns).
