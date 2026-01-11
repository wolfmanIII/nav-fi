import { Controller } from '@hotwired/stimulus';

// Gestione collection waypoints per le rotte (add/remove).
export default class extends Controller {
    static targets = ['collection', 'list', 'prototype'];

    addItem(event) {
        const collection = event.currentTarget.closest('[data-route-waypoints-target="collection"]');
        this.addFromPrototype(collection);
    }

    addFromPrototype(collection) {
        if (!collection) return;
        const list = collection.querySelector('[data-route-waypoints-target="list"]');
        const templateEl = collection.querySelector('template[data-route-waypoints-target="prototype"]');
        if (!list || !templateEl) return;

        const index = list.children.length;
        const html = templateEl.innerHTML.replace(/__name__/g, index);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const newItem = wrapper.firstElementChild;
        list.appendChild(newItem);
    }

    removeItem(event) {
        const item = event.currentTarget.closest('.collection-item');
        if (item) {
            item.remove();
        }
    }
}
