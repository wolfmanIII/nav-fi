import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['campaignSelect', 'assetSelect'];

    connect() {
        if (this.campaignSelectTarget.value) {
            this.filterAssets();
        }
    }

    async filterAssets(event) {
        const campaignId = event ? event.target.value : this.campaignSelectTarget.value;
        const assetSelect = this.assetSelectTarget;
        const currentAssetId = assetSelect.value;

        // Clear current options
        assetSelect.innerHTML = '<option value="">Loading...</option>';
        assetSelect.disabled = true;

        try {
            const response = await fetch(`/api/assets/by-campaign?campaign=${campaignId}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const assets = await response.json();

            // Allow empty selection
            let optionsHtml = '<option value="">None // Independent Account</option>';

            assets.forEach(asset => {
                const selected = String(asset.id) === String(currentAssetId) ? 'selected' : '';
                optionsHtml += `<option value="${asset.id}" ${selected}>${asset.name}</option>`;
            });

            assetSelect.innerHTML = optionsHtml;
            assetSelect.disabled = false;

        } catch (error) {
            console.error('Error fetching assets:', error);
            assetSelect.innerHTML = '<option value="">Error loading assets</option>';
        }
    }
}
