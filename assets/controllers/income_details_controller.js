import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    change(event) {
        const value = event.target.value;

        // Trova il turbo-frame per i dettagli reddito
        const detailsFrame = document.getElementById('income-details-frame');

        if (!detailsFrame) {
            console.warn('income-details-frame not found');
            return;
        }

        // Costruisci l'URL con il parametro categoria
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set("category", value);
        } else {
            url.searchParams.delete("category");
        }

        // Aggiorna il src del turbo-frame per caricare i nuovi dettagli
        detailsFrame.src = url.toString();
    }
}
