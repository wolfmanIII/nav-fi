import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        sessionId: String
    }

    connect() {
        console.log('Cube Controller Active');
    }

    async generate() {
        if (!this.sessionIdValue) return;

        const container = document.getElementById('stream-container');
        container.innerHTML = '<div class="text-center text-accent animate-pulse text-xs py-10">SCANNING SECTOR DATALINKS...</div>';

        try {
            const response = await fetch(`/cube/generate/${this.sessionIdValue}`);
            const data = await response.json();

            container.innerHTML = '';

            if (data.length === 0) {
                container.innerHTML = '<div class="text-center text-warning text-xs py-10">NO OPPORTUNITIES FOUND</div>';
                return;
            }

            data.forEach(item => this.renderStreamItem(item));

        } catch (error) {
            console.error('Scan failed', error);
            // Display specific error if available
            const msg = error.message || 'DATALINK OFFLINE';
            container.innerHTML = `<div class="text-center text-error text-xs py-10">SCAN ERROR: ${msg}</div>`;
        }
    }

    renderStreamItem(item) {
        const template = document.getElementById('stream-item-template');
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.card');

        // Populate Data
        clone.querySelector('[data-slot="type"]').textContent = item.type;
        clone.querySelector('[data-slot="dist"]').textContent = item.distance > 0 ? `${item.distance} PC` : 'LOCAL';
        clone.querySelector('[data-slot="summary"]').textContent = item.summary;
        clone.querySelector('[data-slot="amount"]').textContent = new Intl.NumberFormat().format(item.amount);

        // Pretty print details
        const details = Object.entries(item.details).map(([k, v]) => `${k}: ${v}`).join(' // ');
        clone.querySelector('[data-slot="details"]').textContent = details;

        // Attach Data for Save
        const saveBtn = clone.querySelector('[data-action="cube#save"]');
        saveBtn.dataset.payload = JSON.stringify(item); // Store full payload
        saveBtn.addEventListener('click', (e) => this.save(e, item, card));

        const discardBtn = clone.querySelector('[data-action="cube#discard"]');
        discardBtn.addEventListener('click', () => card.remove());

        document.getElementById('stream-container').appendChild(clone);
    }

    async save(event, item, cardElement) {
        const btn = event.currentTarget;
        if (btn.disabled) return; // Prevent double submit

        btn.classList.add('loading');
        btn.disabled = true;

        try {
            const response = await fetch(`/cube/save/${this.sessionIdValue}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(item)
            });

            if (response.ok) {
                // Success: Update UI Seamlessly

                // 1. Remove from Stream with animation
                cardElement.style.transition = 'all 0.5s ease-out';
                cardElement.style.opacity = '0';
                cardElement.style.transform = 'translateX(50px)';
                setTimeout(() => cardElement.remove(), 500);

                // 2. Add to Storage
                this.renderStorageItem(item);

            } else {
                const text = await response.text();
                console.error('Save error response:', response.status, text);
                alert(`Failed to save (Status ${response.status})`);
                btn.disabled = false;
                btn.classList.remove('loading');
            }
        } catch (error) {
            console.error('Save network failed', error);
            alert('Network Error: ' + error.message);
            btn.disabled = false;
            btn.classList.remove('loading');
        }
    }

    renderStorageItem(item) {
        const container = document.getElementById('storage-container');
        // Remove empty state if present
        if (container.querySelector('.opacity-50')) {
            const emptyMsg = container.querySelector('.opacity-50');
            if (emptyMsg.textContent.includes('Storage Empty')) emptyMsg.remove();
        }

        const template = document.getElementById('storage-item-template');
        const clone = template.content.cloneNode(true);

        clone.querySelector('[data-slot="summary"]').textContent = item.summary;
        clone.querySelector('[data-slot="type"]').textContent = item.type;
        clone.querySelector('[data-slot="amount"]').textContent = new Intl.NumberFormat().format(item.amount) + ' Cr';

        container.prepend(clone); // Add to top
    }
}
