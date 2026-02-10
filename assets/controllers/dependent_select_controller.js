import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['source', 'destination'];
    static values = {
        url: String,
    };

    connect() {
        // Se la destinazione è un TomSelect, inizializzazione specifica potrebbe essere necessaria
        // ma di solito TomSelect intercetta i cambiamenti dell'elemento select originale.
    }

    async change(event) {
        const sector = event.target.value;
        const select = this.destinationTarget;

        if (!sector) {
            this.clearDestination();
            return;
        }

        try {
            // Se TomSelect è presente, mostriamo uno stato di caricamento magari?
            if (select.tomselect) {
                select.tomselect.lock();
            }

            const response = await fetch(`${this.urlValue}?sector=${encodeURIComponent(sector)}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const worlds = await response.json();
            this.updateDestination(worlds);
        } catch (error) {
            console.error('Failed to fetch worlds:', error);
            this.clearDestination();
        } finally {
            if (select.tomselect) {
                select.tomselect.unlock();
            }
        }
    }

    updateDestination(worlds) {
        const select = this.destinationTarget;
        const currentVal = select.value;

        // Pulisci select originale
        select.innerHTML = '<option value="">// SELECT WORLD</option>';
        worlds.forEach(world => {
            const option = document.createElement('option');
            option.value = world.value;
            option.text = world.label;
            select.appendChild(option);
        });

        // Abilita la select
        select.disabled = false;

        // Se è un TomSelect, dobbiamo notificarlo e abilitarlo
        if (select.tomselect) {
            select.tomselect.enable();
            select.tomselect.clearOptions();
            select.tomselect.addOptions(worlds.map(w => ({ text: w.label, value: w.value })));
            if (currentVal) {
                select.tomselect.setValue(currentVal);
            }
            select.tomselect.refreshOptions(false);
        }

        // Se abbiamo il controller Stimulus dedicato, usiamolo per l'estetica
        const tsController = this.application.getControllerForElementAndIdentifier(select, 'tom-select');
        if (tsController) {
            tsController.enable();
        }

        if (currentVal) {
            select.value = currentVal;
        }
    }

    clearDestination() {
        const select = this.destinationTarget;
        select.innerHTML = '<option value="">// SELECT WORLD</option>';
        select.disabled = true;

        if (select.tomselect) {
            select.tomselect.clearOptions();
            select.tomselect.disable();
            select.tomselect.refreshOptions(false);
        }

        const tsController = this.application.getControllerForElementAndIdentifier(select, 'tom-select');
        if (tsController) {
            tsController.disable();
        }
    }
}
