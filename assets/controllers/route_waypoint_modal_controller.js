import { Controller } from '@hotwired/stimulus';

/**
 * Route Waypoint Modal Controller
 * Gestisce l'aggiunta di waypoints dalla pagina route details.
 */
export default class extends Controller {
    static targets = [
        'modal',
        'form',
        'inputHex',
        'inputSector',
        'inputWorld',
        'inputUwp',
        'inputNotes',
        'tableBody',
        'emptyRow'
    ];

    static values = {
        addUrl: String,
        deleteUrl: String,
        lookupUrl: String,
        routeId: Number,
        recalculateUrl: String
    };

    // Apre la modale
    openModal() {
        this.clearForm();
        this.modalTarget.showModal();
    }

    // Chiude la modale
    closeModal() {
        this.modalTarget.close();
    }

    // Pulisce il form
    clearForm() {
        this.inputHexTarget.value = '';
        this.inputSectorTarget.value = '';
        this.inputWorldTarget.value = '';
        this.inputUwpTarget.value = '';
        this.inputNotesTarget.value = '';
    }

    // Lookup world su blur di hex/sector
    async lookup() {
        const hex = this.inputHexTarget.value.trim().toUpperCase();
        const sector = this.inputSectorTarget.value.trim();

        if (!hex || !sector) {
            return;
        }

        try {
            const url = `${this.lookupUrlValue}?hex=${encodeURIComponent(hex)}&sector=${encodeURIComponent(sector)}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.found) {
                this.inputWorldTarget.value = data.world || '';
                this.inputUwpTarget.value = data.uwp || '';
            } else {
                this.inputWorldTarget.value = '';
                this.inputUwpTarget.value = '';
            }
        } catch (e) {
            console.error('Lookup failed:', e);
        }
    }

    // Submit nuovo waypoint
    async submit(event) {
        event.preventDefault();

        const hex = this.inputHexTarget.value.trim().toUpperCase();
        const sector = this.inputSectorTarget.value.trim();

        if (!hex || !sector) {
            alert('Hex e Sector sono obbligatori');
            return;
        }

        try {
            const formData = new FormData(this.formTarget);

            const response = await fetch(this.addUrlValue, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.addRowToTable(data.waypoint);
                this.closeModal();
                this.hideEmptyRow();
            } else {
                alert(data.error || 'Errore nel salvataggio');
            }
        } catch (e) {
            console.error('Submit failed:', e);
            alert('Errore di rete');
        }
    }

    // Rimuove waypoint
    async deleteWaypoint(event) {
        const waypointId = event.currentTarget.dataset.waypointId;
        const row = event.currentTarget.closest('tr');

        if (!confirm('Rimuovere questo waypoint?')) {
            return;
        }

        try {
            const url = this.deleteUrlValue.replace('__ID__', waypointId);
            const response = await fetch(url, { method: 'DELETE' });
            const data = await response.json();

            if (data.success) {
                row.remove();
                this.checkEmptyTable();
            } else {
                alert(data.error || 'Errore nella rimozione');
            }
        } catch (e) {
            console.error('Delete failed:', e);
            alert('Errore di rete');
        }
    }

    // Aggiunge riga alla tabella
    addRowToTable(wp) {
        const row = document.createElement('tr');
        row.className = 'hover:bg-emerald-500/5 transition-colors text-slate-300';

        // Determina classe colore per zona
        let zoneClass = '';
        if (wp.zone === 'R') zoneClass = 'text-red-400';
        else if (wp.zone === 'A') zoneClass = 'text-amber-400';

        row.innerHTML = `
            <td class="text-center p-2">
                ${wp.hex ? `
                    <button type="button"
                            class="btn btn-ghost btn-xs text-[10px] uppercase font-bold tracking-widest text-slate-400 hover:text-emerald-400 hover:bg-emerald-500/10 flex items-center gap-1.5"
                            data-action="route-map#jump"
                            data-route-map-target="button"
                            data-hex="${wp.hex}"
                            data-sector="${wp.sector || ''}">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                        MAP
                    </button>
                ` : '<span class="opacity-30">—</span>'}
            </td>
            <td class="font-mono text-xs p-2 text-slate-500">${wp.position}</td>
            <td class="font-mono text-xs p-2 border-r border-slate-800/50 font-bold text-emerald-400">${wp.hex}</td>
            <td class="font-mono text-xs p-2">${wp.sector || '—'}</td>
            <td class="font-mono text-xs p-2 font-bold ${zoneClass}">${wp.world || '—'}</td>
            <td class="font-mono text-xs p-2">${wp.uwp || '—'}</td>
            <td class="font-mono text-xs p-2 text-right text-emerald-300">${wp.jumpDistance || '—'}</td>
            <td class="text-center p-2">
                <button type="button"
                        class="btn btn-ghost btn-xs text-red-400 hover:text-red-300 hover:bg-red-500/10"
                        data-action="route-waypoint-modal#deleteWaypoint"
                        data-waypoint-id="${wp.id}">
                    ✕
                </button>
            </td>
        `;
        this.tableBodyTarget.appendChild(row);
    }

    // Nasconde la riga empty
    hideEmptyRow() {
        if (this.hasEmptyRowTarget) {
            this.emptyRowTarget.classList.add('hidden');
        }
    }

    // Controlla se tabella è vuota
    checkEmptyTable() {
        const rows = this.tableBodyTarget.querySelectorAll('tr:not(.empty-row)');
        if (rows.length === 0 && this.hasEmptyRowTarget) {
            this.emptyRowTarget.classList.remove('hidden');
        }
    }

    // Ricalcola la rotta usando pathfinding
    async recalculate(event) {
        event.preventDefault();

        if (!confirm('Ricalcolare la rotta? I waypoint verranno riordinati e ne verranno aggiunti di nuovi se necessario.')) {
            return;
        }

        try {
            const response = await fetch(this.recalculateUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Errore nel ricalcolo');
            }
        } catch (e) {
            console.error('Recalculate failed:', e);
            alert('Errore di rete');
        }
    }
}
