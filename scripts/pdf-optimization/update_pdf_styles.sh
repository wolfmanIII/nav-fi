#!/bin/bash

# Script per aggiornare tutti i template PDF con CSS adatto alla stampa
# Questo script sostituisce i vecchi stili CSS con stili ottimizzati a basso consumo di inchiostro

# Definisci i nuovi stili CSS
read -r -d '' NEW_STYLES << 'EOF'
{% block stylesheets %}
    <style>
        /* Stili adatti alla stampa - uso inchiostro minimizzato */
        body { 
            font-family: 'Inter', 'Segoe UI', sans-serif; 
            font-size: 10px; 
            background: #ffffff; 
            color: #1f2937; 
        }
        
        /* Stile card - sfondo molto chiaro */
        .card { 
            border: 1px solid #e5e7eb; 
            border-radius: 6px; 
            background: #f9fafb; 
            page-break-inside: avoid; 
            margin-bottom: 0.75rem;
        }
        
        .no-break { page-break-inside: avoid; }
        
        .card-body { padding: 12px; }
        
        .card-title {
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        /* Tabelle - sfondo bianco, bordi sottili */
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            border: 1px solid #d1d5db; 
            background: #ffffff; 
        }
        
        .table th { 
            background: #f3f4f6; 
            border: 1px solid #d1d5db; 
            padding: 8px; 
            text-align: left;
            font-weight: 600;
            color: #374151;
        }
        
        .table td { 
            border: 1px solid #e5e7eb; 
            padding: 8px;
            color: #1f2937;
        }
        
        /* Colori alternati delle righe per leggibilità */
        .table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        /* Tabelle senza bordi */
        .table-borderless { 
            width: 100%; 
            border-collapse: collapse; 
        }
        
        .table-borderless th, 
        .table-borderless td { 
            border: none; 
            padding: 6px; 
        }
        
        /* Tabella firme - senza bordi, sfondo bianco */
        .signature-table { 
            width: 100%; 
            border-collapse: collapse; 
            background: #ffffff;
        }
        
        .signature-table th { 
            border: none;
            border-bottom: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            font-weight: 600;
            color: #374151;
        }
        
        .signature-table td { 
            border: none;
            padding: 24px 8px 8px 8px;
            color: #6b7280;
        }
        
        .signature-table th:first-child, 
        .signature-table td:first-child { text-align: left; }
        
        .signature-table th:last-child, 
        .signature-table td:last-child { text-align: right; }
        
        /* Tabella header - sfondo bianco */
        .header-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 0.75rem;
            background: #ffffff;
        }
        
        .header-table td { vertical-align: middle; }
        
        .logo { height: 64px; }
        
        /* Classi di utilità */
        .text-right { text-align: right; }
        .text-sm { font-size: 0.75rem; color: #6b7280; }
        .text-lg { font-size: 0.95rem; }
        .text-2xl { font-size: 1.15rem; }
        .font-bold { font-weight: 700; color: #1f2937; }
        .mb-4 { margin-bottom: 0.75rem; }
        .mb-6 { margin-bottom: 1rem; }
        .mt-3 { margin-top: 0.75rem; }
        .grid { display: grid; }
        .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .gap-4 { gap: 0.75rem; }
    </style>
{% endblock %}
EOF

# Elenco dei template contratti da aggiornare (escludendo CONTRACT.html.twig già fatto)
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

echo "Starting PDF template optimization..."
echo "======================================"

# Aggiorna i template contratti
for contract in "${CONTRACTS[@]}"; do
    FILE="templates/pdf/contracts/${contract}.html.twig"
    if [ -f "$FILE" ]; then
        echo "Processing: $FILE"
        
        # Crea backup
        cp "$FILE" "${FILE}.backup"
        
        # Usa sed per sostituire il blocco stylesheet
        # Sostituzione multi-linea usando perl per gestire meglio il testo
        perl -i -0pe 's/{% block stylesheets %}.*?{% endblock %}/'"$(echo "$NEW_STYLES" | sed 's/[&/\]/\\&/g')"'/s' "$FILE"
        
        echo "  ✓ Updated $FILE"
    else
        echo "  ✗ File not found: $FILE"
    fi
done

echo ""
echo "Contract templates completed!"
echo ""

# Nota: i fogli COST e SHIP richiedono gestione speciale per struttura diversa
echo "Note: cost/SHEET.html.twig and ship/SHEET.html.twig require manual review"
echo "      due to their unique structure with gradients and special styling."
echo ""
echo "======================================"
echo "Optimization complete!"
echo "Backups created with .backup extension"
