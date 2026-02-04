import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['output'];
    static values = { type: String };

    connect() {
        const navCode = [
            "class TacticalSystems(object):",
            "    def __init__(self):",
            "        self.radar = PhasedArray(range=2000)",
            "        self.lidar = LidarDetector()",
            "        self.arm_weapons(safe_mode=True)",
            "",
            "    def acquire_target(self, vector):",
            "        coords = self.radar.sweep(vector)",
            "        if not coords: return None",
            "        lock = self.calculate_intercept(coords)",
            "        return lock",
            "",
            "    def calibrate_sensors(self):",
            "        logger.info('Calibrating sensor grid...')",
            "        for sector in self.sectors:",
            "            noise = self.measure_background(sector)",
            "            self.filters.adjust(noise)",
            "        return True",
            "",
            "def fire_control_loop():",
            "    while True:",
            "        target = sys.get_active_target()",
            "        if target and target.is_hostile:",
            "            solution = ballistic_calc(target)",
            "            turrets.track(solution)",
            "        yield check_heat_levels()",
            "",
            "if __name__ == '__main__':",
            "    tac = TacticalSystems()",
            "    tac.calibrate_sensors()",
            "    print('Tactical Layer: ONLINE')"
        ];

        const engCode = [
            "class FusionReactor(PowerSource):",
            "    def __init__(self, output_gw=500):",
            "        self.core_temp = 0.0",
            "        self.plasma_field = MagneticBottle()",
            "        self.injectors = [Injector(i) for i in range(12)]",
            "",
            "    def stabilize_field(self):",
            "        fluctuation = self.plasma_field.read_delta()",
            "        if fluctuation > 0.05:",
            "            self.injectors[0].throttle(0.98)",
            "            logger.warn('Field harmonic instability detected')",
            "",
            "    def jump_init(self, parsecs):",
            "        required_mass = self.calc_fuel(parsecs)",
            "        if self.fuel_tank.level < required_mass:",
            "            raise HydrogenError('Insufficient mass')",
            "        self.charge_capacitors(level=1.0)",
            "",
            "def monitor_drive_plume():",
            "    thrust = sensors.get_thrust_vector()",
            "    efficiency = thrust.actual / thrust.theoretical",
            "    if efficiency < 0.95:",
            "        injectors.clean_nozzles()",
            "    return efficiency",
            "",
            "while reactor.is_running:",
            "    reactor.stabilize_field()",
            "    coolant.cycle_pumps()",
            "    time.sleep(0.01)"
        ];

        // Select code based on type value
        this.lines = this.typeValue === 'engineering' ? engCode : navCode;

        this.currentIndex = 0;
        this.interval = setInterval(() => {
            this.addLine();
        }, 200);
    }

    disconnect() {
        if (this.interval) clearInterval(this.interval);
    }

    addLine() {
        const line = this.lines[this.currentIndex];
        this.currentIndex = (this.currentIndex + 1) % this.lines.length;

        const p = document.createElement('div');
        // Switched to font-rajdhani for sci-fi look
        p.className = "text-xs font-rajdhani text-shadow-sm shrink-0 mb-0.5 leading-tight whitespace-pre tracking-wide font-semibold";

        // Apply syntax highlighting
        p.innerHTML = this.highlight(line || ' ');

        this.outputTarget.appendChild(p);

        // Keep last 30 lines
        if (this.outputTarget.children.length > 30) {
            this.outputTarget.removeChild(this.outputTarget.firstChild);
        }

        this.outputTarget.scrollTop = this.outputTarget.scrollHeight;
    }

    highlight(code) {
        if (!code) return '&nbsp;';

        // Escape HTML
        let html = code
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        const tokens = [];
        const save = (snippet) => {
            const id = `___T${tokens.length}___`;
            tokens.push(snippet);
            return id;
        };

        // 1. Comments
        html = html.replace(/(#.*$)/gm, (match) => save(`<span class="text-slate-500 italic">${match}</span>`));

        // 2. Strings
        html = html.replace(/('[^']*')/g, (match) => save(`<span class="text-amber-300">${match}</span>`));
        html = html.replace(/("[^"]*")/g, (match) => save(`<span class="text-amber-300">${match}</span>`));

        // 3. Keywords & Builtins
        const keywords = ['def', 'class', 'if', 'else:', 'elif', 'while', 'for', 'in', 'return', 'yield', 'import', 'from', 'as', 'try:', 'except', 'pass', 'break', 'continue'];
        const builtins = ['self', 'True', 'False', 'None', 'object', 'len', 'print', 'range'];

        keywords.forEach(kw => {
            const regex = new RegExp(`\\b${kw}\\b`, 'g');
            html = html.replace(regex, save(`<span class="text-pink-400">${kw}</span>`));
        });

        builtins.forEach(kw => {
            const regex = new RegExp(`\\b${kw}\\b`, 'g');
            html = html.replace(regex, save(`<span class="text-purple-400">${kw}</span>`));
        });

        // 4. Numbers
        html = html.replace(/\b(\d+)\b/g, (match) => save(`<span class="text-orange-400">${match}</span>`));

        // 5. Decorators / Special chars
        html = html.replace(/(=|\+|\*|-|:|\[|\]|\(|\)|,)/g, (match) => save(`<span class="text-cyan-600">${match}</span>`));

        // Restore tokens
        tokens.forEach((snippet, i) => {
            html = html.replace(`___T${i}___`, snippet);
        });

        return html;
    }
}
