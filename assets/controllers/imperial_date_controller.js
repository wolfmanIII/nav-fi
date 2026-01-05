import { Controller } from '@hotwired/stimulus';

const MONTHS = [
    { label: 'Holiday', value: 'holiday', start: 1, end: 1 },
    { label: 'Month 1', value: '1', start: 2, end: 29 },
    { label: 'Month 2', value: '2', start: 30, end: 57 },
    { label: 'Month 3', value: '3', start: 58, end: 85 },
    { label: 'Month 4', value: '4', start: 86, end: 113 },
    { label: 'Month 5', value: '5', start: 114, end: 141 },
    { label: 'Month 6', value: '6', start: 142, end: 169 },
    { label: 'Month 7', value: '7', start: 170, end: 197 },
    { label: 'Month 8', value: '8', start: 198, end: 225 },
    { label: 'Month 9', value: '9', start: 226, end: 253 },
    { label: 'Month 10', value: '10', start: 254, end: 281 },
    { label: 'Month 11', value: '11', start: 282, end: 309 },
    { label: 'Month 12', value: '12', start: 310, end: 337 },
    { label: 'Month 13', value: '13', start: 338, end: 365 },
];

export default class extends Controller {
    static targets = ['display', 'day', 'year'];

    connect() {
        this.currentMonth = MONTHS[1]; // default Month 1
        this.popover = null;

        const initialDay = parseInt(
            this.displayTarget.dataset.imperialDateInitialDay || this.dayTarget.value || '',
            10,
        );
        const initialYear = parseInt(
            this.displayTarget.dataset.imperialDateInitialYear || this.yearTarget.value || '',
            10,
        );

        // inizializza hidden con i valori correnti
        if (Number.isFinite(initialDay)) {
            this.dayTarget.value = initialDay;
            this.setFromAbsoluteDay(initialDay, false);
        }
        if (Number.isFinite(initialYear)) {
            this.yearTarget.value = initialYear;
        } else if (this.yearTarget.dataset.minYear) {
            this.yearTarget.value = this.yearTarget.dataset.minYear;
        }

        // aggiorna il campo visibile subito
        this.updateDisplay();

        document.addEventListener('click', this.handleOutsideClick, true);
    }

    disconnect() {
        document.removeEventListener('click', this.handleOutsideClick, true);
        this.closePopover();
    }

    toggle(event) {
        event.preventDefault();
        if (this.popover) {
            this.closePopover();
        } else {
            this.openPopover();
        }
    }

