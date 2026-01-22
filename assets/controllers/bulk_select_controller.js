import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['selectAll', 'item'];

    // Checkbox intestazione → seleziona/deseleziona tutti gli item collegati
    toggleAll(event) {
        const checked = event.target.checked;
        this.itemTargets.forEach(cb => cb.checked = checked);
    }
}
