# Calendario Imperiale Traveller (range giorni/mese)

Assumendo `Holiday = 001` (fuori dai mesi), i range giorno per i mesi 1–13 sono:

- **Mese 1:** 002–029  
- **Mese 2:** 030–057  
- **Mese 3:** 058–085  
- **Mese 4:** 086–113  
- **Mese 5:** 114–141  
- **Mese 6:** 142–169  
- **Mese 7:** 170–197  
- **Mese 8:** 198–225  
- **Mese 9:** 226–253  
- **Mese 10:** 254–281  
- **Mese 11:** 282–309  
- **Mese 12:** 310–337  
- **Mese 13:** 338–365  

Questi range presuppongono un anno da 365 giorni con una festività interstiziale (001) prima del Mese 1.

## Note implementative (realizzate)

- **Datatype**: `App\Model\ImperialDate` (`year`, `day` dove `day` è 1–365, 1 = Holiday).  
- **Form Type**: `App\Form\Type\ImperialDateType` espone un solo campo visibile (`display`, readonly) e due hidden (`year`, `day`). Opzioni: `min_year`, `max_year` (default 1105–9999). La classe CSS `datepicker` è applicata al campo visibile.  
- **Stimulus datepicker**: controller `assets/controllers/imperial_date_controller.js` apre un popover navigabile per mese (Traveller) con griglia di giorni, pulsanti « » per il cambio mese, posizionato sopra eventuali modali. Seleziona un giorno → chiude il popover e aggiorna `display` in formato `DDD/YYYY` e i campi hidden.  
- **Trucchi UI**:
  - Per evitare markup SVG nei data-attr, le frecce sono testuali (« ») passate via `data-imperial-date-prev-icon`/`next-icon`.
  - Il popover è `position:absolute` dentro la `.modal-box` con z-index alto e `overflow: visible` sul contenitore per non forzare scroll.
  - I hidden vengono resi manualmente nei form Twig per evitare wrapper con classi indesiderate (`mb-*`) generati dall’helper di Symfony.
  - Per mostrare subito valori già salvati, il campo `display` viene precompilato da Twig con `value="DDD/YYYY"` e con `data-imperial-date-initial-day/year` valorizzati (vedi dettagli Income: Charter/Subsidy).
- **Uso nel form**: `->add('paymentDate', ImperialDateType::class, [...])` con mapping manuale in `FormEvents::SUBMIT` per scrivere `paymentDay`/`paymentYear` sull’entità. Esempi concreti:
  - `src/Form/CostType.php` → mappa `paymentDate` su `paymentDay/paymentYear`; in Twig (`templates/cost/edit.html.twig`) rendere manualmente `display`, `year`, `day` dentro un wrapper `data-controller="imperial-date"` per evitare markup extra.
  - `CampaignController` modale “Update Session” (`templates/campaign/details.html.twig`) usa lo stesso widget e wrapper.
  - Dettagli Income (Charter, Subsidy, Freight, Passengers, Services, Mail, Interest, Trade, Insurance, Contract, Prize, Salvage) applicano lo stesso pattern e pre-valorizzano display + data-* per far emergere i dati già presenti.
  - Mortgage (startDate), MortgageInstallment (paymentDate), Cost (paymentDate), AnnualBudget (start/end) e Crew (birthDate) usano ImperialDateType; il calendario Ship non viene più usato, perché le sessioni derivano da Campaign.
- **Range mapping**: i range qui sopra sono codificati nel controller JS; il server non ricostruisce il mese, ma accetta il day-of-year normalizzato (1–365) rispettando i limiti min/max anno.  
- **Debug rapidi**: se il popover non si vede sopra una modale, verificare `position` e `overflow` del contenitore (`.modal-box`), e che gli asset siano ricompilati (`php bin/console asset-map:compile`).

## Come integrare una nuova data (day/year) con il calendario imperiale
1) **Entity**: mantieni i campi `xxxDay` (int) e `xxxYear` (int).  
2) **Form Type**: aggiungi il campo con `ImperialDateType` (mapped=false) e un listener `FormEvents::SUBMIT` per copiare day/year da `ImperialDate` ai campi dell’entità. Rispetta `min_year`/`max_year` (min derivato da `campaign_start_year` se disponibile, altrimenti `APP_YEAR_MIN`).  
3) **Twig**: non usare `form_row`. Renderizza manualmente:
   ```twig
   <div data-controller="imperial-date">
     {{ form_label(form.fooDate) }}
     {{ form_widget(form.fooDate.display, { attr: {
       'data-imperial-date-prev-icon': '«',
       'data-imperial-date-next-icon': '»',
       'data-imperial-date-initial-day': existing_day ?? '',
       'data-imperial-date-initial-year': existing_year ?? '',
       'value': existing_value ~ ''
     }}) }}
     {{ form_widget(form.fooDate.year) }}
     {{ form_widget(form.fooDate.day) }}
     {{ form_errors(form.fooDate) }}
   </div>
   ```
   Precompila `value="DDD/YYYY"` e i data-attr usando i valori già salvati, così il picker è coerente al load.  
4) **Popover su modale**: assicurati che il contenitore abbia `overflow: visible` o usa `.modal-box` come contesto posizionato.  
5) **Asset**: se il comportamento non cambia, basta `php bin/console asset-map:compile` (o equivalente vite/encore) per rigenerare il JS.
