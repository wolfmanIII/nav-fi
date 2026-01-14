import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    change(event) {
        const value = event.target.value;

        // Find the turbo-frame for income details
        const detailsFrame = document.getElementById('income-details-frame');

        if (!detailsFrame) {
            console.warn('income-details-frame not found');
            return;
        }

        // Build URL with category parameter
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set("category", value);
        } else {
            url.searchParams.delete("category");
        }

        // Update the turbo-frame src to load new details
        detailsFrame.src = url.toString();
    }
}
