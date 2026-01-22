import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        sessionId: String
    }

    connect() {
        console.log('Cube Controller Active');
        if (this.sessionIdValue) {
            this.generate();
        }
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
            const msg = error.message || 'DATALINK OFFLINE';
            container.innerHTML = `<div class="text-center text-error text-xs py-10">SCAN ERROR: ${msg}</div>`;
        }
    }

    renderStreamItem(item) {
        const template = document.getElementById('stream-item-template');
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.card');

        clone.querySelector('[data-slot="type"]').textContent = item.type;
        clone.querySelector('[data-slot="dist"]').textContent = item.distance > 0 ? `${item.distance} PC` : 'LOCAL';
        clone.querySelector('[data-slot="summary"]').textContent = item.summary;
        clone.querySelector('[data-slot="amount"]').textContent = new Intl.NumberFormat().format(item.amount);

        const details = Object.entries(item.details).map(([k, v]) => `${k}: ${v}`).join(' // ');
        clone.querySelector('[data-slot="details"]').textContent = details;

        const saveBtn = clone.querySelector('[data-action="cube#save"]');
        saveBtn.dataset.payload = JSON.stringify(item);
        saveBtn.addEventListener('click', (e) => this.save(e, item, card));

        const discardBtn = clone.querySelector('[data-action="cube#discard"]');
        discardBtn.addEventListener('click', () => card.remove());

        document.getElementById('stream-container').prepend(clone);
    }

    async save(event, item, cardElement) {
        const btn = event.currentTarget;
        if (btn.disabled) return;

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
                const data = await response.json();

                // Rimuovi dal flusso con animazione
                cardElement.style.transition = 'all 0.5s ease-out';
                cardElement.style.opacity = '0';
                cardElement.style.transform = 'translateX(50px)';
                setTimeout(() => cardElement.remove(), 500);

                // Aggiungi allo storage con ID dalla risposta
                this.renderStorageItem(item, data.id);

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

    async unsave(event) {
        const btn = event.currentTarget;
        const id = event.params?.id || btn.dataset.cubeIdParam || btn.dataset.id;

        if (!id || btn.disabled) return;

        btn.disabled = true;
        const cardElement = btn.closest('.relative');

        try {
            const response = await fetch(`/cube/unsave/${id}`, {
                method: 'POST'
            });

            if (response.ok) {
                const result = await response.json();

                // Rimuovi dallo storage con animazione
                cardElement.style.transition = 'all 0.4s ease-in';
                cardElement.style.opacity = '0';
                cardElement.style.transform = 'translateX(-50px)';

                setTimeout(() => {
                    cardElement.remove();
                    // Se lo storage è vuoto, potremmo mostrare un messaggio (omesso per brevità o gestito da osservatore)
                }, 400);

                // Ridisegna nel flusso
                this.renderStreamItem(result.data);
            } else {
                alert('Failed to return contract to stream.');
                btn.disabled = false;
            }
        } catch (error) {
            console.error('Unsave failed', error);
            btn.disabled = false;
        }
    }

    renderStorageItem(item, id) {
        const container = document.getElementById('storage-container');

        // Rimuovi lo stato vuoto se presente
        const emptyMsg = container.querySelector('.opacity-50');
        if (emptyMsg && emptyMsg.textContent.includes('Storage Empty')) {
            emptyMsg.remove();
        }

        const template = document.getElementById('storage-item-template');
        const clone = template.content.cloneNode(true);
        const cardElement = clone.querySelector('.relative');

        clone.querySelector('[data-slot="summary"]').textContent = item.summary;
        clone.querySelector('[data-slot="type"]').textContent = item.type;
        clone.querySelector('[data-slot="amount"]').textContent = new Intl.NumberFormat().format(item.amount) + ' Cr';

        // Imposta i pulsanti
        const unsaveBtn = clone.querySelector('[data-slot="unsave-btn"]');
        unsaveBtn.dataset.id = id;

        const viewLink = clone.querySelector('[data-slot="view-link"]');
        viewLink.href = `/cube/contract/${id}`;

        container.prepend(clone);
    }
}
