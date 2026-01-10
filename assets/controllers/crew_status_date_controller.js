import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['status', 'active', 'onLeave', 'retired', 'mia', 'deceased'];

    connect() {
        this.boundRefresh = this.toggle.bind(this);
        this.element.addEventListener('crew-status-date:refresh', this.boundRefresh);
        this.toggle();
    }

    disconnect() {
        this.element.removeEventListener('crew-status-date:refresh', this.boundRefresh);
    }

    toggle() {
        if (!this.hasStatusTarget) {
            return;
        }

        if (!this.isShipActive()) {
            this.statusTarget.required = false;
            this.clearRequiredDates();
            this.hideAll();
            return;
        }

        this.statusTarget.required = true;

        const value = this.statusTarget.value || '';
        const key = this.normalizeStatus(value);

        this.hideAll();
        this.clearRequiredDates();
        if (!key) {
            return;
        }

        const targetName = this.statusToTarget(key);
        if (!targetName) {
            return;
        }

        const target = this[`${targetName}Target`];
        if (!target) {
            return;
        }

        target.classList.remove('hidden');
        this.setDateRequired(target, true);
    }

    hideAll() {
        [
            this.activeTarget,
            this.onLeaveTarget,
            this.retiredTarget,
            this.miaTarget,
            this.deceasedTarget,
        ].forEach((target) => {
            if (target) {
                target.classList.add('hidden');
            }
        });
    }

    clearRequiredDates() {
        [
            this.activeTarget,
            this.onLeaveTarget,
            this.retiredTarget,
            this.miaTarget,
            this.deceasedTarget,
        ].forEach((target) => this.setDateRequired(target, false));
    }

    setDateRequired(target, required) {
        if (!target) {
            return;
        }

        const display = target.querySelector('[data-imperial-date-target="display"]');
        if (display) {
            display.required = required;
        }
    }

    isShipActive() {
        return this.element.dataset.shipActive === 'true';
    }

    normalizeStatus(value) {
        return value.trim().toLowerCase();
    }

    statusToTarget(value) {
        switch (value) {
            case 'active':
                return 'active';
            case 'on leave':
                return 'onLeave';
            case 'retired':
                return 'retired';
            case 'missing (mia)':
                return 'mia';
            case 'deceased':
                return 'deceased';
            default:
                return '';
        }
    }
}
