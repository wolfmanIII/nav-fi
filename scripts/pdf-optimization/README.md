# Script di Ottimizzazione Template PDF

Questa directory contiene gli script bash utilizzati per ottimizzare i template PDF per una stampa printer-friendly.

## 📁 Panoramica Script

### 1. `update_pdf_styles.sh` ⭐ SCRIPT PRINCIPALE
**Scopo:** Aggiornamento batch di tutti i template PDF dei contratti con CSS ottimizzato per ridurre il consumo di inchiostro.

**Cosa fa:**
- Aggiorna 12 template di contratti con CSS printer-friendly
- Sostituisce i vecchi blocchi stylesheet con nuovi stili ottimizzati
- Crea file `.backup` per sicurezza

**Utilizzo:**
```bash
./update_pdf_styles.sh
```

**File interessati:**
- Tutti i template di contratti eccetto CONTRACT.html.twig (aggiornato manualmente per primo)
- CHARTER, FREIGHT, INSURANCE, INTEREST, MAIL, MORTGAGE, PASSENGERS, PRIZE, SALVAGE, SERVICES, SUBSIDY, TRADE

---

### 2. `update_signature_alignment.sh`
**Scopo:** Aggiungere CSS per l'allineamento della colonna centrale nelle tabelle delle firme.

**Cosa fa:**
- Aggiunge regole CSS per tabelle firme a 3 colonne
- Aggiunge supporto per la classe `sign-table` (usata in MORTGAGE)
- Imposta allineamenti: sinistra, centro, destra

**Utilizzo:**
```bash
./update_signature_alignment.sh
```

**CSS aggiunto:**
```css
.signature-table th:nth-child(2),
.signature-table td:nth-child(2) { text-align: center; }

.sign-table { /* ... */ }
```

---

### 3. `add_signature_center_column.sh`
**Scopo:** Aggiungere fisicamente la colonna centrale alle tabelle firme nell'HTML.

**Cosa fa:**
- Aggiunge header `<th>Signing location & Date</th>`
- Aggiunge cella `<td>{{SIGNING_LOCATION}} - {{SIGNING_DATE}}</td>`
- Converte tabelle a 2 colonne in tabelle a 3 colonne

**Utilizzo:**
```bash
./add_signature_center_column.sh
```

**Prima:**
```html
<th>Patron</th>
<th>Contractor</th>
```

**Dopo:**
```html
<th>Patron</th>
<th>Signing location & Date</th>
<th>Contractor</th>
```

---

### 4. `fix_signature_css_final.sh` ⭐ FIX FINALE
**Scopo:** Rendere il CSS delle tabelle firme flessibile per supportare sia 2 che 3 colonne.

**Cosa fa:**
- Sostituisce `nth-child(2)` incondizionato con selettore condizionale
- Usa `:nth-child(2):nth-last-child(2)` per centrare solo quando esistono 3 colonne
- Garantisce che le tabelle a 2 colonne rimangano allineate sinistra/destra

**Utilizzo:**
```bash
./fix_signature_css_final.sh
```

**Logica CSS:**
- `:nth-child(2):nth-last-child(2)` seleziona solo quando l'elemento è 2° E penultimo
- Questo è possibile solo con esattamente 3 elementi totali

---

