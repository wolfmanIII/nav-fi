# Proposal: The Cube // Contract System 2.0

## 1. Economic Rebalance (Realism)
Current rewards (10-50k Cr) are often too low for the risk involved in a spacefaring "Shadowrunner" style campaign.

**Proposed Formula:**
`Base Reward + (Difficulty Multiplier * Risk Factor) + Operational Expenses`

-   **Tiered Rewards**:
    -   *Routine*: 50k - 150k Cr (Local courier, minor security)
    -   *Hazardous*: 200k - 500k Cr (Extraction, corporate theft)
    -   *Black Ops*: 1M+ Cr (High risk, high yield, illegal)
-   **Advance Payment**: Option to negotiate upfront vs completion.

## 2. Narrative Engine ("The Story")
Contracts should feel like "Episodes". We will use the **Seed** to deterministically generate a 3-part narrative structure.

### A. The Patron (Who?)
Instead of just "Local Corp", we generate specific archetypes:
-   *The Desperate Noble*: Needs discretion, pays well but entitlement issues.
-   *The Faceless Corp*: High pay, zero moral latitude.
-   *The Fixer*: Shady, dangerous, but connects to the underworld.

### B. The Mission (What?)
Specific mission profiles with flavor text templates:
1.  **Extraction**: "Recover 'Asset X' from a secure facility."
2.  **Smuggling**: "Move sensitive cargo past a blockade."
3.  **Data Heist**: "Infiltrate a server farm and steal the ledger."
4.  **Wetwork**: "Eliminate a piracy threat." (Optional moral filters)

### C. The Twist (The "Uh Oh")
Every good contract has a complication (1 in 3 chance):
-   *Intelligence Failure*: The target is heavily guarded than reported.
-   *Third Party*: A rival team is also on the job.
-   *Moral Dilemma*: The "Asset" is a person, or the cargo is humanitarian aid.

## 3. Implementation Strategy (Strategy Pattern Extended)
The `ContractGenerator` will be expanded to use a `NarrativeBuilder` service.

```php
// Example Generated Contract
[
    'type' => 'EXTRACTION',
    'summary' => 'Extract Dr. Aris Thorne from the GeDeCo Research Outpost.',
    'patron' => 'GeDeCo Rival Faction',
    'reward' => 450000, // Cr
    'risk' => 'High',
    'briefing' => 'Target is being held against their will. Security is automated. Non-lethal approach preferred.',
    'complication' => 'The target keeps a dangerous pet.',
]
```

## 4. Configuration
We will move the narrative tables to `config/packages/cube_narrative.yaml` or a dedicated JSON resource to keep the PHP code clean and the content easily editable.
