import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['output'];

    connect() {
        this.lines = [
            "Initializing neural handshake...",
            "Loading sector charts...",
            "Decrypting bio-metrics...",
            "Establishing secure uplink...",
            "Checking hull integrity...",
            "Life support systems: NOMINAL",
            "Reactor output: 98%",
            "Gravity plating: STABLE",
            "Atmospheric pressure: 1.0 ATM",
            "Scanning for localized anomalies...",
            "Updating starcharts database...",
            "Verifying crew manifests...",
            "Accessing financial ledger...",
            "Ping remote beacon: 24ms",
            "Packet loss: 0.001%",
            "Encryption cycle complete.",
            "Waiting for user auth...",
            "System idle.",
            "Background process PID 4092 started",
            "Garbage collection verified",
            "Memory buffer flushed",
            "Warning: Low signal on sub-channel 4",
            "Rerouting through secondary array...",
            "Signal strength restored."
        ];

        this.interval = setInterval(() => {
            this.addLine();
        }, 100);
    }

    disconnect() {
        if (this.interval) clearInterval(this.interval);
    }

    addLine() {
        const line = this.lines[Math.floor(Math.random() * this.lines.length)];
        const p = document.createElement('div');
        p.className = "text-xs font-mono truncate opacity-60";

        // Random hex code prefix for "tech" feel
        const prefix = "0x" + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0').toUpperCase();
        p.textContent = `[${prefix}] ${line}`;

        this.outputTarget.appendChild(p);

        // Keep only last 20 lines to prevent DOM bloat
        if (this.outputTarget.children.length > 20) {
            this.outputTarget.removeChild(this.outputTarget.firstChild);
        }

        this.outputTarget.scrollTop = this.outputTarget.scrollHeight;
    }
}
