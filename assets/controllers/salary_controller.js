import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'campaign',
        'asset',
        'crew',
        'amount',
        'firstPaymentDay',
        'firstPaymentYear',
        'proRataContainer',
        'proRataDays',
        'proRataAmount'
    ];

    connect() {
        this.applyFilters();
    }

    onCampaignChange() {
        this.applyFilters();
    }

    onAssetChange() {
        this.applyFilters();
    }

    onCrewChange() {
        this.recalculateProRata();
    }

    applyFilters() {
        const campaignId = this.campaignTarget.value;
        const assetId = this.assetTarget.value;

        // Filtra gli asset per campagna
        Array.from(this.assetTarget.options).forEach(option => {
            if (option.value === '') return;
            const optionCampaignId = option.dataset.campaign;
            option.hidden = campaignId !== '' && optionCampaignId !== campaignId;
        });

        // Se l'asset corrente è nascosto, resetta
        if (this.assetTarget.selectedOptions[0] && this.assetTarget.selectedOptions[0].hidden) {
            this.assetTarget.value = '';
        }

        const effectiveAssetId = this.assetTarget.value;

        // Filtra l'equipaggio per asset
        Array.from(this.crewTarget.options).forEach(option => {
            if (option.value === '') return;
            const optionAssetId = option.dataset.asset;
            option.hidden = effectiveAssetId !== '' && optionAssetId !== effectiveAssetId;
        });

        // Se l'equipaggio corrente è nascosto, resetta
        if (this.crewTarget.selectedOptions[0] && this.crewTarget.selectedOptions[0].hidden) {
            this.crewTarget.value = '';
        }

        this.recalculateProRata();
    }

    recalculateProRata() {
        const crewOption = this.crewTarget.selectedOptions[0];
        const monthlyAmount = parseFloat(this.amountTarget.value);
        const dayPay = parseInt(this.firstPaymentDayTarget.value);
        const yearPay = parseInt(this.firstPaymentYearTarget.value);

        if (!crewOption || crewOption.value === '' || isNaN(monthlyAmount) || isNaN(dayPay) || isNaN(yearPay)) {
            this.proRataContainerTarget.classList.add('hidden');
            return;
        }

        const dayAct = parseInt(crewOption.dataset.activationDay);
        const yearAct = parseInt(crewOption.dataset.activationYear);

        if (isNaN(dayAct) || isNaN(yearAct)) {
            this.proRataContainerTarget.classList.add('hidden');
            return;
        }

        // Formula: (Salario mensile / 28) * (Giorni totali da attivazione al giorno di pagamento)
        const DAYS_IN_YEAR = 365;
        const totalDaysAct = (yearAct * DAYS_IN_YEAR) + dayAct;
        const totalDaysPay = (yearPay * DAYS_IN_YEAR) + dayPay;
        const diffDays = totalDaysPay - totalDaysAct;

        if (diffDays <= 0) {
            this.proRataContainerTarget.classList.add('hidden');
            return;
        }

        const proRata = (monthlyAmount / 28) * diffDays;

        this.proRataDaysTarget.textContent = diffDays;
        this.proRataAmountTarget.textContent = proRata.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        this.proRataContainerTarget.classList.remove('hidden');
    }
}
