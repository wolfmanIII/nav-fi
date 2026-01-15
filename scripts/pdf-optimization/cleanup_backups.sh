#!/bin/bash

# Script per eliminare tutti i file di backup creati durante l'ottimizzazione PDF

echo "=========================================="
echo "Pulizia File di Backup PDF Templates"
echo "=========================================="
echo ""

# Contatori
total_removed=0
backup_dir="templates/pdf/contracts"

# Verifica che la directory esista
if [ ! -d "$backup_dir" ]; then
    echo "❌ Errore: Directory $backup_dir non trovata!"
    exit 1
fi

echo "📁 Directory: $backup_dir"
echo ""

# Lista i file di backup prima di eliminarli
echo "🔍 File di backup trovati:"
echo ""

backup_files=$(find "$backup_dir" -type f \( -name "*.backup" -o -name "*.bak*" \) 2>/dev/null)

if [ -z "$backup_files" ]; then
    echo "✅ Nessun file di backup trovato!"
    echo ""
    echo "=========================================="
    exit 0
fi

# Mostra i file
echo "$backup_files" | while read -r file; do
    size=$(du -h "$file" | cut -f1)
    echo "  - $(basename "$file") ($size)"
    ((total_removed++))
done

echo ""
echo "📊 Totale file da eliminare: $(echo "$backup_files" | wc -l)"
echo ""

# Chiedi conferma
read -p "⚠️  Vuoi eliminare questi file? (s/N): " confirm

if [[ ! "$confirm" =~ ^[sS]$ ]]; then
    echo ""
    echo "❌ Operazione annullata."
    echo "=========================================="
    exit 0
fi

echo ""
echo "🗑️  Eliminazione in corso..."
echo ""

# Elimina i file
removed_count=0
echo "$backup_files" | while read -r file; do
    if rm "$file" 2>/dev/null; then
        echo "  ✓ Eliminato: $(basename "$file")"
        ((removed_count++))
    else
        echo "  ✗ Errore eliminando: $(basename "$file")"
    fi
done

echo ""
echo "=========================================="
echo "✅ Pulizia completata!"
echo ""
echo "File eliminati: $(echo "$backup_files" | wc -l)"
echo ""
echo "Spazio liberato: $(echo "$backup_files" | xargs du -ch 2>/dev/null | tail -1 | cut -f1)"
echo "=========================================="
