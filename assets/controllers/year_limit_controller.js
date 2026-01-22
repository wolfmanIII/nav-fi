import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        default: Number,
    };

    connect() {
        this.apply();
    }

    onAssetChange() {
        this.apply();
    }

    apply() {
        const defaultMin = Number.isFinite(this.defaultValue) ? this.defaultValue : 0;
        let min = defaultMin;

        const option = this.element instanceof HTMLSelectElement
            ? this.element.options[this.element.selectedIndex]
            : null;

        const startYearAttr = option?.dataset.startYear;
        const startYear = startYearAttr !== undefined && startYearAttr !== ''
            ? parseInt(startYearAttr, 10)
            : NaN;

        if (!Number.isNaN(startYear)) {
            min = Math.max(defaultMin, startYear);
        }

        // Trova tutti gli input anno nello stesso form
        const form = this.element.closest('form');
        if (!form) {
            return;
        }

        // Trova tutti gli input con nome che termina in [year] che hanno data-min-year
        const yearInputs = form.querySelectorAll('input[name$="[year]"][data-min-year]');

        yearInputs.forEach((input) => {
            input.min = min;
            input.setAttribute('data-min-year', min);

            // Trova il container del controller imperial-date e invia l'evento
            const imperialDateContainer = input.closest('[data-controller*="imperial-date"]');
            if (imperialDateContainer) {
                imperialDateContainer.dispatchEvent(new CustomEvent('year-limits-changed', {
                    detail: { min, max: input.dataset.maxYear }
                }));
            }

            if (input.value && Number(input.value) < min) {
                input.value = min;
            }
        });
    }
}
