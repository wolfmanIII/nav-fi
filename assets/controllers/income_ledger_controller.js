import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['asset', 'financialAccount', 'newLedgerSection'];

    connect() {
        this.syncAccountWithAsset();
    }

    onAssetChange(event) {
        this.syncAccountWithAsset();
    }

    onAccountChange() {
        this.updateLedgerVisibility();
    }

    syncAccountWithAsset() {
        const select = this.assetTarget;
        // If no asset selected, assume everything unlocked or empty
        if (!select.value) {
            this.financialAccountTarget.disabled = false;
            this.updateLedgerVisibility();
            return;
        }

        const selectedOption = select.options[select.selectedIndex];
        const accountId = selectedOption.dataset.financialAccountId;

        if (accountId) {
            // Asset has an account -> Lock it
            this.financialAccountTarget.value = accountId;
            this.financialAccountTarget.disabled = true;
        } else {
            // Asset has no account -> Unlock to allow manual selection or creation
            // Make sure we don't clear it if user manually selected one? 
            // Logic says: if Asset has no account, user MUST provide one (existing orphan or new)
            // So we default to empty if the PREVIOUS asset had forced a lock?
            // Safer to clear if we were locked.
            if (this.financialAccountTarget.disabled) {
                this.financialAccountTarget.value = '';
                this.financialAccountTarget.disabled = false;
            }
        }

        this.updateLedgerVisibility();
    }

    updateLedgerVisibility() {
        // Check value (even if disabled)
        const hasAccount = this.financialAccountTarget.value !== '';

        if (hasAccount) {
            this.newLedgerSectionTarget.classList.add('hidden');
        } else {
            this.newLedgerSectionTarget.classList.remove('hidden');
        }
    }
}
