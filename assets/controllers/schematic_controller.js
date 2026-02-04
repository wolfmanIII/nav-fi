import { Controller } from '@hotwired/stimulus';

/*
 * assets/controllers/schematic_controller.js
 * 
 * Handles interaction with the Ship Schematic Overlay.
 * - Opens modals when hotspots are clicked.
 */
export default class extends Controller {

    connect() {
        console.log('Schematic controller connected');
        // Add listeners to buttons manually if needed to verify
    }

    openModal(event) {
        // Prevent form submission if button is type="submit" (though it should be type="button")
        event.preventDefault();

        const modalId = event.currentTarget.dataset.modalId;
        const modal = document.getElementById(modalId);

        if (modal) {
            modal.showModal();
        } else {
            console.error(`Modal with ID "${modalId}" not found.`);
        }
    }
}
