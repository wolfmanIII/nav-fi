import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container', 'template'];

    connect() {
        window.NavFiToast = this;
    }

    notify(message, type = 'info') {
        const toast = this.templateTarget.content.cloneNode(true).firstElementChild;

        // Colors
        let alertClass = 'border-l-4 border-cyan-500 bg-cyan-950/90 text-cyan-100 shadow-[0_0_15px_rgba(6,182,212,0.1)]'; // default/info
        let iconClass = 'text-cyan-400';

        if (type === 'success') {
            alertClass = 'border-l-4 border-emerald-500 bg-emerald-950/90 text-emerald-100 shadow-[0_0_15px_rgba(16,185,129,0.1)]';
            iconClass = 'text-emerald-400';
        } else if (type === 'error' || type === 'danger') {
            alertClass = 'border-l-4 border-rose-500 bg-rose-950/90 text-rose-100 shadow-[0_0_15px_rgba(244,63,94,0.1)]';
            iconClass = 'text-rose-400';
        } else if (type === 'warning') {
            alertClass = 'border-l-4 border-amber-500 bg-amber-950/90 text-amber-100 shadow-[0_0_15px_rgba(245,158,11,0.1)]';
            iconClass = 'text-amber-400';
        }

        toast.className = `alert mb-4 ${alertClass} rounded-r-lg rounded-tl-none rounded-bl-none border-t-0 border-b-0 border-r-0 flex items-start gap-4 backdrop-blur-md transition-all duration-300 transform translate-x-full opacity-0`;

        // Content
        const messageEl = toast.querySelector('[data-toast-content]');
        if (messageEl) messageEl.innerHTML = message;

        // Icon color
        const iconWrapper = toast.querySelector('[data-toast-icon]');
        if (iconWrapper) iconWrapper.className = `animate-spin-slow pt-1 ${iconClass}`;

        this.containerTarget.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
        });

        // Auto remove
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            toast.addEventListener('transitionend', () => toast.remove());
        }, 5000);
    }
}
