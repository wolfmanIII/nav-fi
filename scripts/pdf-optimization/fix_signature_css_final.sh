#!/bin/bash

# Script per correggere il CSS delle tabelle firme con supporto flessibile 2/3 colonne

echo "Fixing signature table CSS for flexible column support..."
echo "=========================================================="

# Elenco template contratti (CONTRACT già corretto manualmente)
CONTRACTS=(
    "CHARTER"
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
        
        # Sostituisci l'nth-child(2) incondizionato con nth-child(2):nth-last-child(2) condizionato
        sed -i.bak4 '
            # Trova e sostituisci l'allineamento centrale di nth-child(2)
            s/\.signature-table th:nth-child(2),$/        \/\* Allineamento centrale SOLO per la colonna centrale nelle tabelle a 3 colonne \*\/\n        .signature-table th:nth-child(2):nth-last-child(2),/
            s/\.signature-table td:nth-child(2) { text-align: center; }/.signature-table td:nth-child(2):nth-last-child(2) { text-align: center; }/
        ' "$FILE"
        
        echo "  ✓ Updated $FILE"
    else
        echo "  ✗ File not found: $FILE"
    fi
done

echo ""
echo "=========================================================="
echo "CSS fix complete!"
echo ""
echo "Signature tables now support:"
echo "  - 2 columns: left, right (no center)"
echo "  - 3 columns: left, CENTER, right"
echo ""
echo "The selector :nth-child(2):nth-last-child(2) only matches"
echo "the 2nd column when there are exactly 3 columns total."
