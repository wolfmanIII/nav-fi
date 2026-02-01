import { Controller } from '@hotwired/stimulus';

/*
 * Gestisce il blocco dei campi Financial Account se l'Asset selezionato ne ha già uno.
 * 
 * Target:
 * - asset: la select dell'Asset (deve avere data-financial-account-id nelle option)
 * - debitAccount: la select del FinancialAccount
 * - debitCreation: il container dei campi per creare nuovo conto (Bank/Name)
 */
export default class extends Controller {
    static targets = ['asset', 'debitAccount', 'debitCreation'];

    connect() {
        this.check();
    }

    check() {
        const assetSelect = this.assetTarget;
        const selectedOption = assetSelect.options[assetSelect.selectedIndex];

        // Se non c'è opzione selezionata o valore vuoto, resetta (unlock) e aggiorna visibilità
        if (!selectedOption || !assetSelect.value) {
            this.unlock();
            return;
        }

        const financialAccountId = selectedOption.dataset.financialAccountId;

        if (financialAccountId) {
            this.lock(financialAccountId);
        } else {
            this.unlock();
        }
    }

    onAccountChange() {
        this.updateVisibility();
    }

    lock(accountId) {
        const debitSelect = this.debitAccountTarget;

        // Imposta il valore
        debitSelect.value = accountId;

        // Disabilita la select (Backend gestirà il fallback se riceve null)
        debitSelect.disabled = true;
        debitSelect.classList.add('opacity-60', 'cursor-not-allowed');

        this.updateVisibility();
    }

    unlock() {
        const debitSelect = this.debitAccountTarget;

        // Abilita
        debitSelect.disabled = false;
        debitSelect.classList.remove('opacity-60', 'cursor-not-allowed');

        this.updateVisibility();
    }

    updateVisibility() {
        const hasAccount = this.debitAccountTarget.value !== '';
        this.debitCreationTargets.forEach(el => {
            if (hasAccount) {
                el.classList.add('hidden');
            } else {
                el.classList.remove('hidden');
            }
        });
    }
}
