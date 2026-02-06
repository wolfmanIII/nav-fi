import { Controller } from '@hotwired/stimulus';

/**
 * Route Waypoints Controller
 * Manages waypoint collection via table display + modal form.
 */
export default class extends Controller {
    static targets = [
        'tableBody',
        'emptyRow',
        'modal',
        'modalTitle',
        'inputHex',
        'inputSector',
        'inputWorld',
        'inputUwp',
        'inputNotes',
        'rowPrototype',
        'inputsPrototype'
    ];

    static values = {
        lookupUrl: String,
    };

    editingIndex = null; // null = adding new, number = editing existing

    // ----- MODAL OPERATIONS -----

    openModal(event) {
        event?.preventDefault();
        this.editingIndex = null;
        this.clearModalInputs();
        this.modalTitleTarget.textContent = 'Add Waypoint';
        this.modalTarget.showModal();
    }

    closeModal() {
        this.modalTarget.close();
    }

    clearModalInputs() {
        this.inputHexTarget.value = '';
        this.inputSectorTarget.value = '';
        this.inputWorldTarget.value = '';
        this.inputUwpTarget.value = '';
        this.inputNotesTarget.value = '';
    }

    // ----- SAVE WAYPOINT -----

    saveWaypoint() {
        const hex = this.inputHexTarget.value.trim();
        const sector = this.inputSectorTarget.value.trim();
        const world = this.inputWorldTarget.value.trim();
        const uwp = this.inputUwpTarget.value.trim();
        const notes = this.inputNotesTarget.value.trim();

        if (this.editingIndex !== null) {
            // Update existing row
            this.updateRow(this.editingIndex, { hex, sector, world, uwp, notes });
        } else {
            // Add new row
            this.addRow({ hex, sector, world, uwp, notes });
        }

        this.closeModal();
    }

    // ----- EDIT WAYPOINT -----

    editWaypoint(event) {
        event.preventDefault();
        const index = event.currentTarget.dataset.index;
        const row = this.tableBodyTarget.querySelector(`tr[data-waypoint-index="${index}"]`);
        if (!row) return;

        // Get values from hidden inputs
        const inputs = row.querySelector('td.hidden');
        const hexInput = inputs?.querySelector('[id$="_hex"]');
        const sectorInput = inputs?.querySelector('[id$="_sector"]');
        const worldInput = inputs?.querySelector('[id$="_world"]');
        const uwpInput = inputs?.querySelector('[id$="_uwp"]');
        const notesInput = inputs?.querySelector('[id$="_notes"]');

        this.inputHexTarget.value = hexInput?.value ?? '';
        this.inputSectorTarget.value = sectorInput?.value ?? '';
        this.inputWorldTarget.value = worldInput?.value ?? '';
        this.inputUwpTarget.value = uwpInput?.value ?? '';
        this.inputNotesTarget.value = notesInput?.value ?? '';

        this.editingIndex = index;
        this.modalTitleTarget.textContent = 'Edit Waypoint';
        this.modalTarget.showModal();
    }

    // ----- REMOVE WAYPOINT -----

    removeWaypoint(event) {
        event.preventDefault();
        const index = event.currentTarget.dataset.index;
        const row = this.tableBodyTarget.querySelector(`tr[data-waypoint-index="${index}"]`);
        if (row) {
            row.remove();
            this.renumberRows();
            this.showEmptyRowIfNeeded();
        }
    }

    // ----- ADD NEW ROW -----

