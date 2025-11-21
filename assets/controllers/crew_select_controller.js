import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['selectAll', 'item'];

    // Header checkbox → seleziona/deseleziona tutti
    toggleAll(event) {
        const checked = event.target.checked;
        this.itemTargets.forEach(cb => cb.checked = checked);
    }
}
