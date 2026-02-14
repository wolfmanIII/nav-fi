import { Controller } from '@hotwired/stimulus';

/**
 * Custom Searchable Select (Generic)
 * Replaces a standard <select> with a searchable search input and results menu.
 */
export default class extends Controller {
    static values = {
        placeholder: { type: String, default: 'Search...' },
        noResultsText: { type: String, default: 'No results' }
    };

    connect() {
        if (this.element.dataset.searchableSelectActive) return;
        this.element.dataset.searchableSelectActive = 'true';

        this.originalSelect = this.element;
        this.originalSelect.style.display = 'none';

        this.setupUI();
        this.refreshOptions();

        // Sincronizzazione iniziale ritardata per stabilità
        setTimeout(() => {
            this.syncFromNative();
        }, 50);

        this.mutationObserver = new MutationObserver(() => {
            this.refreshOptions();
            this.syncFromNative();
        });
        this.mutationObserver.observe(this.originalSelect, {
            childList: true, attributes: true, attributeFilter: ['disabled', 'selected', 'value']
        });

        this._onExternalChange = this.syncFromNative.bind(this);
        this.originalSelect.addEventListener('change', this._onExternalChange);
        this._onClickOutside = this.onClickOutside.bind(this);
        document.addEventListener('click', this._onClickOutside);
        this._onResize = this.updatePosition.bind(this);
        window.addEventListener('resize', this._onResize);
        window.addEventListener('scroll', this._onResize, true);

        this.selectedIndex = -1;
    }

    disconnect() {
        this.mutationObserver?.disconnect();
        this.originalSelect.removeEventListener('change', this._onExternalChange);
        document.removeEventListener('click', this._onClickOutside);
        window.removeEventListener('resize', this._onResize);
        window.removeEventListener('scroll', this._onResize, true);
        this.wrapper?.remove();
        if (this.menu?.parentNode) this.menu.parentNode.removeChild(this.menu);
    }

    setupUI() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'searchable-select-wrapper relative w-full group';
        const inputContainer = document.createElement('div');
        inputContainer.className = 'relative flex items-center w-full';

        this.searchField = document.createElement('input');
        this.searchField.type = 'text';
        this.searchField.className = 'input input-bordered w-full bg-slate-950/50 border-slate-700 focus:border-cyan-500/50 text-sm h-12 pr-10 cursor-pointer font-rajdhani';

        if (this.originalSelect.classList.contains('select-sm')) {
            this.searchField.classList.add('input-sm');
            this.searchField.classList.replace('h-12', 'h-8');
        }

        this.searchField.placeholder = this.hasPlaceholderValue ? this.placeholderValue : (this.originalSelect.getAttribute('placeholder') || 'Search...');

