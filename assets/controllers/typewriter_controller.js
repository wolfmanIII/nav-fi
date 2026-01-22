import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['line'];
    static values = {
        speed: { type: Number, default: 20 }, // ms per carattere
        lineDelay: { type: Number, default: 300 } // ms tra le righe
    };

    connect() {
        this.queue = this.lineTargets.map(target => {
            const text = target.textContent.trim(); // Usa textContent per leggere il testo nascosto

            // Svuota il contenuto
            target.textContent = '';

            // Rendi visibile ma vuoto
            target.style.visibility = 'visible';

            return { element: target, text: text };
        });

        if (this.queue.length > 0) {
            this.typeNextLine(0);
        }
    }

    typeNextLine(index) {
        if (index >= this.queue.length) return;

        const line = this.queue[index];

        this.typeText(line.element, line.text, 0, () => {
            // Se è la prima riga, rimuoviamo l'animazione pulse al termine
            if (index === 0) {
                line.element.classList.remove('animate-pulse');
            }

            // Se è l'ultima riga, aggiungiamo l'animazione pulse al termine
            if (index === this.queue.length - 1) {
                line.element.classList.add('animate-pulse');
            }

            setTimeout(() => {
                this.typeNextLine(index + 1);
            }, this.lineDelayValue);
        });
    }

    typeText(element, text, charIndex, callback) {
        if (charIndex >= text.length) {
            callback();
            return;
        }

        element.textContent += text.charAt(charIndex);

        // Variazione casuale per realismo
        const randomSpeed = this.speedValue + (Math.random() * 30 - 15);

        setTimeout(() => {
            this.typeText(element, text, charIndex + 1, callback);
        }, Math.max(5, randomSpeed));
    }
}
