import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

/**
 * Controller per Tom Select con integrazione tema DaisyUI e supporto per opzioni dinamiche.
 */
export default class extends Controller {
    static values = {
        placeholder: String,
    };

    connect() {
        if (this.element.tomselect) return;

        const config = {
            allowEmptyOption: true,
            plugins: ['dropdown_input'],
            searchField: ['text', 'value'],
            maxOptions: null,
            openOnFocus: true,
            dropdownParent: 'body', // Necessario per evitare che lo z-index venga tagliato da containers
            placeholder: this.hasPlaceholderValue ? this.placeholderValue : undefined,
            ...this.getExtraOptions(),
        };

        this.instance = new TomSelect(this.element, config);

        // RIPULISCI IL WRAPPER: Rimuovi classi DaisyUI/Tailwind che causano lo stato "smorto" (grey-out)
        const wrapper = this.instance.wrapper;
        if (wrapper) {
            wrapper.classList.remove('select', 'select-bordered', 'text-gray-500', 'border-gray-300', 'opacity-50');
            wrapper.style.opacity = '1';
        }

        // Applica stili del tema all'apertura e durante la ricerca
        this.instance.on('dropdown_open', () => this.applyThemeOverrides());
        this.instance.on('type', () => this.applyThemeOverrides());

        this.applyThemeOverrides();
        this.handleDisabledState();
    }

    disconnect() {
        if (this.instance) {
            this.instance.destroy();
            this.instance = null;
        }
    }

    /**
     * Recupera opzioni aggiuntive passate tramite data-tom-select-options-value
     */
    getExtraOptions() {
        try {
            return JSON.parse(this.element.dataset.tomSelectOptionsValue || '{}');
        } catch (e) {
            console.error('TomSelect: Error parsing extra options', e);
            return {};
        }
    }

    handleDisabledState() {
        if (!this.instance) return;

        const wrapper = this.instance.wrapper;
        const control = this.instance.control;

        if (this.element.disabled) {
            if (wrapper) wrapper.style.setProperty('opacity', '0.5', 'important');
            if (control) control.style.setProperty('opacity', '0.5', 'important');
            this.instance.disable();
        } else {
            if (wrapper) wrapper.style.setProperty('opacity', '1', 'important');
            if (control) control.style.setProperty('opacity', '1', 'important');
            this.instance.enable();
            this.applyThemeOverrides(); // Forza rinfresco stili
        }
    }

    enable() {
        this.element.disabled = false;
        this.handleDisabledState();
    }

    disable() {
        this.element.disabled = true;
        this.handleDisabledState();
    }

    applyThemeOverrides() {
        if (!this.instance) return;

        const themeVars = getComputedStyle(document.documentElement);
        const bg = themeVars.getPropertyValue('--b2').trim() || '#1f2937';
        const border = themeVars.getPropertyValue('--b3').trim() || '#334155';
        const text = themeVars.getPropertyValue('--bc').trim() || '#e2e8f0';
        const highlight = themeVars.getPropertyValue('--b3').trim() || '#334155';

        // Forza stili sul controllo e su TUTTI i suoi figli (placeholder, item, etc)
        const control = this.instance.control;
        if (control) {
            control.style.setProperty('background-color', bg, 'important');
            control.style.setProperty('border-color', 'transparent', 'important');
            control.style.setProperty('color', text, 'important');
            control.style.setProperty('opacity', this.element.disabled ? '0.5' : '1', 'important');

            // Fix per il testo interno (item e placeholder)
            control.querySelectorAll('.item, .items-placeholder, input').forEach(el => {
                el.style.setProperty('color', text, 'important');
                el.style.setProperty('opacity', '1', 'important');
            });
        }

        // Dropdown styling
        const dropdown = this.instance.dropdown;
        if (dropdown) {
            dropdown.style.setProperty('background-color', bg, 'important');
            dropdown.style.setProperty('border-color', border, 'important');
            dropdown.style.setProperty('color', text, 'important');
            dropdown.style.zIndex = '2147483647';
        }

        const dropdownContent = dropdown?.querySelector('.ts-dropdown-content');
        if (dropdownContent) {
            dropdownContent.style.backgroundColor = bg;
        }

        const dropdownInput = dropdown?.querySelector('.dropdown-input');
        if (dropdownInput) {
            dropdownInput.style.backgroundColor = bg;
            dropdownInput.style.borderColor = border;
            dropdownInput.style.color = text;
        }

        // Stile delle singole opzioni
        const options = dropdown?.querySelectorAll('.option');
        if (options) {
            options.forEach((option) => {
                option.style.setProperty('color', text, 'important');
                option.style.backgroundColor = 'transparent';

                if (option.classList.contains('active') || option.classList.contains('selected')) {
                    option.style.setProperty('background-color', highlight, 'important');
                }

                // Hover manuale per garantire compatibilità con i colori del tema
                option.onmouseenter = () => { option.style.setProperty('background-color', highlight, 'important'); };
                option.onmouseleave = () => {
                    option.style.setProperty('background-color', option.classList.contains('selected') ? highlight : 'transparent', 'important');
                };
            });
        }
    }
}
