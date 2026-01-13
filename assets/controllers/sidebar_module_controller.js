import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['content', 'summary', 'item'];

    connect() {
        // If the details is open by default, show children immediately
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
            // Check if we already show them (e.g. from connect)
            if (this.itemTargets[0] && this.itemTargets[0].style.opacity === '1') return;

            this.showScanningEffect();
        } else {
            // When closing, we hide them so that NEXT TIME they open, they can animate in
            this.itemTargets.forEach(item => {
                item.style.opacity = '0';
                item.classList.remove('reveal-item');
            });
        }
    }

    showScanningEffect() {
        // Create scanning line
        const scanner = document.createElement('div');
        scanner.className = 'scanning-line';
        this.summaryTarget.appendChild(scanner);

        // Accessing... text simulation
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
