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
        'inputWorldChoice',
        'inputUwp',
        'inputNotes',
        'tableBody',
        'emptyRow',
        'invalidJumpAlert',
        'loadingOverlay'
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
        if (this.hasInputWorldChoiceTarget) this.inputWorldChoiceTarget.value = '';
        if (this.hasInputWorldTarget) this.inputWorldTarget.value = '';
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
                if (this.hasInputWorldTarget) this.inputWorldTarget.value = data.world || '';
                this.inputUwpTarget.value = data.uwp || '';
            } else {
                if (this.hasInputWorldTarget) this.inputWorldTarget.value = '';
                this.inputUwpTarget.value = '';
            }
        } catch (e) {
            console.error('Lookup failed:', e);
        }
    }

    /**
     * Sincronizza l'hex quando viene scelto un mondo dal dropdown
     */
    onWorldChoiceChange(event) {
        const hex = event.target.value;
        if (hex) {
            this.inputHexTarget.value = hex;
            this.lookup();
        }
    }

    // Submit nuovo waypoint
    async submit(event) {
        event.preventDefault();

        const hex = this.inputHexTarget.value.trim().toUpperCase();
        const sector = this.inputSectorTarget.value.trim();

        if (!hex || !sector) {
            window.NavFiToast.notify('Hex and Sector are required', 'warning');
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
                this.toggleInvalidJumpAlert(data.hasInvalidJumps);
                window.NavFiToast.notify('Waypoint added successfully', 'success');
            } else {
                window.NavFiToast.notify(data.error || 'Error saving waypoint', 'error');
            }
        } catch (e) {
            console.error('Submit failed:', e);
            window.NavFiToast.notify('Network error', 'error');
        }
    }

    // Rimuove waypoint
    async deleteWaypoint(event) {
        const waypointId = event.currentTarget.dataset.waypointId;
        const row = event.currentTarget.closest('tr');

        const confirmed = await window.NavFiConfirmation.confirm({
            title: 'Remove Waypoint',
            message: 'Are you sure you want to remove this waypoint from the route?',
            confirmText: 'Remove',
            type: 'danger'
        });

        if (!confirmed) return;

        try {
            const url = this.deleteUrlValue.replace('__ID__', waypointId);
            const response = await fetch(url, { method: 'DELETE' });
            const data = await response.json();

            if (data.success) {
                row.remove();
                this.checkEmptyTable();
                this.toggleInvalidJumpAlert(data.hasInvalidJumps);

                // Update remaining waypoints (position & distance)
                if (data.updatedWaypoints) {
                    data.updatedWaypoints.forEach(wp => {
                        const btn = this.tableBodyTarget.querySelector(`button[data-waypoint-id="${wp.id}"]`);
                        if (btn) {
                            const wpRow = btn.closest('tr');
                            if (wpRow) {
                                // Update Position (Column 1)
                                wpRow.cells[1].textContent = wp.position;
                                // Update Distance (Column 6)
                                wpRow.cells[6].textContent = wp.jumpDistance !== null ? wp.jumpDistance : '—';
                            }
                        }
                    });
                }

                window.NavFiToast.notify('Waypoint removed', 'success');
            } else {
                window.NavFiToast.notify(data.error || 'Error removing waypoint', 'error');
            }
        } catch (e) {
            console.error('Delete failed:', e);
            window.NavFiToast.notify('Network error', 'error');
        }
    }

    // Mostra/Nasconde alert salti invalidi
    toggleInvalidJumpAlert(show) {
        if (this.hasInvalidJumpAlertTarget) {
            if (show) {
                this.invalidJumpAlertTarget.classList.remove('hidden');
            } else {
                this.invalidJumpAlertTarget.classList.add('hidden');
            }
        }
    }

    // Aggiunge riga alla tabella
    addRowToTable(wp) {
        const row = document.createElement('tr');
        row.className = 'hover:bg-emerald-500/5 transition-colors text-slate-300 border-b border-slate-800/50';

        // Determina classe badge per zona
        let badgeClass = 'badge-ghost text-slate-300';
        if (wp.zone === 'R') badgeClass = 'badge-error text-white';
        else if (wp.zone === 'A') badgeClass = 'badge-warning text-black';

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
            <td class="font-mono text-xs p-2 text-slate-500 text-center">${wp.position}</td>
            <td class="font-mono text-xs p-2 border-r border-slate-800/50 font-bold text-emerald-400">${wp.hex}</td>
            <td class="font-mono text-xs p-2">${wp.sector || '—'}</td>
            <td class="p-2">
                <span class="badge badge-sm font-mono font-bold ${badgeClass}">
                    ${wp.world || '—'}
                </span>
            </td>
            <td class="font-mono text-xs p-2">${wp.uwp || '—'}</td>
            <td class="font-mono text-xs p-2 text-right text-emerald-300">${wp.jumpDistance || '—'}</td>
            <td class="text-center p-2">
                <button type="button"
                        class="btn btn-ghost btn-xs text-red-500 hover:text-red-400 hover:bg-red-500/10"
                        data-action="route-waypoint-modal#deleteWaypoint"
                        data-waypoint-id="${wp.id}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M8 6l1-3h6l1 3"></path></svg>
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

        const confirmed = await window.NavFiConfirmation.confirm({
            title: 'Recalculate Route',
            message: 'This will optimize the route path. Existing waypoints may be reordered or added. Continue?',
            confirmText: 'Recalculate',
            type: 'warning'
        });

        if (!confirmed) return;

        // Show loading overlay
        if (this.hasLoadingOverlayTarget) {
            this.loadingOverlayTarget.classList.remove('hidden');
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
                window.NavFiToast.notify(data.error || 'Recalculation error', 'error');
                // Hide overlay on error
                if (this.hasLoadingOverlayTarget) {
                    this.loadingOverlayTarget.classList.add('hidden');
                }
            }
        } catch (e) {
            console.error('Recalculate failed:', e);
            window.NavFiToast.notify('Network error', 'error');
            // Hide overlay on error
            if (this.hasLoadingOverlayTarget) {
                this.loadingOverlayTarget.classList.add('hidden');
            }
        }
    }
}