        const icon = document.createElement('div');
        icon.className = 'absolute right-3 pointer-events-none opacity-30 group-hover:opacity-60 text-cyan-500';
        icon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>`;

        inputContainer.appendChild(this.searchField);
        inputContainer.appendChild(icon);

        this.menu = document.createElement('div');
        this.menu.className = 'fixed z-[9999] mt-1 bg-slate-900/95 border border-slate-700/50 rounded-lg shadow-2xl hidden backdrop-blur-xl';
        this.menu.style.maxHeight = '250px';
        this.menu.style.overflowY = 'auto';

        this.resultsList = document.createElement('ul');
        this.resultsList.className = 'menu menu-compact p-1 w-full';

        this.menu.appendChild(this.resultsList);
        this.wrapper.appendChild(inputContainer);

        const parentDialog = this.originalSelect.closest('dialog');
        if (parentDialog) parentDialog.appendChild(this.menu);
        else document.body.appendChild(this.menu);

        this.originalSelect.parentNode.insertBefore(this.wrapper, this.originalSelect.nextSibling);

        this.searchField.addEventListener('focus', () => this.openDropdown());
        this.searchField.addEventListener('input', () => this.onInput());
        this.searchField.addEventListener('keydown', (e) => this.onKeyDown(e));
    }

    refreshOptions() {
        this.options = Array.from(this.originalSelect.options)
            .filter(opt => opt.value !== '')
            .map(opt => ({ label: opt.text, value: opt.value }));
    }

    syncFromNative() {
        let selectedOption = this.originalSelect.options[this.originalSelect.selectedIndex];
        if ((!selectedOption || selectedOption.value === '') && this.originalSelect.value) {
            selectedOption = Array.from(this.originalSelect.options).find(o => o.value === this.originalSelect.value);
        }

        if (selectedOption && selectedOption.value !== '') {
            this.searchField.value = selectedOption.text;
            this.searchField.classList.add('text-cyan-400', 'font-bold');
        } else {
            this.searchField.value = '';
            this.searchField.classList.remove('text-cyan-400', 'font-bold');
        }

        this.searchField.disabled = this.originalSelect.disabled;
        if (this.originalSelect.disabled) this.wrapper.classList.add('opacity-40', 'pointer-events-none');
        else this.wrapper.classList.remove('opacity-40', 'pointer-events-none');
    }

    updatePosition() {
        if (!this.menu || this.menu.classList.contains('hidden')) return;
        const rect = this.searchField.getBoundingClientRect();
        this.menu.style.top = `${rect.bottom}px`;
        this.menu.style.left = `${rect.left}px`;
        this.menu.style.width = `${rect.width}px`;
    }

    openDropdown() {
        if (this.originalSelect.disabled) return;
        this.renderResults(this.searchField.value);
        this.menu.classList.remove('hidden');
        this.updatePosition();
    }

    closeDropdown() {
        this.menu.classList.add('hidden');
        this.selectedIndex = -1;
        this.syncFromNative();
    }

    onClickOutside(event) {
        if (!this.wrapper.contains(event.target) && !this.menu.contains(event.target)) this.closeDropdown();
    }

    onInput() {
        this.renderResults(this.searchField.value);
        this.menu.classList.remove('hidden');
        this.updatePosition();
    }

    renderResults(query) {
        this.resultsList.innerHTML = '';
        const search = query.toLowerCase().trim();
        const filtered = this.options.filter(opt => opt.label.toLowerCase().includes(search));

        if (filtered.length === 0) {
            const noResults = document.createElement('li');
            noResults.className = 'disabled p-3 text-slate-500 text-[10px] uppercase font-bold tracking-widest text-center';
            noResults.textContent = this.noResultsTextValue;
            this.resultsList.appendChild(noResults);
            return;
        }

        filtered.slice(0, 50).forEach((opt, index) => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.className = 'rounded-md py-2 px-3 text-xs font-rajdhani transition-all duration-200';
            if (this.originalSelect.value === opt.value) a.classList.add('bg-cyan-500/10', 'text-cyan-400', 'font-bold');
            a.textContent = opt.label;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                this.selectOption(opt);
            });
            li.appendChild(a);
            this.resultsList.appendChild(li);
        });

        this.selectedIndex = -1;
        this.updatePosition();
    }

    selectOption(option) {
        this.originalSelect.value = option.value;
        this.originalSelect.dispatchEvent(new Event('change', { bubbles: true }));
        this.searchField.value = option.label;
        this.closeDropdown();
    }

    onKeyDown(e) {
        const items = this.resultsList.querySelectorAll('li:not(.disabled) a');
        if (items.length === 0) {
            if (e.key === 'Escape') this.closeDropdown();
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
            this.highlightItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
            this.highlightItem(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (this.selectedIndex >= 0) {
                items[this.selectedIndex].click();
            } else if (items.length > 0) {
                items[0].click(); // Se preme invio a caso, prende il primo
            }
        } else if (e.key === 'Escape') {
            this.closeDropdown();
        }
    }

    highlightItem(items) {
        items.forEach((item, index) => {
            if (index === this.selectedIndex) {
                item.classList.add('bg-cyan-500/20', 'text-cyan-400', 'translate-x-1');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('bg-cyan-500/20', 'text-cyan-400', 'translate-x-1');
            }
        });
    }
}
