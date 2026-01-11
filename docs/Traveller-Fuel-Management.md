# Traveller (MgT2 Update 2022) — Fuel: J-Drive, Power Plant, M-Drive e Reaction Drive

## 1) Capacità carburante: come si ragiona
In Traveller, la “fuel capacity” di una nave è la quantità di **tankage** (serbatoi, in **tons di displacement**) che decidi di dedicare al carburante.

Per una starship jump-capable, si divide quasi sempre in:
- **Jump fuel (J-Drive)**: serve a sostenere il salto.
- **Power plant fuel**: serve a far funzionare la nave “nel tempo” (vita di bordo, sistemi, manovra, sensori, ecc.), cioè l’energia.

## 2) Jump fuel (J-Drive)
Regola base:

**Fuel_jump (tons) = 0.1 × tonnellaggio_nave × distanza_salto (in parsec)**

Quindi:
- Jump-1 → 10% dello scafo
- Jump-2 → 20% dello scafo
- Jump-3 → 30% dello scafo

Un salto “corto” sotto 1 parsec conta comunque come Jump-1 per il consumo.

## 3) Power plant fuel
Oltre al jump fuel, la nave tiene carburante “di servizio” per l’autonomia del power plant.

Nei design standard spesso trovi l’autonomia espressa in **settimane/mesi** (tipicamente: abbastanza per coprire il salto e una finestra di operazioni). Il power plant può generalmente usare anche carburante non raffinato; il J-Drive è invece quello che vuole carburante raffinato per evitare problemi.

## 4) Propulsione: M-Drive vs Reaction Drive
### 4.1 Manoeuvre Drive (M-Drive)
Con l’M-Drive “classico” di Traveller (gravitico/reactionless), **non consumi propellente dedicato**: consumi **potenza** e quindi l’impatto è sul **power plant fuel**, non su un serbatoio “per i motori”.

### 4.2 Reaction Drive / Thrusters (razzi “veri”)
Se usi Reaction Drive (o thrusters ad alto burn), allora sì: consumi carburante/propellente per spingere massa.

Regola pratica (High Guard):

**Fuel (come % del displacement) = 2.5% × Thrust massimo (G) × ore a thrust massimo**

Dato che un turno di combattimento spaziale è 6 minuti:
- **1 ora di fuel = 10 turni** di manovra a thrust massimo.

C’è anche una regola “di emergenza”: se hai jump fuel disponibile, puoi (in certe condizioni) “bruciarlo” per manovrare con Reaction Drive, riducendo la jump capability.

---

## 5) Formule pratiche di consumo (che cosa “brucia” davvero carburante)
Qui sotto trovi una mini–cheat sheet “da scheda”. Le formule sono espresse in **dtons (tons di displacement)**.

### 5.1 J-Drive (Jump fuel)
**Jump fuel richiesto (dtons) = 0.1 × Hull (dtons) × Jump distance (parsec)**

Note operative:
- Anche un salto < 1 parsec conta come **Jump-1** per fuel.
- Il consumo del jump fuel è “one-shot” per quel salto (non “per giorno”).

### 5.2 Power Plant (fuel di servizio / endurance)
Nella sequenza di design, la tankage per il power plant viene ragionata come “mesi di endurance”. Una regola pratica usata in MgT2/High Guard è:

**Fuel_PP per 4 settimane (dtons) = max(1, ceil(0.1 × Tonnage_PP))**

Da qui puoi ottenere l’endurance:
- **Endurance (in settimane) = 4 × (Fuel_PP_allocato / Fuel_PP_per_4_settimane)**

E soprattutto la cosa che ti serve in gioco:
- Se fai **un jump**, stai in jump space ~1 settimana → in media consumi **circa 1/4** del fuel “da 4 settimane”, perché la nave resta operativa.

### 5.3 Consumo dettagliato “per power point” (tracking fine)
Se vuoi tracciare in modo più granulare *quanto* stai consumando mentre alimenti sistemi diversi (sensori attivi, fuel processor, armi a energia, comunicazioni, ecc.), puoi usare una conversione che parte dal minimo di endurance “1 dton per 4 settimane”.

Definizioni:
- **Turno** = 6 minuti (10 turni = 1 ora)
- **4 settimane** = 4 × 7 × 24 ore = 672 ore = 6720 turni

Quindi:
- **1 dton / 4 settimane = 1/6720 dton per turno ≈ 0.000149 dton/turno**

Una regola pratica (discussa in ambito RAW/interpretazione) è:
- **Fuel_per_turno(dtons) ≈ 0.000149 + 0.00000149 × max(0, PowerPoints_in_uso − 100)**

