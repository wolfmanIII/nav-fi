import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['play', 'rewind', 'forward'];
    static values = {
        id: Number,
        active: Boolean,
        hasInvalidJumps: Boolean
    };

    connect() {
        this.updateState();
        this.onRouteRefreshBound = (event) => {
            if (event.detail && event.detail.hasInvalidJumps !== undefined) {
                this.hasInvalidJumpsValue = event.detail.hasInvalidJumps;
                this.updateState();
            }
        };
        window.addEventListener('navfi:route-refresh', this.onRouteRefreshBound);
    }

    disconnect() {
        window.removeEventListener('navfi:route-refresh', this.onRouteRefreshBound);
    }

    async togglePlay() {
        if (this.hasInvalidJumpsValue) {
            window.NavFiToast.notify('NAV-COMPUTER: Recalculation required before engagement.', 'warning');
            return;
        }

        if (this.activeValue) {
            return;
        }

        const confirmed = await window.NavFiConfirmation.confirm({
            title: 'INITIATE NAV-LINK',
            message: 'Establish navigation link with target asset? This will lock current coordinates and enable transit tracking.',
            confirmText: 'INITIATE',
            type: 'primary'
        });

        if (!confirmed) return;

        try {
            const response = await fetch(`/route/${this.idValue}/activate`, { method: 'POST' });

            if (response.ok) {
                const data = await response.json();
                this.activeValue = true;
                this.updateState();

                // Dispatch events for map and table
                if (data.allWaypoints) {
                    window.dispatchEvent(new CustomEvent('navfi:route-updated', { detail: { waypoints: data.allWaypoints } }));
                    window.dispatchEvent(new CustomEvent('navfi:route-refresh', { detail: { waypoints: data.allWaypoints } }));
                }

                this.dispatch('route-updated');
                window.NavFiToast.notify('NAV-COMPUTER: Engagement complete. Route locked.', 'success');
            } else {
                const data = await response.json();
                window.NavFiToast.notify(data.error || 'NAV-SYNC: Engagement failure', 'error');
            }
        } catch (error) {
            console.error('Nav-Fi Travel: Error activating route', error);
            window.NavFiToast.notify('Network error', 'error');
        }
    }

    async forward() {
        await this.travel('forward');
    }

    async rewind() {
        await this.travel('backward');
    }

    async travel(direction) {
        if (this.hasInvalidJumpsValue) {
            window.NavFiToast.notify('NAV-COMPUTER: Route requires recalculation.', 'warning');
            return;
        }

        if (!this.activeValue) return;

        const verb = direction === 'forward' ? 'TRANSIT' : 'RETRACE';
        const message = direction === 'forward'
            ? 'Execute jump sequence to next waypoint? Imperial calendar will advance by 7 days.'
            : 'Retrace traverse to previous waypoint? Imperial calendar will advance by 7 days.';

        const confirmed = await window.NavFiConfirmation.confirm({
            title: `EXECUTE ${verb}`,
            message: message,
            confirmText: verb,
            type: 'primary'
        });

        if (!confirmed) return;

        try {
            const response = await fetch(`/route/${this.idValue}/travel/${direction}`, { method: 'POST' });
            const data = await response.json();

            if (response.ok) {
                this.dispatch('waypoint-changed', {
                    detail: {
                        waypointId: data.activeWaypointId,
                        hex: data.activeWaypointHex
                    }
                });

                // Update date in navbar
                if (data.sessionDate) {
                    const dateEl = document.getElementById('navbar-session-date');
                    if (dateEl) dateEl.textContent = data.sessionDate;
                }

                // Global refresh for map and tables
                if (data.allWaypoints) {
                    window.dispatchEvent(new CustomEvent('navfi:route-updated', { detail: { waypoints: data.allWaypoints } }));
                    window.dispatchEvent(new CustomEvent('navfi:route-refresh', { detail: { waypoints: data.allWaypoints } }));
                }

                window.NavFiToast.notify(`NAV-COMPUTER: ${verb} sequence complete.`, 'success');
            } else {
                window.NavFiToast.notify(data.error || 'TRANSIT FAILURE', 'error');
            }
        } catch (error) {
            console.error('Nav-Fi Travel: Error traveling', error);
            window.NavFiToast.notify('Network error', 'error');
        }
    }

    updateState() {
        if (this.activeValue) {
            this.playTarget.classList.add('btn-active', 'text-emerald-400', 'border-emerald-500');
            this.playTarget.classList.remove('text-cyan-400', 'border-cyan-500');
        } else {
            this.playTarget.classList.remove('btn-active', 'text-emerald-400', 'border-emerald-500');
            this.playTarget.classList.add('text-cyan-400', 'border-cyan-500');
        }

        this.rewindTarget.disabled = !this.activeValue || this.hasInvalidJumpsValue;
        this.forwardTarget.disabled = !this.activeValue || this.hasInvalidJumpsValue;

        if (this.hasInvalidJumpsValue) {
            this.playTarget.disabled = true;
            this.playTarget.classList.add('opacity-50', 'cursor-not-allowed');
            this.playTarget.title = "NAV-DATA ERROR: RECALCULATION REQUIRED";
        } else {
            this.playTarget.disabled = false;
            this.playTarget.classList.remove('opacity-50', 'cursor-not-allowed');
            this.playTarget.title = ""; // Clear title if not disabled by invalid jumps
        }
    }
}
