import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['select', 'chips'];

    connect() {
        if (!this.hasSelectTarget || !this.hasChipsTarget) {
            return;
        }

        this.boundReset = this.resetSelection.bind(this);
        this.boundOnShow = this.prepareSelection.bind(this);
        this.prepareSelection();
        this.renderChips();

        this.element?.addEventListener('close', this.boundReset);
        this.element?.addEventListener('cancel', this.boundReset);
        this.element?.addEventListener('show', this.boundOnShow);
    }

    disconnect() {
        this.element?.removeEventListener('close', this.boundReset);
        this.element?.removeEventListener('cancel', this.boundReset);
        this.element?.removeEventListener('show', this.boundOnShow);
    }

    prepareSelection() {
        this.saveInitialSelection();
        this.renderChips();
    }

    saveInitialSelection() {
        if (!this.hasSelectTarget) {
            this.initialSelection = [];
            return;
        }

        this.initialSelection = Array.from(this.selectTarget.selectedOptions).map(option => option.value);
    }

    resetSelection() {
        if (!this.hasSelectTarget || !this.initialSelection) {
            return;
        }

        Array.from(this.selectTarget.options).forEach(option => {
            option.selected = this.initialSelection.includes(option.value);
        });
        this.renderChips();
    }

    renderChips() {
        if (!this.hasSelectTarget || !this.hasChipsTarget) {
            return;
        }

        this.chipsTarget.innerHTML = '';
        Array.from(this.selectTarget.options).forEach(option => {
            if (!option.value) {
                return;
            }

            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'btn btn-xs btn-outline flex items-center gap-1';
            if (option.selected) {
                chip.classList.add('btn-primary');
                chip.classList.remove('btn-outline');
            }
            chip.textContent = option.textContent.trim();
            chip.dataset.value = option.value;
            chip.addEventListener('click', () => this.toggleRole(option.value));
            this.chipsTarget.appendChild(chip);
        });
    }

    toggleRole(value) {
        const option = Array.from(this.selectTarget.options).find(opt => opt.value === value);
        if (!option) {
            return;
        }

        option.selected = !option.selected;
        this.renderChips();
    }
}
