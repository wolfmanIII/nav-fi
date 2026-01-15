#!/bin/bash

# Script to add center alignment for signature tables with 3 columns

echo "Updating signature table CSS for center column alignment..."
echo "============================================================"

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

# CSS to add for center column (nth-child(2))
CENTER_ALIGN_CSS="        
        .signature-table th:nth-child(2),
        .signature-table td:nth-child(2) { text-align: center; }
        
        /* For MORTGAGE sign-table with 3 columns */
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

# Update each contract template
for contract in "${CONTRACTS[@]}"; do
    FILE="templates/pdf/contracts/${contract}.html.twig"
    if [ -f "$FILE" ]; then
        echo "Processing: $FILE"
        
        # Find the line with "signature-table td:last-child" and add center alignment after it
        # Using awk for precise insertion
        awk '
        /\.signature-table td:last-child \{ text-align: right; \}/ {
            print
            print ""
            print "        .signature-table th:nth-child(2),"
            print "        .signature-table td:nth-child(2) { text-align: center; }"
            print ""
            print "        /* For MORTGAGE sign-table with 3 columns */"
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
