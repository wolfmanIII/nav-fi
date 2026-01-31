import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['campaignSelect', 'assetSelect'];

    connect() {
        // Optional: Trigger initial filter if campaign is pre-selected?
        // Usually not needed for new forms, but useful for edits if campaign field was persistent.
        // Since mapped=false, it starts empty or default.
    }

    async filterAssets(event) {
        const campaignId = event.target.value;
        const assetSelect = this.assetSelectTarget;

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
                optionsHtml += `<option value="${asset.id}">${asset.name}</option>`;
            });

            assetSelect.innerHTML = optionsHtml;
            assetSelect.disabled = false;

        } catch (error) {
            console.error('Error fetching assets:', error);
            assetSelect.innerHTML = '<option value="">Error loading assets</option>';
        }
    }
}
