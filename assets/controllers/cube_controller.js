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

        // Route (New in 2.1)
        clone.querySelector('[data-slot="origin"]').textContent = item.details?.origin || 'Unknown';
        clone.querySelector('[data-slot="destination"]').textContent = item.details?.destination || 'Unknown';

        // Narrative & Difficulty (New in 2.0)
        if (item.details?.difficulty) {
            const diffSlot = clone.querySelector('[data-slot="difficulty"]');
            diffSlot.textContent = item.details.difficulty.toUpperCase();
            diffSlot.classList.remove('hidden');

            if (item.details.difficulty === 'Black Ops') {
                diffSlot.classList.add('bg-rose-950', 'text-rose-500', 'border-rose-900');
            } else if (item.details.difficulty === 'Hazardous') {
                diffSlot.classList.add('bg-amber-950', 'text-amber-500', 'border-amber-900');
            } else {
                diffSlot.classList.add('bg-emerald-950', 'text-emerald-500', 'border-emerald-900');
            }
        }

        if (item.details?.briefing) {
            clone.querySelector('[data-slot="narrative-block"]').classList.remove('hidden');
            const briefingSlot = clone.querySelector('[data-slot="briefing"]');
            briefingSlot.textContent = item.details.briefing;
            // Removed glitch-text from briefing based on user feedback "il resto lascialo normale"

            if (item.details.twist && item.details.twist !== 'None') {
                const twistSlot = clone.querySelector('[data-slot="twist"]');
                // Use HTML to separate label from glitched content
                twistSlot.innerHTML = `TWIST: <span class="glitch-text">${item.details.twist}</span>`;
                twistSlot.classList.remove('hidden');
            }
        }

        // Filter internals from the quick details view
        const internals = ['origin', 'destination', 'dest_hex', 'dest_dist', 'difficulty', 'mission_type', 'twist', 'tier', 'briefing', 'patron', 'variables', 'start_day', 'start_year'];
        const details = Object.entries(item.details)
            .filter(([k]) => !internals.includes(k))
            .map(([k, v]) => `${k.replace('_', ' ')}: ${v}`)
            .join(' // ');

        clone.querySelector('[data-slot="details"]').textContent = details || 'Standard Terms Apply';

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
        const cardElement = clone.querySelector('.card');

        clone.querySelector('[data-slot="summary"]').textContent = item.summary;
        clone.querySelector('[data-slot="type"]').textContent = item.type;
        clone.querySelector('[data-slot="amount"]').textContent = new Intl.NumberFormat().format(item.amount);
        clone.querySelector('[data-slot="dist"]').textContent = item.distance > 0 ? `${item.distance} PC` : 'LOCAL';

        // Difficulty
        if (item.details?.difficulty) {
            const diffSlot = clone.querySelector('[data-slot="difficulty"]');
            diffSlot.textContent = item.details.difficulty.toUpperCase();
            diffSlot.classList.remove('hidden');
            if (item.details.difficulty === 'Black Ops') {
                diffSlot.classList.add('bg-rose-950', 'text-rose-500', 'border-rose-900');
            } else if (item.details.difficulty === 'Hazardous') {
                diffSlot.classList.add('bg-amber-950', 'text-amber-500', 'border-amber-900');
            } else {
                diffSlot.classList.add('bg-emerald-950', 'text-emerald-500', 'border-emerald-900');
            }
        }

        // Narrative
        if (item.details?.briefing) {
            clone.querySelector('[data-slot="narrative-block"]').classList.remove('hidden');
            clone.querySelector('[data-slot="briefing"]').textContent = item.details.briefing;

            if (item.details.twist && item.details.twist !== 'None') {
                const twistSlot = clone.querySelector('[data-slot="twist"]');
                twistSlot.innerHTML = `TWIST: <span class="glitch-text">${item.details.twist}</span>`;
                twistSlot.classList.remove('hidden');
            }
        }

        // Route
        clone.querySelector('[data-slot="origin"]').textContent = item.details?.origin || 'Unknown';
        clone.querySelector('[data-slot="destination"]').textContent = item.details?.destination || 'Unknown';

        // Details
        const internals = ['origin', 'destination', 'dest_hex', 'dest_dist', 'difficulty', 'mission_type', 'twist', 'tier', 'briefing', 'patron', 'variables', 'start_day', 'start_year'];
        const details = Object.entries(item.details)
            .filter(([k]) => !internals.includes(k))
            .map(([k, v]) => `${k.replace('_', ' ')}: ${v}`)
            .join(' // ');
        clone.querySelector('[data-slot="details"]').textContent = details || 'Standard Terms Apply';

        // Buttons
        const unsaveBtn = clone.querySelector('[data-slot="unsave-btn"]');
        unsaveBtn.dataset.id = id;

        const viewLink = clone.querySelector('[data-slot="view-link"]');
        viewLink.href = `/cube/contract/${id}`;

        const viewBtnLink = clone.querySelector('[data-slot="view-link-btn"]');
        if (viewBtnLink) viewBtnLink.href = `/cube/contract/${id}`;

        container.prepend(clone);
    }
}
