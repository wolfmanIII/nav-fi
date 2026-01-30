import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['campaign', 'asset'];

    connect() {
        this.applyFilter();
    }

    onCampaignChange() {
        this.applyFilter();
    }

    applyFilter() {
        const campaignId = this.campaignTarget.value;
        const assetSelect = this.assetTarget;
        const options = Array.from(assetSelect.options);

        const hasCampaign = campaignId !== '';
        assetSelect.disabled = !hasCampaign;

        options.forEach((option) => {
            if (option.value === '') {
                option.hidden = false;
                return;
            }

            const optionCampaign = option.dataset.campaign || '';
            option.hidden = !hasCampaign || optionCampaign !== campaignId;
        });

        if (!hasCampaign) {
            if (assetSelect.value !== '') {
                assetSelect.value = '';
                assetSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            return;
        }

        const selectedOption = assetSelect.options[assetSelect.selectedIndex];
        if (selectedOption && selectedOption.value && selectedOption.dataset.campaign !== campaignId) {
            assetSelect.value = '';
            assetSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
}
