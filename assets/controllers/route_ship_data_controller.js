import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    change(event) {
        const value = event.target.value;

        // Find the turbo-frame for ship data
        const detailsFrame = document.getElementById('route-ship-data');

        if (!detailsFrame) {
            console.warn('route-ship-data frame not found');
            return;
        }

        // Build URL with asset parameter
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set("asset", value);
        } else {
            url.searchParams.delete("asset");
        }

        // Update frame src to trigger reload
        detailsFrame.src = url.toString();

        // Debug
        console.log('Updating route ship data for asset:', value, url.toString());
    }
}
