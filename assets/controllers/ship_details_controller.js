import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['collection', 'list', 'prototype', 'totalInput', 'totalDisplay'];

    connect() {
        this.bindCostInputs(this.element.querySelectorAll('[data-cost-mcr]'));
        this.recalcTotal();
    }

    addItem(event) {
        const collection = event.currentTarget.closest('[data-ship-details-target="collection"]');
        if (!collection) {
            return;
        }
        const list = collection.querySelector('[data-ship-details-target="list"]');
        const templateEl = collection.querySelector('template[data-ship-details-target="prototype"]');
        if (!list || !templateEl) {
            return;
        }
        const index = list.children.length;
        const html = templateEl.innerHTML.replace(/__name__/g, index);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const newItem = wrapper.firstElementChild;
        list.appendChild(newItem);
        this.bindCostInputs(newItem.querySelectorAll('[data-cost-mcr]'));
        this.recalcTotal();
    }

    removeItem(event) {
        const item = event.currentTarget.closest('.collection-item');
        if (item) {
            item.remove();
            this.recalcTotal();
        }
    }

    bindCostInputs(nodeList) {
        nodeList.forEach((input) => {
            input.addEventListener('input', () => this.recalcTotal());
            input.addEventListener('change', () => this.recalcTotal());
        });
    }

    recalcTotal() {
        const costFields = this.element.querySelectorAll('[data-cost-mcr]');
        let sum = 0;
        costFields.forEach((input) => {
            const raw = (input.value || '').trim();
            if (!raw) {
                return;
            }
            const val = parseFloat(raw.replace(',', '.'));
            if (!Number.isNaN(val)) {
                sum += val;
            }
        });

        if (this.hasTotalInputTarget) {
            this.totalInputTarget.value = sum ? sum.toFixed(2) : '';
        }
        if (this.hasTotalDisplayTarget) {
            if (sum) {
                const locale = document.documentElement.lang || navigator.language || 'en';
                this.totalDisplayTarget.value = sum.toLocaleString(locale, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            } else {
                this.totalDisplayTarget.value = '—';
            }
        }
    }
}