    openPopover() {
        this.closePopover();
        const wrapper = document.createElement('div');
        wrapper.className = 'card shadow-lg border border-base-content/10 bg-base-100 p-3 space-y-3';
        wrapper.style.position = 'absolute';
        wrapper.style.zIndex = '2147483646'; // sopra la modal/overlay
        wrapper.style.minWidth = '280px';
        wrapper.style.maxWidth = '320px';

        const host = this.displayTarget.closest('.modal-box') || document.body;
        if (getComputedStyle(host).position === 'static') {
            host.style.position = 'relative';
        }
        host.style.overflow = 'visible';

        const rect = this.displayTarget.getBoundingClientRect();
        const hostRect = host.getBoundingClientRect();
        const top = rect.bottom - hostRect.top + 6;
        const left = rect.left - hostRect.left;
        wrapper.style.top = `${top}px`;
        wrapper.style.left = `${left}px`;

        const header = document.createElement('div');
        header.className = 'flex items-center justify-between gap-2';

        const prev = document.createElement('button');
        prev.type = 'button';
        prev.className = 'btn btn-outline btn-primary flex items-center justify-center text-base h-8 min-h-8';
        prev.innerHTML = this.decodeIcon(this.displayTarget.dataset.imperialDatePrevIcon) || '«';
        prev.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.changeMonth(-1);
        });

        const title = document.createElement('div');
        title.className = 'font-semibold text-sm';
        this.titleEl = title;

        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'btn btn-outline btn-primary flex items-center justify-center text-base h-8 min-h-8';
        next.innerHTML = this.decodeIcon(this.displayTarget.dataset.imperialDateNextIcon) || '»';
        next.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.changeMonth(1);
        });

        header.append(prev, title, next);

        const yearRow = document.createElement('div');
        yearRow.className = 'flex items-center gap-2';

        const yearLabel = document.createElement('span');
        yearLabel.className = 'text-sm opacity-70';
        yearLabel.textContent = 'Year';

        const yearInput = document.createElement('input');
        yearInput.type = 'number';
        yearInput.className = 'input input-bordered input-sm w-full';
        yearInput.value = this.yearTarget.value || '';
        yearInput.min = this.yearTarget.dataset.minYear || '';
        yearInput.max = this.yearTarget.dataset.maxYear || '';
        yearInput.addEventListener('input', () => {
            this.yearTarget.value = yearInput.value;
            this.updateDisplay();
        });
        this.yearInputEl = yearInput;

        yearRow.append(yearLabel, yearInput);

        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-7 gap-1 text-sm';
        this.gridEl = grid;

        wrapper.append(header, yearRow, grid);
        wrapper.addEventListener('click', (e) => e.stopPropagation());

        host.appendChild(wrapper);
        this.popover = wrapper;

        // assicura che la month view rifletta il valore selezionato
        const currentDay = parseInt(this.dayTarget.value || '', 10);
        if (Number.isFinite(currentDay)) {
            const month = MONTHS.find((m) => currentDay >= m.start && currentDay <= m.end);
            if (month) {
                this.currentMonth = month;
            }
        }

        this.renderMonth();
    }

    closePopover() {
        if (this.popover && this.popover.parentNode) {
            this.popover.parentNode.removeChild(this.popover);
        }
        this.popover = null;
    }

    handleOutsideClick = (event) => {
        if (!this.popover) {
            return;
        }
        const isClickInside = this.popover.contains(event.target) || this.displayTarget.contains(event.target);
        if (!isClickInside) {
            this.closePopover();
        }
    };

    changeMonth(delta) {
        const idx = MONTHS.findIndex((m) => m.value === this.currentMonth.value);
        let nextIdx = idx + delta;
        if (nextIdx < 0) nextIdx = MONTHS.length - 1;
        if (nextIdx >= MONTHS.length) nextIdx = 0;
        this.currentMonth = MONTHS[nextIdx];
        this.renderMonth();
    }

    renderMonth() {
        if (!this.gridEl || !this.titleEl) {
            return;
        }
        this.titleEl.textContent = `${this.currentMonth.label}`;
        this.gridEl.innerHTML = '';

        for (let day = this.currentMonth.start; day <= this.currentMonth.end; day += 1) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-xs btn-outline';
            btn.textContent = String(day).padStart(3, '0');
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.selectDay(day);
            });
            if (Number(this.dayTarget.value) === day) {
                btn.classList.add('btn-primary');
            }
            this.gridEl.appendChild(btn);
        }
    }

    selectDay(day) {
        this.dayTarget.value = day;
        if (!this.yearTarget.value) {
            this.yearTarget.value = this.yearTarget.dataset.minYear || this.yearInputEl?.value || '';
        }
        if (this.yearInputEl && !this.yearInputEl.value && this.yearTarget.value) {
            this.yearInputEl.value = this.yearTarget.value;
        }
        this.updateDisplay();
        // keep popover open so the modal doesn't close/flicker
    }

    setFromAbsoluteDay(day, updateDisplay = true) {
        const month = MONTHS.find((m) => day >= m.start && day <= m.end);
        if (month) {
            this.currentMonth = month;
        }
        this.dayTarget.value = day;
        if (updateDisplay) {
            this.updateDisplay();
        }
    }

    updateDisplay() {
        const day = this.dayTarget.value;
        const year = this.yearTarget.value;
        if (day && year) {
            this.displayTarget.value = `${String(day).padStart(3, '0')}/${year}`;
        } else {
            this.displayTarget.value = '';
        }

        // keep month synced
        const numericDay = parseInt(day, 10);
        if (Number.isFinite(numericDay)) {
            const month = MONTHS.find((m) => numericDay >= m.start && numericDay <= m.end);
            if (month) {
                this.currentMonth = month;
            }
        }
    }

    decodeIcon(ref) {
        return ref || null;
    }
}