Oppure, ignorando il minimo e usando una linearizzazione semplice:
- **Fuel_per_turno(dtons) ≈ 0.00000149 × PowerPoints_in_uso**

Come si usa:
- Sommi i **power point** dei sistemi che stai effettivamente alimentando (o la tua “modalità operativa”).
- Converti in fuel per turno/ora/giorno.

Conversioni utili:
- **Fuel_per_ora = Fuel_per_turno × 10**
- **Fuel_per_giorno = Fuel_per_ora × 24**

### 5.4 Che cosa fa consumare fuel del power plant?
Nel modello “a grana grossa” di Traveller, il fuel del power plant viene considerato consumato quando fai operazioni che richiedono potenza “seria”, ad esempio:
- manovra (thrust / M-Drive)
- **sensori attivi**
- **raffinare carburante** (fuel processor)
- uso intenso di comunicazioni a lungo raggio
- armi a energia

Se sei **in drift**, senza manovre, senza sensori attivi e senza refining, in alcune interpretazioni l’endurance può essere trattata come quasi infinita (o comunque molto estesa) perché stai consumando pochissimo.

### 5.5 Sensori
- I **sensori passivi** sono “quasi sempre on” a basso impatto: di solito non li tracci.
- I **sensori attivi** (radar/lidar/active EM) li tratti come “modalità operativa” che alza i power point in uso → quindi aumentano il consumo via **5.2 / 5.3**.

### 5.6 Fuel Processor / Refining
Non è una “perdita di fuel” nel senso classico: è consumo energetico mentre lavori.
- L’effetto sul carburante è indiretto: bruci fuel del power plant mentre il processor è attivo.

### 5.7 M-Drive (riassunto)
- Nessun propellente dedicato.
- Il costo è tutto nel **power plant fuel** (endurance) e nella potenza disponibile.

### 5.8 Reaction Drive (riassunto)
- Ha un serbatoio “propellente” dedicato.
- Usa la formula del **2.5% × G × ore** (5.1 non c’entra).

---

# Caso concreto: Far Trader “Empress Marava” (MgT2 Update 2022)

## Dati chiave di progetto (valori canonici più comuni)
- **Displacement:** 200 tons
- **Jump:** 2
- **Maneuver:** 1G
- **Fuel tankage totale:** ~44 tons

In pratica, quei ~44 tons si leggono bene così:
- **40 tons** = jump fuel per **Jump-2** (20% di 200)
- **~4 tons** = fuel “di servizio” per il **power plant** (autonomia dichiarata nei profili canonici)

### Quanta autonomia di jump dà?
- Con **40 tons di jump fuel** hai:
  - **1 salto Jump-2**, oppure
  - **2 salti Jump-1**

### E il consumo “per i propulsori”?
- Se la nave usa il suo **M-Drive** (standard): nessun “serbatoio propellente” dedicato; consumi potenza → incide solo sul fuel del power plant.
- Se la nave montasse un **Reaction Drive** (variante/regola opzionale): devi aggiungere tankage extra.

Esempio (reaction drive ipotetico su 200 tons):
- **1G per 1 ora (10 turni)** → Fuel% = 2.5% × 1 × 1 = 2.5% → **5 tons**
- **1G per 6 ore** → 2.5% × 1 × 6 = 15% → **30 tons**

Nota pratica: queste tonnellate sono *oltre* al jump fuel e al fuel del power plant, perché il reaction drive è “affamato” di propellente.

---

## Post-jump: dopo un salto restano davvero “4 settimane di operazioni”?
Sì, ma solo se intendi “4 settimane” come **autonomia totale del power plant a serbatoi pieni**.

Il punto chiave è che:
- **Il jump consuma jump fuel.**
- **Il tempo passato in jump (circa 1 settimana) consuma comunque fuel del power plant**, perché la nave resta operativa (supporto vitale, sistemi, luci, computer, ecc.).

Quindi, se parti con “4 settimane” di power plant fuel e **vai subito in jump**:
- dopo l’uscita dal salto ti resta **circa 3 settimane** di autonomia di power plant (a parità di condizioni), perché **1 settimana l’hai appena spesa in jump space**.

Se invece prima del jump hai già fatto giorni/settimane di operazioni in-system, quel consumo si somma.

### Come si calcola il power plant fuel (regola di design)
In MgT2 (Update 2022) / High Guard 2022, il power plant fuel è dimensionato a “mesi di operazioni”: una regola di design comunemente usata è **10% della taglia del power plant per mese (4 settimane)**, con arrotondamenti/minimi a seconda del caso.

In gioco, l’effetto pratico è: la voce “4 weeks of operation” è un **serbatoio di endurance**, non un effetto speciale “post-jump”.

