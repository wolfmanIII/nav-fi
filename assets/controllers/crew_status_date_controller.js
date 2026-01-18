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
        if (!this.hasStatusTarget) return;

        const assetActive = this.isAssetActive();

        if (!assetActive) {
            this.statusTarget.required = false;
            this.clearRequiredDates();
            this.hideAll();
            return;
        }

        this.statusTarget.required = true;

        const value = this.statusTarget.value || '';
        const key = this.normalizeStatus(value);

        this.hideAll();

        if (!key) {
            this.clearRequiredDates();
            return;
        }

        const targetName = this.statusToTarget(key);
        if (!targetName) {
            this.clearRequiredDates();
            return;
        }

        if (this[`has${targetName.charAt(0).toUpperCase() + targetName.slice(1)}Target`]) {
            const target = this[`${targetName}Target`];
            target.classList.remove('hidden');
            this.setDateRequired(target, true);
        }
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

    isAssetActive() {
        return this.element.dataset.assetActive === 'true' || this.element.getAttribute('data-asset-active') === 'true';
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
