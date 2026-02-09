import * as TurboModule from '@hotwired/turbo';

const Turbo = TurboModule.default ?? TurboModule.Turbo ?? window.Turbo;

if (!Turbo) {
    console.error('Errore Sistema Nav-Fi: Turbo Drive non rilevato. Override conferme disabilitato.');
} else {
    console.log('Sistema Nav-Fi: Override Turbo Drive attivato.');
}

/**
 * Sovrascrive il metodo di conferma predefinito di Turbo per utilizzare il nostro Modale personalizzato.
 * Supporta attributi data aggiuntivi sull'elemento scatenante:
 * - data-confirm-title: Titolo del modale
 * - data-confirm-type: 'neutral', 'warning', 'danger' (default: neutral)
 * - data-confirm-text: Etichetta pulsante conferma
 * - data-confirm-cancel: Etichetta pulsante annulla
 */
Turbo.setConfirmMethod((message, element) => {
    if (window.NavFiConfirmation) {
        // Estrai opzioni di personalizzazione dal dataset dell'elemento
        const title = element.dataset.confirmTitle || 'Confirmation Required';
        const type = element.dataset.confirmType || 'neutral';
        const confirmText = element.dataset.confirmText || 'Confirm';
        const cancelText = element.dataset.confirmCancel || 'Cancel';

        return window.NavFiConfirmation.confirm({
            title: title,
            message: message,
            confirmText: confirmText,
            cancelText: cancelText,
            type: type
        });
    }

    console.warn('Avviso Sistema Nav-Fi: Controller Conferma non connesso. Fallback su alert di sistema.');
    // Fallback sull'alert nativo del browser se il nostro controller non è pronto
    return Promise.resolve(window.confirm(message));
});

