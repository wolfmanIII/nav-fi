# Analisi Criticità Architetturali

## 1. "The Cube" e i Numeri Magici
Attualmente la logica di gioco (es. probabilità di generare merci vs passeggeri) è *hardcoded* nel codice PHP di `TheCubeEngine` con strutture `if/elseif` e "numeri magici" (es. `<= 40`, `< 65`).

**Il Problema:**
Per bilanciare il gioco (es. "troppe missioni passeggeri, poche merci"), è necessario modificare il codice sorgente e fare un deploy. Un Game Master o un amministratore non può intervenire sulle regole senza conoscenze di programmazione.

**Consiglio:**
Spostare queste tabelle di probabilità e parametri in un file di configurazione (`yaml`) o, ancora meglio, nel database (es. entità `GameRules`). Questo permetterebbe di bilanciare il gioco dinamicamente senza toccare il motore logico.

## 2. La fragilità del "Determinismo" (`mt_srand`) [RISOLTO]
L'uso di un seed per rendere le missioni deterministiche è un'ottima idea per il gameplay, ma l'implementazione attuale basata su `mt_srand()` globale è rischiosa.

**Status (2026-01-27):** Risolto implementando `Random\Engine\Xoshiro256StarStar` locale. I servizi procedurali (`TheCubeEngine` e i generatori) ora utilizzano un'istanza isolata di `Randomizer` senza impattare lo stato globale.

**Il Problema Originario:**
`mt_srand` modifica lo stato *globale* del generatore di numeri casuali del processo PHP. Se una libreria di terze parti (o Symfony stesso) dovesse chiamare `mt_rand()` nel mezzo dell'esecuzione, o subito prima della logica del Cube, la sequenza "deterministica" verrebbe rotta. Inoltre, in ambienti asincroni o concorrenti (come Swoole o FrankenPHP), questo approccio è distruttivo perché lo stato globale è condiviso.

**Consiglio:**
Sfruttando PHP 8.2+, si dovrebbero adottare le nuove classi `Random\Engine` (come `PCG64` o `Xoshiro`). È possibile istanziare un oggetto generatore *separato e isolato* per ogni richiesta del Cube, garantendo un determinismo robusto e sicuro senza side-effect globali.

## 3. UI "Sci-Fi" vs Manutenibilità
Il comparto grafico (CSS) fa pesante uso di `@keyframes` custom e hack complessi (come `clip-path` per i bordi tagliati) mescolati a classi utility di Tailwind.

**Il Rischio:**
Sebbene l'estetica sia accattivante, la manutenibilità è compromessa. L'aggiunta di feature standard (come tabelle dati complesse o form nativi di EasyAdmin) richiederà sforzi sproporzionati per adattarle allo stile "Sci-Fi", costringendo a combattere contro il proprio CSS.

## 4. Proposta Soluzione UI: "Astrazione Tattica"

### Il Conflitto Principale
Si desidera un look "Sci-Fi" unico (Cyberpunk/Nav-UI), ma gli elementi HTML standard (EasyAdmin, Form) si rompono o appaiono sgradevoli perché il CSS attuale è troppo invasivo e basato su hack (`clip-path`, keyframes globali).

### La Soluzione: "Decorazione sopra Ispezione"
Invece di forzare ogni bottone ad *essere* un poligono tagliato tramite CSS globale, creiamo una chiara separazione tra **Layout/Funzione** (gestito da Tailwind/DaisyUI) e **Decorazione** (gestito da un "Theme Layer" specifico).

#### 1. Adottare un Approccio a Componenti (Twig Components)
Evitare di scrivere HTML/CSS grezzo per elementi comuni. Creare Componenti Twig riutilizzabili.

**Esempio: La Tactical Card**
Attuale: Copia-incolla di un `div` con 15 classi e stili custom per i bordi.
Proposto: `<twig:TacticalCard>`

