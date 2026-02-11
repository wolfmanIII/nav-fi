import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['source', 'destination'];
    static values = {
        url: String,
    };

    connect() {
    }

    async change(event) {
        const sector = event.target.value;

        if (!sector) {
            this.clearDestination();
            return;
        }

        this.setLoading(true);

        try {
            const response = await fetch(`${this.urlValue}?sector=${encodeURIComponent(sector)}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const worlds = await response.json();
            this.updateDestination(worlds);
        } catch (error) {
            console.error('Failed to fetch worlds:', error);
            this.clearDestination();
        } finally {
            this.setLoading(false);
        }
    }

    setLoading(state) {
        this.element.classList.toggle('is-loading', state);
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

        if (currentVal) {
            select.value = currentVal;
            // Trigger change event to sync with other controllers
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    clearDestination() {
        const select = this.destinationTarget;
        select.innerHTML = '<option value="">// SELECT WORLD</option>';
        select.disabled = true;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
