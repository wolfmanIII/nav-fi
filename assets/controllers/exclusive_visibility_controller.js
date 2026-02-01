import { Controller } from '@hotwired/stimulus';

/*
 * Nasconde i target quando il trigger ha un valore selezionato.
 * Utile per campi "Esclusivi": Se selezioni A, nascondi blocco creazione B.
 */
export default class extends Controller {
    static targets = ['trigger', 'target'];

    connect() {
        this.check();
    }

    check() {
        const hasValue = this.triggerTarget.value !== '';

        this.targetTargets.forEach(el => {
            if (hasValue) {
                el.classList.add('hidden');
            } else {
                el.classList.remove('hidden');
            }
        });
    }
}
