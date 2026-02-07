import { Controller } from '@hotwired/stimulus';

/**
 * Route Waypoint Modal Controller
 * Gestisce l'aggiunta di waypoints dalla pagina route details.
 */
export default class extends Controller {
    static targets = [
        'modal',
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
        routeId: Number
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
        const notes = this.inputNotesTarget.value.trim();

        if (!hex || !sector) {
            alert('Hex e Sector sono obbligatori');
            return;
        }

        try {
            const response = await fetch(this.addUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ hex, sector, notes })
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
        row.innerHTML = `
            <td class="text-center p-2">
                <button type="button"
                        class="btn btn-ghost btn-xs text-[10px] uppercase font-bold tracking-widest text-red-400 hover:text-red-300 hover:bg-red-500/10"
                        data-action="route-waypoint-modal#deleteWaypoint"
                        data-waypoint-id="${wp.id}">
                    ✕
                </button>
            </td>
            <td class="font-mono text-xs p-2 text-slate-500">${wp.position}</td>
            <td class="font-mono text-xs p-2 border-r border-slate-800/50 font-bold text-emerald-400">${wp.hex}</td>
            <td class="font-mono text-xs p-2">${wp.sector || '—'}</td>
            <td class="font-mono text-xs p-2 font-bold">${wp.world || '—'}</td>
            <td class="font-mono text-xs p-2">${wp.uwp || '—'}</td>
            <td class="font-mono text-xs p-2 text-right text-emerald-300">${wp.jumpDistance || '—'}</td>
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
}
