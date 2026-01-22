import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dateDisplay', 'yearInput', 'dayInput', 'help'];
    static values = {
        startingSelector: String,
    };

    connect() {
        this.startingField = this._findStartingField();
        this._toggle();
        if (this.startingField) {
            this.startingField.addEventListener('input', this._toggle);
        }
    }

    disconnect() {
        if (this.startingField) {
            this.startingField.removeEventListener('input', this._toggle);
        }
    }

    _findStartingField() {
        // Preferisce il selettore esplicito se fornito
        if (this.hasStartingSelectorValue) {
            try {
                const found = document.querySelector(this.startingSelectorValue);
                if (found) {
                    return found;
                }
            } catch (e) {
                // ignora
            }
        }

        // Alternativa: cerca un input startingYear nello stesso form
        const form = this.element.closest('form');
        if (form) {
            const candidate = form.querySelector("input[name$='[startingYear]']");
            if (candidate) {
                return candidate;
            }
        }
        return null;
    }

    _toggle = () => {
        // Abilita/disabilita il datepicker in base allo startingYear valorizzato
        const enabled = this.startingField && this.startingField.value !== '';

        this.dateDisplayTargets.forEach((el) => {
            el.toggleAttribute('disabled', !enabled);
            if (!enabled) {
                el.classList.add('opacity-60', 'cursor-not-allowed');
            } else {
                el.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        });

        // Aggiorna l'anno minimo per l'input anno in base allo startingYear
        if (enabled && this.startingField) {
            const startingYear = parseInt(this.startingField.value, 10);
            if (!isNaN(startingYear)) {
                this.yearInputTargets.forEach((el) => {
                    el.setAttribute('data-min-year', startingYear);
                    // Aggiorna anche l'attributo min per la validazione HTML5
                    el.setAttribute('min', startingYear);
                });
            }
        }

        if (!enabled) {
            this.yearInputTargets.forEach((el) => el.value = '');
            this.dayInputTargets.forEach((el) => el.value = '');
        }

        if (this.hasHelpTarget) {
            this.helpTarget.classList.toggle('hidden', enabled);
        }
    };
}
