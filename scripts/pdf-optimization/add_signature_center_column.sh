#!/bin/bash

# Script per aggiungere la colonna centrale "Signing location & Date" a tutte le tabelle firme dei contratti

echo "Adding center column to signature tables..."
echo "============================================"

# Elenco template contratti (escludendo MORTGAGE che ha già 3 colonne)
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
        
        # Usa sed per aggiungere la colonna centrale nella tabella firme
        # 1. Aggiungi <th> in thead dopo "Patron"
        # 2. Aggiungi <td> in tbody dopo il primo <td>
        
        sed -i.bak2 '
            # Nel thead della signature-table, aggiungi l'intestazione della colonna centrale dopo Patron
            /<table class="signature-table text-sm">/,/<\/thead>/ {
                s|<th>Patron</th>|<th>Patron</th>\n                        <th>Signing location \&amp; Date</th>|
            }
            # Nel tbody della signature-table, aggiungi la cella della colonna centrale dopo il primo td
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
