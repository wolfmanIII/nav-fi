import { Controller } from '@hotwired/stimulus';

/**
 * Route Waypoints Controller
 * Lookup only - for single starting position on route edit.
 */
export default class extends Controller {
    static targets = ['inputHex', 'inputSector', 'inputWorld', 'inputUwp'];

    static values = {
        lookupUrl: String,
    };

    async lookup() {
        if (!this.hasLookupUrlValue) return;

        const hex = this.inputHexTarget.value.trim();
        const sector = this.inputSectorTarget.value.trim();
        if (!hex || !sector) return;

        const url = `${this.lookupUrlValue}?hex=${encodeURIComponent(hex)}&sector=${encodeURIComponent(sector)}`;
        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            if (!data?.found) return;

            if (this.hasInputWorldTarget && !this.inputWorldTarget.value) {
                this.inputWorldTarget.value = data.world ?? '';
            }
            if (this.hasInputUwpTarget) {
                this.inputUwpTarget.value = data.uwp ?? '';
            }
        } catch (error) {
            // Silently fail
        }
    }
}

