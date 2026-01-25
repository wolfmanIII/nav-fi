import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['unit', 'total', 'quantity'];

    connect() {
        // Initialize if total has value
        this.calculateUnit();
    }

    calculateTotal() {
        const unit = parseFloat(this.unitTarget.value);
        const qty = parseFloat(this.quantityTarget.dataset.value);

        if (!isNaN(unit) && !isNaN(qty)) {
            this.totalTarget.value = Math.round(unit * qty);
        }
    }

    calculateUnit() {
        const total = parseFloat(this.totalTarget.value);
        const qty = parseFloat(this.quantityTarget.dataset.value);

        if (!isNaN(total) && !isNaN(qty) && qty > 0) {
            this.unitTarget.value = (total / qty).toFixed(0);
        }
    }
}
