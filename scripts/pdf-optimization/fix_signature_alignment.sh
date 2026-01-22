#!/bin/bash

# Script per aggiornare il CSS della tabella firme con allineamento colonne flessibile
# - 2 colonne: sinistra, destra
# - 3 colonne: sinistra, centro, destra

echo "Updating signature table CSS for flexible alignment..."
echo "======================================================"

# Elenco di tutti i template contratti
CONTRACTS=(
    "CHARTER"
    "CONTRACT"
    "FREIGHT"
    "INSURANCE"
    "INTEREST"
    "MAIL"
    "MORTGAGE"
    "PASSENGERS"
    "PRIZE"
    "SALVAGE"
    "SERVICES"
    "SUBSIDY"
    "TRADE"
)

for contract in "${CONTRACTS[@]}"; do
    FILE="templates/pdf/contracts/${contract}.html.twig"
    if [ -f "$FILE" ]; then
        echo "Processing: $FILE"
        
        # Sostituisci la sezione CSS signature-table con allineamento flessibile
        # Rimuovi la vecchia regola nth-child(2) e aggiorna in modo più flessibile
        sed -i.bak3 '
            # Rimuovi il vecchio allineamento centrale per nth-child(2)
            /\.signature-table th:nth-child(2),/,/\.signature-table td:nth-child(2) { text-align: center; }/d
            
            # Aggiorna le regole first-child e last-child su righe separate per chiarezza
            s/\.signature-table th:first-child,/\.signature-table th:first-child { text-align: left; }/
            s/\.signature-table td:first-child { text-align: left; }/\.signature-table td:first-child { text-align: left; }/
            
            # Dopo la regola last-child, aggiungi la regola nth-child(2) per tabelle a 3 colonne
            /\.signature-table td:last-child { text-align: right; }/ a\
        \
        /* Allineamento centrale per la colonna centrale (solo tabelle a 3 colonne) */\
        .signature-table th:nth-child(2):nth-last-child(2),\
        .signature-table td:nth-child(2):nth-last-child(2) { text-align: center; }
        ' "$FILE"
        
        echo "  ✓ Updated $FILE"
    else
        echo "  ✗ File not found: $FILE"
    fi
done

echo ""
echo "======================================================"
echo "CSS update complete!"
echo ""
echo "Signature table alignment now supports:"
echo "  - 2 columns: left, right"
echo "  - 3 columns: left, CENTER, right"
