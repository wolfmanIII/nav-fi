import { Controller } from '@hotwired/stimulus';

// Gestione collezione waypoints per le rotte (aggiunta/rimozione).
export default class extends Controller {
    static targets = ['collection', 'list', 'prototype'];
    static values = {
        lookupUrl: String,
    };

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

    async lookup(event) {
        if (!this.hasLookupUrlValue) return;
        const item = event.currentTarget.closest('.collection-item');
        if (!item) return;

        const hexInput = item.querySelector('[data-waypoint-hex]');
        const sectorInput = item.querySelector('[data-waypoint-sector]');
        const worldInput = item.querySelector('[data-waypoint-world]');
        const uwpInput = item.querySelector('[data-waypoint-uwp]');

        const hex = hexInput?.value?.trim();
        const sector = sectorInput?.value?.trim();
        if (!hex || !sector) return;

        const url = `${this.lookupUrlValue}?hex=${encodeURIComponent(hex)}&sector=${encodeURIComponent(sector)}`;
        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            if (!data?.found) return;

            if (worldInput && !worldInput.value) {
                worldInput.value = data.world ?? '';
            }
            if (uwpInput && !uwpInput.value) {
                uwpInput.value = data.uwp ?? '';
            }
        } catch (error) {
            // Ignora i fallimenti di ricerca per evitare di bloccare le modifiche.
        }
    }
}
