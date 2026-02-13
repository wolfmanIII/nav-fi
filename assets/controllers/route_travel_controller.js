import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['play', 'rewind', 'forward'];
    static values = {
        id: Number,
        active: Boolean
    };

    connect() {
        this.updateState();
    }

    async togglePlay() {
        if (this.activeValue) {
            return;
        }

        const confirmed = await window.NavFiConfirmation.confirm({
            title: 'Activate Route',
            message: 'Activate this route? This will lock the start point and enable travel tracking.',
            confirmText: 'Activate',
            type: 'primary'
        });

        if (!confirmed) return;

        try {
            const response = await fetch(`/route/${this.idValue}/activate`, { method: 'POST' });

            if (response.ok) {
                this.activeValue = true;
                this.updateState();
                this.dispatch('route-updated');
                window.location.reload();
            } else {
                const data = await response.json();
                window.NavFiToast.notify(data.error || 'Error activating route', 'error');
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
        if (!this.activeValue) return;

        const verb = direction === 'forward' ? 'Advance' : 'Rewind';
        const message = direction === 'forward'
            ? 'Proceed to next waypoint? Session date will advance by 7 days.'
            : 'Return to previous waypoint? Session date will still advance by 7 days.';

        const confirmed = await window.NavFiConfirmation.confirm({
            title: `${verb} Route`,
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

                window.location.reload();
            } else {
                window.NavFiToast.notify(data.error || 'Travel error', 'error');
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

        this.rewindTarget.disabled = !this.activeValue;
        this.forwardTarget.disabled = !this.activeValue;
    }
}