    addRow({ hex, sector, world, uwp, notes }) {
        // Remove empty row if present
        if (this.hasEmptyRowTarget) {
            this.emptyRowTarget.remove();
        }

        const nextIndex = this.getNextIndex();
        const rowHtml = this.rowPrototypeTarget.innerHTML
            .replace(/__INDEX__/g, nextIndex)
            .replace(/__NUM__/g, this.tableBodyTarget.querySelectorAll('tr[data-waypoint-index]').length + 1);

        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = rowHtml.trim();
        const newRow = wrapper.firstElementChild;

        // Set display cells
        newRow.querySelector('[data-cell="hex"]').textContent = hex;
        newRow.querySelector('[data-cell="sector"]').textContent = sector;
        newRow.querySelector('[data-cell="world"]').textContent = world;
        newRow.querySelector('[data-cell="uwp"]').textContent = uwp;
        newRow.querySelector('[data-cell="jump"]').textContent = '—'; // Calculated on save

        // Create hidden inputs from prototype
        const inputsHtml = this.inputsPrototypeTarget.innerHTML.replace(/__name__/g, nextIndex);
        const inputsWrapper = document.createElement('div');
        inputsWrapper.innerHTML = inputsHtml.trim();

        const inputsCell = newRow.querySelector('[data-cell="inputs"]');
        inputsCell.innerHTML = '';
        inputsCell.append(...inputsWrapper.children);

        // Set hidden input values (jumpDistance left empty - calculated server-side)
        inputsCell.querySelector('[id$="_hex"]').value = hex;
        inputsCell.querySelector('[id$="_sector"]').value = sector;
        inputsCell.querySelector('[id$="_world"]').value = world;
        inputsCell.querySelector('[id$="_uwp"]').value = uwp;
        inputsCell.querySelector('[id$="_notes"]').value = notes;
        inputsCell.querySelector('[id$="_position"]').value = nextIndex;

        this.tableBodyTarget.appendChild(newRow);
    }

    // ----- UPDATE EXISTING ROW -----

    updateRow(index, { hex, sector, world, uwp, notes }) {
        const row = this.tableBodyTarget.querySelector(`tr[data-waypoint-index="${index}"]`);
        if (!row) return;

        // Update display cells
        const cells = row.querySelectorAll('td');
        cells[1].textContent = hex;  // Hex
        cells[2].textContent = sector;  // Sector
        cells[3].textContent = world;  // World
        cells[4].textContent = uwp;  // UWP
        // cells[5] = Jump - not updated, calculated server-side

        // Update hidden inputs (jumpDistance not touched - calculated server-side)
        const inputs = row.querySelector('td.hidden');
        inputs.querySelector('[id$="_hex"]').value = hex;
        inputs.querySelector('[id$="_sector"]').value = sector;
        inputs.querySelector('[id$="_world"]').value = world;
        inputs.querySelector('[id$="_uwp"]').value = uwp;
        inputs.querySelector('[id$="_notes"]').value = notes;
    }

    // ----- HELPERS -----

    getNextIndex() {
        const rows = this.tableBodyTarget.querySelectorAll('tr[data-waypoint-index]');
        let maxIndex = -1;
        rows.forEach(row => {
            const idx = parseInt(row.dataset.waypointIndex, 10);
            if (idx > maxIndex) maxIndex = idx;
        });
        return maxIndex + 1;
    }

    renumberRows() {
        const rows = this.tableBodyTarget.querySelectorAll('tr[data-waypoint-index]');
        rows.forEach((row, i) => {
            row.querySelector('td:first-child').textContent = i + 1;
        });
    }

    showEmptyRowIfNeeded() {
        const rows = this.tableBodyTarget.querySelectorAll('tr[data-waypoint-index]');
        if (rows.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.setAttribute('data-route-waypoints-target', 'emptyRow');
            emptyRow.innerHTML = '<td colspan="7" class="text-center text-slate-500 italic py-4">No waypoints defined. Click "Add Waypoint" to begin.</td>';
            this.tableBodyTarget.appendChild(emptyRow);
        }
    }

    // ----- LOOKUP (Auto-fill via API) -----

    async lookup(event) {
        if (!this.hasLookupUrlValue) return;

        const hex = this.inputHexTarget.value.trim();
        const sector = this.inputSectorTarget.value.trim();
        if (!hex || !sector) return;

        const url = `${this.lookupUrlValue}?hex=${encodeURIComponent(hex)}&sector=${encodeURIComponent(sector)}`;
        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            if (!data?.found) return;

            if (!this.inputWorldTarget.value) {
                this.inputWorldTarget.value = data.world ?? '';
            }
            if (!this.inputUwpTarget.value) {
                this.inputUwpTarget.value = data.uwp ?? '';
            }
        } catch (error) {
            // Silently fail to avoid blocking user input
        }
    }
}
