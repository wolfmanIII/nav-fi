import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['content', 'summary', 'item'];

    connect() {
        // Se i dettagli sono aperti in modo predefinito, mostra subito i figli
        if (this.element.hasAttribute('open')) {
            this.itemTargets.forEach(item => {
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
                item.classList.remove('reveal-item');
            });
        }
    }

    toggle() {
        const isOpen = this.element.hasAttribute('open');

        if (isOpen) {
            // Verifica se li stiamo già mostrando (es. da connect)
            if (this.itemTargets[0] && this.itemTargets[0].style.opacity === '1') return;

            this.showScanningEffect();
        } else {
            // In chiusura, li nascondiamo così la PROSSIMA volta potranno animarsi
            this.itemTargets.forEach(item => {
                item.style.opacity = '0';
                item.classList.remove('reveal-item');
            });
        }
    }

    showScanningEffect() {
        // Crea la linea di scansione
        const scanner = document.createElement('div');
        scanner.className = 'scanning-line';
        this.summaryTarget.appendChild(scanner);

        // Simulazione testo "Accessing..."
        const originalText = this.summaryTarget.textContent;
        this.summaryTarget.classList.add('animate-[flicker_0.5s_infinite]');

        setTimeout(() => {
            scanner.remove();
            this.summaryTarget.classList.remove('animate-[flicker_0.5s_infinite]');
            this.revealChildren();
        }, 600);
    }

    revealChildren() {
        this.itemTargets.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.08}s`;
            item.classList.add('reveal-item');
        });
    }
}