```html
<!-- components/TacticalCard.html.twig -->
<div class="relative bg-slate-900 border border-slate-700 p-4 {{ attributes.class }}">
    <!-- Decorazione isolata (non impatta il layout del contenuto) -->
    <div class="absolute top-0 right-0 w-4 h-4 border-t-2 border-r-2 border-cyan-500"></div>
    <div class="absolute bottom-0 left-0 w-4 h-4 border-b-2 border-l-2 border-cyan-500"></div>
    
    <!-- Contenuto "Safe" (Layout standard) -->
    <div class="relative z-10">
        {{ block('content') }}
    </div>
</div>
```

*Perché funziona:* Il contenuto all'interno usa CSS standard. Gli elementi "Sci-Fi" sono overlay posizionati in assoluto che non rompono il flusso del layout.

#### 2. Isolare gli Effetti ad "Alto Costo"
Spostare il CSS rischioso (come `clip-path` che rompe ombre/click, o animazioni pesanti) in classi utility specifiche da applicare **ottimalmente**, non globalmente.

*   `btn-primary` -> Bottone Standard Tailwind/Daisy (Quadrato, affidabile).
*   `btn-tactical` -> La versione complessa e tagliata.
    *   *Regola:* Usare `btn-tactical` SOLO per azioni principali (Conferma, Jump). Usare `btn-primary` per tutto il resto (griglie, form admin, azioni secondarie).

#### 3. Il Pattern "Cornice" per EasyAdmin/Datatables
Quando si ha un componente standard (come una Datatable) difficile da stilizzare in profondità:
**Non stilizzare la tabella.** Stilizza la **Cornice** attorno ad essa.

Creare un "Monitor Container" che sembri uno schermo. Inserire la tabella standard e "noiosa" al suo interno.
*   L'utente ha la percezione di guardare uno schermo *nel gioco*.
*   Non è necessario hackerare il CSS di ogni `tr/td`.

#### 4. Sistema di Variabili CSS
Definire la palette "Sci-Fi" in `tailwind.config.js` mappandola a nomi semantici.
*   `colors: { 'hud-primary': '#00f0ff', 'hud-alert': '#ff003c' }`
*   Cambiare il look modificando la configurazione, non cercando/sostituendo codici esadecimali.

## 5. Proposta Soluzione GameRules: Configurazione Dinamica

### Il Conflitto
Il bilanciamento del gioco (probabilità, prezzi, modificatori) è bloccato nel codice. Modificare la frequenza delle missioni richiede un deploy.

### La Soluzione: "GameRules Entity"
Introdurre una entità `GameRule` che permetta al Game Master di modificare i parametri a caldo tramite EasyAdmin.

#### 1. Struttura Dati (Entity)
Una semplice tabella Key-Value tipizzata.

```php
// Entity/GameRule.php
class GameRule {
    private string $ruleKey;     // es. "cube.mission.passenger_chance"
    private string $value;       // es. "45"
    private string $type;        // es. "percentage", "integer", "boolean"
    private string $description; // Per l'interfaccia admin
}
```

#### 2. Service Layer (RuleEngine)
Un servizio che recupera le regole, gestendo cache e valori di default (nel caso la regola manchi nel DB).

```php
// Service/GameRules.php
public function get(string $key, mixed $default): mixed {
    // 1. Check Cache
    // 2. Check DB
    // 3. Return Default
}

// Utilizzo nel codice
if ($rng <= $this->rules->get('cube.mission.passenger_chance', 40)) {
    // Genera Passeggeri
}
```

#### 3. Interfaccia Admin
Usare EasyAdmin per creare una semplice griglia dove il GM può vedere le chiavi e modificare i valori.
*   Nessun bisogno di toccare PHP per dire "Oggi voglio più missioni di guerra".

### Benefici
*   **Live Tuning**: Bilanciamento immediato durante le sessioni.
*   **Sicurezza**: I valori di default nel codice garantiscono che il sistema funzioni anche con DB vuoto.

## Sintesi
Il progetto è un motore di gioco artigianale ambizioso e divertente, piuttosto che un'applicazione "enterprise" standard. Se lo scopo è l'uso personale come companion app, l'approccio attuale è accettabile. Se l'obiettivo dovesse spostarsi verso un prodotto manutenibile a lungo termine, sarà necessario smettere di aggiungere feature e concentrarsi sul disaccoppiare la logica (spezzare il God Object `Asset`, isolare le regole di gioco dal codice, e standardizzare la UI).
