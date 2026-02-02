import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

export default class extends Controller {
    static values = {
        placeholder: String,
    };

    connect() {
        if (this.element.tomselect) {
            return;
        }

        this.instance = new TomSelect(this.element, {
            allowEmptyOption: true,
            plugins: ['dropdown_input'],
            searchField: ['text', 'value'],
            maxOptions: null,
            openOnFocus: true,
            dropdownParent: 'body',
            placeholder: this.hasPlaceholderValue ? this.placeholderValue : undefined,
        });

        this.applyThemeOverrides();
    }

    disconnect() {
        if (this.instance) {
            this.instance.destroy();
            this.instance = null;
        }
    }

    applyThemeOverrides() {
        const themeVars = getComputedStyle(document.documentElement);
        const bg = themeVars.getPropertyValue('--b2').trim() || '#1f2937';
        const border = themeVars.getPropertyValue('--b3').trim() || '#334155';
        const text = themeVars.getPropertyValue('--bc').trim() || '#e2e8f0';

        const control = this.element.closest('.ts-wrapper')?.querySelector('.ts-control');
        if (control) {
            control.style.backgroundColor = bg;
            control.style.borderColor = border;
            control.style.color = text;
        }

        const dropdown = document.querySelector('.ts-dropdown');
        if (dropdown) {
            dropdown.style.backgroundColor = bg;
            dropdown.style.borderColor = border;
            dropdown.style.color = text;
        }

        const dropdownContent = dropdown?.querySelector('.ts-dropdown-content');
        if (dropdownContent) {
            dropdownContent.style.backgroundColor = bg;
            dropdownContent.style.color = text;
        }

        const dropdownInput = dropdown?.querySelector('.dropdown-input');
        if (dropdownInput) {
            dropdownInput.style.backgroundColor = bg;
            dropdownInput.style.borderColor = border;
            dropdownInput.style.color = text;
        }

        const options = dropdown?.querySelectorAll('.option');
        const highlight = themeVars.getPropertyValue('--b3').trim() || '#334155';

        if (options) {
            options.forEach((option) => {
                option.style.color = text;
                option.style.backgroundColor = 'transparent';
                if (option.classList.contains('selected')) {
                    option.style.backgroundColor = highlight;
                }
                option.onmouseenter = () => {
                    option.style.backgroundColor = highlight;
                };
                option.onmouseleave = () => {
                    if (option.classList.contains('selected')) {
                        option.style.backgroundColor = highlight;
                        return;
                    }
                    option.style.backgroundColor = 'transparent';
                };
            });
        }

        this.instance.on('dropdown_open', () => this.applyThemeOverrides());
        this.instance.on('type', () => this.applyThemeOverrides());
    }
}
