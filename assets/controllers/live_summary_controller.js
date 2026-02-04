import { Controller } from '@hotwired/stimulus';

/*
 * assets/controllers/live_summary_controller.js
 * 
 * Robust version with ID auto-detection.
 */
export default class extends Controller {
    static targets = [
        'hullTons', 'hullConfig', 'structPoints', 'techLevel',
        'jDriveRating', 'mDriveRating', 'powerPlantRating',
        'fuelTons', 'cargoTons',
        'stateroomsCount', 'weaponsCount',
        'totalCost'
    ];

    connect() {
        console.log('✅ LIVE SUMMARY CONTROLLER CONNECTED');
        // alert('SYSTEM ONLINE: Tactical Readout Connected'); // Uncomment if needed for extreme debugging


        // Listen to global changes
        this.element.addEventListener('input', () => this.sync());
        this.element.addEventListener('change', () => this.sync());

        // Observer
        this.observer = new MutationObserver(() => this.sync());
        this.observer.observe(this.element, { childList: true, subtree: true });

        // Initial sync
        this.sync();
    }

    disconnect() {
        if (this.observer) this.observer.disconnect();
    }

    sync() {
        // Prevent infinite loop by disconnecting observer during updates
        if (this.observer) this.observer.disconnect();

        try {
            // HULL
            this.updateField('hullTons', ['asset_shipDetails_hull_tons', 'asset_hull_tons']);
            this.updateField('hullConfig', ['asset_shipDetails_hull_configuration', 'asset_hull_configuration']);
            this.updateField('structPoints', ['asset_shipDetails_hull_points', 'asset_hull_points']);
            this.updateField('techLevel', ['asset_shipDetails_techLevel', 'asset_techLevel']);

            // DRIVES
            this.updateField('jDriveRating', ['asset_shipDetails_jDrive_rating', 'asset_jDrive_rating']);
            this.updateField('mDriveRating', ['asset_shipDetails_mDrive_rating', 'asset_mDrive_rating']);
            this.updateField('powerPlantRating', ['asset_shipDetails_powerPlant_rating', 'asset_powerPlant_rating']);

            // LOGISTICS
            this.updateField('fuelTons', ['asset_shipDetails_fuel_tons', 'asset_fuel_tons']);
            this.updateField('cargoTons', ['asset_shipDetails_cargo_tons', 'asset_cargo_tons']);

            // COLLECTIONS
            this.updateCountField('stateroomsCount', ['asset_shipDetails_staterooms', 'asset_staterooms']);
            this.updateCountField('weaponsCount', ['asset_shipDetails_weapons', 'asset_weapons']);

            // TOTAL
            this.updateField('totalCost', ['asset_shipDetails_totalCost', 'asset_totalCost']);
        } finally {
            // Reconnect observer
            if (this.observer) {
                this.observer.observe(this.element, { childList: true, subtree: true });
            }
        }
    }

    updateField(targetName, candidateIds) {
        if (!this.hasTarget(targetName)) return; // Safe check using standard API

        const target = this[`${targetName}Target`]; // Access target property
        let input = null;

        // Try candidates
        for (const id of candidateIds) {
            input = document.getElementById(id);
            if (input) break;
        }

        if (input) {
            let val = input.value;
            if (val === '') val = '-';

            if (targetName.includes('Tons') && val !== '-') {
                target.textContent = val + ' dT';
            } else {
                target.textContent = val;
            }
        }
    }

    updateCountField(targetName, candidatePrefixes) {
        if (!this.hasTarget(targetName)) return;

        const target = this[`${targetName}Target`];
        let count = 0;
        let found = false;

        for (const prefix of candidatePrefixes) {
            // Count elements that look like {prefix}_0_description
            const inputs = document.querySelectorAll(`[id^="${prefix}_"][id$="_description"]`);
            if (inputs.length > 0) {
                count = inputs.length;
                found = true;
                break;
            }
        }

        target.textContent = count > 0 ? count : (found ? '0' : '-');
    }

    hasTarget(name) {
        // Polyfill-ish check just in case, though Stimulus 3+ has has[Name]Target
        return this[`has${name.charAt(0).toUpperCase() + name.slice(1)}Target`];
    }
}
