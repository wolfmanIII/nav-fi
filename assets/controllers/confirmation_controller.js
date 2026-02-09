import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dialog', 'title', 'message', 'confirmButton', 'cancelButton'];

    connect() {
        // Espone globalmente per un facile accesso da altri controller
        window.NavFiConfirmation = this;
    }

    /**
     * Mostra il modale di conferma.
     * @param {Object} options
     * @param {string} options.title
     * @param {string} options.message
     * @param {string} options.confirmText
     * @param {string} options.cancelText
     * @param {string} options.type - 'neutral', 'danger', 'warning'
     * @returns {Promise<boolean>}
     */
    async confirm({ title = 'Confirm Action', message = 'Are you sure you want to proceed?', confirmText = 'Confirm', cancelText = 'Cancel', type = 'neutral' } = {}) {
        this.titleTarget.textContent = title;
        this.messageTarget.textContent = message;
        this.confirmButtonTarget.textContent = confirmText;
        this.cancelButtonTarget.textContent = cancelText;

        // Ripristina stili pulsante
        this.confirmButtonTarget.className = 'btn btn-sm font-orbitron tracking-widest';

        if (type === 'danger') {
            this.confirmButtonTarget.classList.add('btn-error', 'text-white');
        } else if (type === 'warning') {
            this.confirmButtonTarget.classList.add('btn-warning', 'text-black');
        } else {
            this.confirmButtonTarget.classList.add('btn-primary', 'text-white');
        }

        this.dialogTarget.showModal();

        return new Promise((resolve) => {
            this.resolvePromise = resolve;
        });
    }

    confirmAction() {
        this.dialogTarget.close();
        if (this.resolvePromise) this.resolvePromise(true);
    }

    cancelAction() {
        this.dialogTarget.close();
        if (this.resolvePromise) this.resolvePromise(false);
    }
}
