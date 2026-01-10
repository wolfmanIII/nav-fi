// controllers/ship_roles_toggle_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["wrapper", "shipSelect", "statusWrapper", "statusSelect", "shipRolesSelect", "statusHelper"];

  connect() {
    this.refresh();
  }

  refresh() {
    const hasShip = !!this.shipSelectTarget.value;
    this.wrapperTarget.classList.toggle("hidden", !hasShip);
    if (this.hasStatusWrapperTarget) {
      this.statusWrapperTarget.classList.toggle("hidden", !hasShip);
      this.statusWrapperTarget.dataset.shipActive = hasShip ? "true" : "false";
      this.statusWrapperTarget.dispatchEvent(new CustomEvent("crew-status-date:refresh"));
    }

    if (this.hasStatusHelperTarget) {
      this.statusHelperTarget.classList.toggle("hidden", hasShip);
    }

    if (this.hasStatusSelectTarget) {
      this.statusSelectTarget.required = hasShip;
    }

    if (this.hasShipRolesSelectTarget) {
      this.shipRolesSelectTarget.required = hasShip;
    }
  }
}
