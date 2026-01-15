#!/bin/bash

# Script to fix signature table CSS for flexible 2/3 column support

echo "Fixing signature table CSS for flexible column support..."
echo "=========================================================="

# List of contract templates (CONTRACT already fixed manually)
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
        
        # Replace the unconditional nth-child(2) with conditional nth-child(2):nth-last-child(2)
        sed -i.bak4 '
            # Find and replace the nth-child(2) center alignment
            s/\.signature-table th:nth-child(2),$/        \/\* Center alignment ONLY for middle column in 3-column tables \*\/\n        .signature-table th:nth-child(2):nth-last-child(2),/
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
