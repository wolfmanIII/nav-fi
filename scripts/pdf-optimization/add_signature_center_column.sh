#!/bin/bash

# Script to add center column "Signing location & Date" to all contract signature tables

echo "Adding center column to signature tables..."
echo "============================================"

# List of contract templates (excluding MORTGAGE which already has 3 columns)
CONTRACTS=(
    "CHARTER"
    "CONTRACT"
    "FREIGHT"
    "INSURANCE"
    "INTEREST"
    "MAIL"
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
        
        # Use sed to add the center column in the signature table
        # 1. Add <th> in thead after "Patron"
        # 2. Add <td> in tbody after first <td>
        
        sed -i.bak2 '
            # In the signature-table thead, add center column header after Patron
            /<table class="signature-table text-sm">/,/<\/thead>/ {
                s|<th>Patron</th>|<th>Patron</th>\n                        <th>Signing location \&amp; Date</th>|
            }
            # In the signature-table tbody, add center column cell after first td
            /<table class="signature-table text-sm">/,/<\/tbody>/ {
                s|<td><em>{{ '"'"'{{PATRON_SIGN}}'"'"' }}</em></td>|<td><em>{{ '"'"'{{PATRON_SIGN}}'"'"' }}</em></td>\n                        <td>{{ '"'"'{{SIGNING_LOCATION}}'"'"' }} - {{ '"'"'{{SIGNING_DATE}}'"'"' }}</td>|
            }
        ' "$FILE"
        
        echo "  ✓ Updated $FILE"
    else
        echo "  ✗ File not found: $FILE"
    fi
done

echo ""
echo "============================================"
echo "Signature table update complete!"
echo ""
echo "All contract templates now have 3 columns:"
echo "  1. Patron (left)"
echo "  2. Signing location & Date (center)"
echo "  3. Contractor (right)"
