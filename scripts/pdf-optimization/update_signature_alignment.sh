#!/bin/bash

# Script per aggiungere l'allineamento centrale nelle tabelle firme con 3 colonne

echo "Updating signature table CSS for center column alignment..."
echo "============================================================"

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

# CSS da aggiungere per la colonna centrale (nth-child(2))
CENTER_ALIGN_CSS="        
        .signature-table th:nth-child(2),
        .signature-table td:nth-child(2) { text-align: center; }
        
        /* Per la sign-table di MORTGAGE con 3 colonne */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }
        
        .sign-table td {
            border: none;
            padding: 24px 8px 8px 8px;
            color: #6b7280;
            vertical-align: top;
        }
        
        .sign-table td:first-child { text-align: left; }
        .sign-table td:nth-child(2) { text-align: center; }
        .sign-table td:last-child { text-align: right; }"

# Aggiorna ogni template contratto
for contract in "${CONTRACTS[@]}"; do
    FILE="templates/pdf/contracts/${contract}.html.twig"
    if [ -f "$FILE" ]; then
        echo "Processing: $FILE"
        
        # Trova la riga con "signature-table td:last-child" e aggiungi l'allineamento centrale subito dopo
        # Usa awk per un inserimento preciso
        awk '
        /\.signature-table td:last-child \{ text-align: right; \}/ {
            print
            print ""
            print "        .signature-table th:nth-child(2),"
            print "        .signature-table td:nth-child(2) { text-align: center; }"
            print ""
            print "        /* Per la sign-table di MORTGAGE con 3 colonne */"
            print "        .sign-table {"
            print "            width: 100%;"
            print "            border-collapse: collapse;"
            print "            background: #ffffff;"
            print "        }"
            print ""
            print "        .sign-table td {"
            print "            border: none;"
            print "            padding: 24px 8px 8px 8px;"
            print "            color: #6b7280;"
            print "            vertical-align: top;"
            print "        }"
            print ""
            print "        .sign-table td:first-child { text-align: left; }"
            print "        .sign-table td:nth-child(2) { text-align: center; }"
            print "        .sign-table td:last-child { text-align: right; }"
            next
        }
        { print }
        ' "$FILE" > "${FILE}.tmp" && mv "${FILE}.tmp" "$FILE"
        
        echo "  ✓ Updated $FILE"
    else
        echo "  ✗ File not found: $FILE"
    fi
done

echo ""
echo "============================================================"
echo "Signature table alignment update complete!"
echo ""
echo "Changes applied:"
echo "  - signature-table: left, CENTER, right alignment"
echo "  - sign-table: added for MORTGAGE (3 columns)"
