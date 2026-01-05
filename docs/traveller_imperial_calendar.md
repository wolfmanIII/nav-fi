# Traveller Imperial Calendar (Day Ranges per Month)

Assuming `Holiday = 001` and sits outside any month, the day ranges for months 1–13 are:

- **Month 1:** 002–029  
- **Month 2:** 030–057  
- **Month 3:** 058–085  
- **Month 4:** 086–113  
- **Month 5:** 114–141  
- **Month 6:** 142–169  
- **Month 7:** 170–197  
- **Month 8:** 198–225  
- **Month 9:** 226–253  
- **Month 10:** 254–281  
- **Month 11:** 282–309  
- **Month 12:** 310–337  
- **Month 13:** 338–365  

These ranges reflect a 365‑day year with one interstitial holiday (001) before Month 1.

## Implementation notes (realizzate)

- **Datatype**: `App\Model\ImperialDate` (`year`, `day` dove `day` è 1–365, 1 = Holiday).  
- **Form Type**: `App\Form\Type\ImperialDateType` espone un solo campo visibile (`display`, readonly) e due hidden (`year`, `day`). Opzioni: `min_year`, `max_year` (default 1105–9999). La classe CSS `datepicker` è applicata al campo visibile.  
- **Stimulus datepicker**: controller `assets/controllers/imperial_date_controller.js` apre un popover navigabile per mese (Traveller) con griglia di giorni, pulsanti « » per il cambio mese, posizionato sopra eventuali modali. Seleziona un giorno → chiude il popover e aggiorna `display` in formato `DDD/YYYY` e i campi hidden.  
- **Trucchi UI**:
  - Per evitare markup SVG nei data-attr, le frecce sono testuali (« ») passate via `data-imperial-date-prev-icon`/`next-icon`.
  - Il popover è `position:absolute` dentro la `.modal-box` con z-index alto e `overflow: visible` sul contenitore per non forzare scroll.
  - I hidden vengono resi manualmente nei form Twig per evitare wrapper con classi indesiderate (`mb-*`) generati dall’helper di Symfony.
- **Uso nel form**: `->add('paymentDate', ImperialDateType::class, [...])` con mapping manuale in `FormEvents::SUBMIT` per scrivere `paymentDay`/`paymentYear` sull’entità. Esempio concreto in `src/Form/CostType.php`; per Campaign, la modale “Update Session” usa lo stesso widget.  
- **Range mapping**: i range qui sopra sono codificati nel controller JS; il server non ricostruisce il mese, ma accetta il day-of-year normalizzato (1–365) rispettando i limiti min/max anno.  
- **Debug rapidi**: se il popover non si vede sopra una modale, verificare `position` e `overflow` del contenitore (`.modal-box`), e che gli asset siano ricompilati (`php bin/console asset-map:compile`).
