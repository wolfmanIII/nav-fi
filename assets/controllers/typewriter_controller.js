import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['line'];
    static values = {
        speed: { type: Number, default: 20 }, // ms per char
        lineDelay: { type: Number, default: 300 } // ms between lines
    };

    connect() {
        this.queue = this.lineTargets.map(target => {
            const text = target.textContent.trim(); // Use textContent to read hidden text

            // Clear content
            target.textContent = '';

            // Make visible but empty
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
        // Add a blinking cursor class if desired? For now, simple typing.

        this.typeText(line.element, line.text, 0, () => {
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

        // Random variation for realism
        const randomSpeed = this.speedValue + (Math.random() * 30 - 15);

        setTimeout(() => {
            this.typeText(element, text, charIndex + 1, callback);
        }, Math.max(5, randomSpeed));
    }
}