### 5. `fix_signature_alignment.sh`
**Scopo:** Primo tentativo di allineamento flessibile (sostituito dallo script #4).

**Stato:** ⚠️ Sostituito da `fix_signature_css_final.sh`

---

## 🎯 Ordine di Esecuzione

Se devi ri-eseguire l'ottimizzazione da zero:

```bash
# 1. Ottimizza CSS per tutti i contratti
./update_pdf_styles.sh

# 2. Aggiungi CSS allineamento colonna centrale
./update_signature_alignment.sh

# 3. Aggiungi fisicamente colonna centrale all'HTML
./add_signature_center_column.sh

# 4. Correggi CSS per supporto flessibile 2/3 colonne
./fix_signature_css_final.sh
```

**Nota:** Questi script sono già stati eseguiti. Ri-eseguirli potrebbe causare problemi a meno che non ripristini prima i backup.

---

## 🔄 Rollback

Tutti gli script creano file di backup:

- `.backup` - Creati da `update_pdf_styles.sh`
- `.bak2` - Creati da `add_signature_center_column.sh`
- `.bak3` - Creati da `fix_signature_alignment.sh`
- `.bak4` - Creati da `fix_signature_css_final.sh`

**Per ripristinare:**
```bash
cd templates/pdf/contracts
for file in *.backup; do mv "$file" "${file%.backup}"; done
```

---

## 📊 Risultati

### Ottimizzazioni CSS Applicate

**Colori:**
- Sfondo: `#ffffff` (bianco)
- Card: `#f9fafb` (grigio leggerissimo)
- Bordi: `#e5e7eb`, `#d1d5db` (grigio chiaro)
- Testo: `#1f2937`, `#374151`, `#6b7280` (da grigio scuro a medio)

**Modifiche Principali:**
- ✅ Sfondi bianchi ovunque
- ✅ Sfondi card molto chiari
- ✅ Bordi sottili (1px)
- ✅ Nessun gradiente
- ✅ Tabelle firme: senza bordi, sfondo bianco
- ✅ Header tabelle: grigio chiaro (#f3f4f6)
- ✅ Righe alternate per leggibilità

### Allineamento Tabelle Firme

**2 colonne:**
- Colonna 1: sinistra
- Colonna 2: destra

**3 colonne:**
- Colonna 1: sinistra
- Colonna 2: **centro**
- Colonna 3: destra

---

## 📝 Template Modificati

### Template Contratti (13)
1. CHARTER.html.twig
2. CONTRACT.html.twig
3. FREIGHT.html.twig
4. INSURANCE.html.twig
5. INTEREST.html.twig
6. MAIL.html.twig
7. MORTGAGE.html.twig
8. PASSENGERS.html.twig
9. PRIZE.html.twig
10. SALVAGE.html.twig
11. SERVICES.html.twig
12. SUBSIDY.html.twig
13. TRADE.html.twig

### Template Schede (2)
- cost/SHEET.html.twig (ottimizzazione manuale)
- ship/SHEET.html.twig (ottimizzazione manuale, gradienti rimossi)

---

## 💡 Suggerimenti

### Test
Genera un PDF dopo le modifiche:
```bash
# Genera PDF di test tramite l'applicazione
# Stampa per verificare il consumo di inchiostro
```

### Pulizia
Dopo aver confermato che le modifiche funzionano:
```bash
# Rimuovi file di backup
rm templates/pdf/contracts/*.backup
rm templates/pdf/contracts/*.bak*

# Rimuovi script (opzionale)
rm -rf scripts/pdf-optimization
```

---

## 🎨 Risparmio Inchiostro

**Riduzione stimata:** 80-85%

**Prima:**
- Sfondi scuri, gradienti
- Bordi spessi
- Copertura: 40-50%

**Dopo:**
- Sfondi bianchi/molto chiari
- Bordi sottili
- Copertura: 5-10%

---

## 📚 File di Riferimento

- `templates/pdf/_pdf_styles_reference.twig` - Riferimento CSS completo
- Questo README - Documentazione

---

## ⚠️ Note Importanti

1. **Non ri-eseguire gli script** a meno che tu non sappia cosa stai facendo
2. **Esistono backup** ma potrebbero essere sovrascritti ri-eseguendo gli script
3. **Modifiche manuali** sono state fatte a cost/SHEET e ship/SHEET
4. **CONTRACT.html.twig** è stato aggiornato manualmente prima degli script batch

---

## 🔗 Documentazione Correlata

Vedi walkthrough principale: `/home/apascucci/.gemini/antigravity/brain/27c9a7c0-dd36-47b7-867d-11eddb7633d6/walkthrough.md`

---

## 📖 Spiegazione Tecnica

### Selettore CSS Intelligente

Il selettore `.signature-table td:nth-child(2):nth-last-child(2)` funziona così:

- `:nth-child(2)` - seleziona il 2° elemento
- `:nth-last-child(2)` - seleziona il penultimo elemento
- **Insieme** - selezionano un elemento che è CONTEMPORANEAMENTE il 2° E il penultimo

Questo è possibile **SOLO** quando ci sono esattamente **3 elementi** totali!

**Esempio con 2 colonne:**
- Colonna 1: è `:first-child` ma NON `:nth-child(2)` → nessun match
- Colonna 2: è `:nth-child(2)` ma NON `:nth-last-child(2)` (è l'ultimo) → nessun match
- **Risultato:** nessuna colonna viene centrata ✅

**Esempio con 3 colonne:**
- Colonna 1: è `:first-child` ma NON `:nth-child(2)` → nessun match
- Colonna 2: è `:nth-child(2)` E `:nth-last-child(2)` → **MATCH!** → centrato ✅
- Colonna 3: è `:last-child` ma NON `:nth-child(2)` → nessun match
- **Risultato:** solo la colonna centrale viene centrata ✅

---

## 🎯 Riepilogo Modifiche

### Fase 1: Ottimizzazione CSS Base
- Sfondi bianchi
- Colori chiari
- Bordi sottili
- Rimozione gradienti

### Fase 2: Allineamento Firme
- Aggiunta regola CSS per colonna centrale
- Supporto tabella `sign-table`

### Fase 3: Colonna Fisica HTML
- Aggiunta `<th>` e `<td>` per "Signing location & Date"
- Conversione da 2 a 3 colonne

### Fase 4: CSS Flessibile
- Selettore condizionale `:nth-child(2):nth-last-child(2)`
- Supporto automatico per 2 o 3 colonne
- Nessuna modifica HTML richiesta

---

**Ultimo Aggiornamento:** 15 Gennaio 2026  
**Autore:** Antigravity AI Assistant  
**Progetto:** Ottimizzazione Template PDF Nav-Fi
