import { Controller } from '@hotwired/stimulus';

// Gestione collezione detailItems per i costi (aggiunta/rimozione).
export default class extends Controller {
    static targets = ['collection', 'list', 'prototype'];

    connect() {
        // Se non ci sono item, aggiungiamo un prototipo iniziale.
        if (this.hasListTarget && this.listTarget.children.length === 0) {
            this.addFromPrototype(this.collectionTarget);
        }
        this.bindCalculations();
        this.recalcTotal();
    }

    addItem(event) {
        const collection = event.currentTarget.closest('[data-cost-details-target="collection"]');
        this.addFromPrototype(collection);
    }

    addFromPrototype(collection) {
        if (!collection) return;
        const list = collection.querySelector('[data-cost-details-target="list"]');
        const templateEl = collection.querySelector('template[data-cost-details-target="prototype"]');
        if (!list || !templateEl) return;

        const index = list.children.length;
        const html = templateEl.innerHTML.replace(/__name__/g, index);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const newItem = wrapper.firstElementChild;
        list.appendChild(newItem);
        this.bindCalculations(newItem);
        this.recalcTotal();
    }

    removeItem(event) {
        const item = event.currentTarget.closest('.collection-item');
        if (item) {
            item.remove();
            this.recalcTotal();
        }
    }

    bindCalculations(scope = null) {
        const root = scope || this.element;
        const inputs = root.querySelectorAll('input[name$="[quantity]"], input[name$="[cost]"]');
        inputs.forEach((input) => {
            input.removeEventListener('input', this._recalcHandler);
            input.removeEventListener('change', this._recalcHandler);
            input.addEventListener('input', this.recalcTotal.bind(this));
            input.addEventListener('change', this.recalcTotal.bind(this));
        });
    }

    recalcTotal() {
        const inputs = this.element.querySelectorAll('input[name$="[quantity]"], input[name$="[cost]"]');
        let total = 0;
        // itera le coppie per riga
        this.listTarget.querySelectorAll('.collection-item').forEach((item) => {
            const qtyInput = item.querySelector('input[name$="[quantity]"]');
            const costInput = item.querySelector('input[name$="[cost]"]');
            const qty = qtyInput ? parseFloat((qtyInput.value || '').replace(',', '.')) : 0;
            const cost = costInput ? parseFloat((costInput.value || '').replace(',', '.')) : 0;
            if (!Number.isNaN(qty) && !Number.isNaN(cost)) {
                total += qty * cost;
            }
        });

        const amountSelector = this.element.dataset.costDetailsAmountSelector || '[name$="[amount]"]';
        const amountInput = document.querySelector(amountSelector);
        if (amountInput) {
            amountInput.value = total.toFixed(2).replace('.', ',');
        }
    }
}
