import { Controller } from '@hotwired/stimulus';

/*
 * Controls a generic confirmation modal.
 * 
 * Usage:
 * 1. Place the modal HTML anywhere (e.g., footer) with `data-controller="confirm-modal"`
 * 2. On trigger buttons, add `data-action="confirm-modal#open"` and `data-confirm-url-param="/path/to/action"`.
 *    Optionally pass `data-confirm-message-param="Are you sure?"`.
 */
export default class extends Controller {
    static targets = ['dialog', 'form', 'message'];
    static values = {
        defaultMessage: String
    };

    connect() {
        // Initialize if needed
    }

    open(event) {
        event.preventDefault();

        const params = event.params;
        const url = params.url;
        const message = params.message || this.defaultMessageValue;
        const token = params.token;

        if (!url) {
            console.error('ConfirmModal: No URL provided in data-confirm-modal-url-param');
            return;
        }

        // Update Form Action
        if (this.hasFormTarget) {
            this.formTarget.action = url;

            // Handle Token if provided (optional dynamic token injection)
            if (token) {
                let tokenInput = this.formTarget.querySelector('input[name="_token"]');
                if (!tokenInput) {
                    tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    this.formTarget.appendChild(tokenInput);
                }
                tokenInput.value = token;
            }
        }

        // Update Message
        if (this.hasMessageTarget) {
            this.messageTarget.textContent = message || "Are you sure you want to proceed?";
        }

        // Show Modal
        this.dialogTarget.showModal();
    }

    close(event) {
        if (event) event.preventDefault();
        this.dialogTarget.close();
    }
}
