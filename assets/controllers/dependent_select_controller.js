import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['source', 'destination', 'loader', 'full'];
    static values = { url: String };

    connect() {
        if (this.hasSourceTarget && this.sourceTarget.value) {
            this.syncFull();
            // Fetch mondi se abbiamo un settore ma la lista mondi è ridotta (modalità Edit)
            if (this.hasDestinationTarget && this.destinationTarget.options.length <= 2) {
                this.change({ target: this.sourceTarget });
            }
        }
    }

    async change(event) {
        const sector = event.target.value;
        if (!sector) {
            this.clearDestination();
            this.syncFull();
            return;
        }

        this.setLoading(true);
        try {
            const fetchUrl = `${this.urlValue}?sector=${encodeURIComponent(sector)}`;
            const response = await fetch(fetchUrl);
            const worlds = await response.json();
            this.updateDestination(worlds);
            this.syncFull();
        } catch (error) {
            this.syncFull();
        } finally {
            this.setLoading(false);
        }
    }

    syncFull() {
        if (!this.hasFullTarget) return;
        const sectorVal = this.hasSourceTarget ? this.sourceTarget.value : '';
        const worldSelect = this.hasDestinationTarget ? this.destinationTarget : null;
        let worldLabel = '';

        if (sectorVal && worldSelect && worldSelect.value) {
            const selectedOpt = worldSelect.options[worldSelect.selectedIndex];
            // Pulizia label: togliamo l'Hex e i prefissi per avere "Regina" pulito
            worldLabel = selectedOpt ? selectedOpt.text.split(' (')[0].replace('// ', '').trim() : worldSelect.value;
            this.fullTarget.value = `${sectorVal} // ${worldLabel}`;
        } else if (sectorVal) {
            this.fullTarget.value = `${sectorVal} // ...`;
        } else {
            this.fullTarget.value = '';
        }
        this.fullTarget.dispatchEvent(new Event('input', { bubbles: true }));
    }

    setLoading(state) {
        this.element.classList.toggle('is-loading', state);
    }

    updateDestination(worlds) {
        if (!this.hasDestinationTarget) return;
        const select = this.destinationTarget;

        // Salviamo il valore corrente (che potrebbe essere il NOME dal DB o l'HEX dall'API)
        const currentVal = select.value;
        const currentText = select.options[select.selectedIndex]?.text || '';

        select.innerHTML = '<option value="">// SELECT WORLD</option>';
        if (Array.isArray(worlds)) {
            worlds.forEach(world => {
                const option = document.createElement('option');
                option.value = world.value; // Hex
                option.text = world.label; // Nome (Hex) ...

                // Match corazzato: per valore esatto OR se la label inizia con il valore salvato (es "Regina")
                if (world.value === currentVal || (currentVal && world.label.startsWith(currentVal)) || (currentText && world.label.startsWith(currentText))) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
        select.disabled = false;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    clearDestination() {
        if (!this.hasDestinationTarget) return;
        const select = this.destinationTarget;
        select.innerHTML = '<option value="">// SELECT WORLD</option>';
        select.disabled = true;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
