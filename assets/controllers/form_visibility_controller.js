import { Controller } from '@hotwired/stimulus';

/*
 * Gestisce la visibilità di sezioni del form in base al valore di un input trigger (es. Asset).
 * Uso:
 * <div data-controller="form-visibility">
 *    <select data-form-visibility-target="trigger" data-action="change->form-visibility#toggle">...</select>
 *    <div data-form-visibility-target="container">...</div>
 * </div>
 */
export default class extends Controller {
    static targets = ['trigger', 'container'];

    connect() {
        this.toggle();
    }

    toggle() {
        const trigger = this.triggerTarget;
        const hasValue = trigger.value !== '';

        this.containerTargets.forEach(container => {
            // Usa una classe utility per nascondere o lo stile inline
            if (hasValue) {
                container.classList.remove('hidden');
                // Se era nascosto con stile inline (es. da logica precedente), rimuovilo
                container.style.display = '';
            } else {
                container.classList.add('hidden');
            }
        });
    }
}
