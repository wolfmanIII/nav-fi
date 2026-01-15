#!/bin/bash

# Script to update signature table CSS for flexible column alignment
# - 2 columns: left, right
# - 3 columns: left, center, right

echo "Updating signature table CSS for flexible alignment..."
echo "======================================================"

# List of all contract templates
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
        
        # Replace the signature-table CSS section with flexible alignment
        # Remove old nth-child(2) rule and update to be more flexible
        sed -i.bak3 '
            # Remove the old center alignment for nth-child(2)
            /\.signature-table th:nth-child(2),/,/\.signature-table td:nth-child(2) { text-align: center; }/d
            
            # Update first-child and last-child rules to be on separate lines for clarity
            s/\.signature-table th:first-child,/\.signature-table th:first-child { text-align: left; }/
            s/\.signature-table td:first-child { text-align: left; }/\.signature-table td:first-child { text-align: left; }/
            
            # After the last-child rule, add the nth-child(2) rule for 3-column tables
            /\.signature-table td:last-child { text-align: right; }/ a\
        \
        /* Center alignment for middle column (3-column tables only) */\
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
