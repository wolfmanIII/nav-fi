import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dialog', 'title', 'message', 'confirmButton', 'cancelButton'];

    connect() {
        // Expose globally for easy access from other controllers
        window.NavFiConfirmation = this;
    }

    /**
     * Shows the confirmation modal.
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

        // Reset button styles
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
