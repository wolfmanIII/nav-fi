// controllers/ship_roles_toggle_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["wrapper", "shipSelect"];

  connect() {
    this.refresh();
  }

  refresh() {
    const hasShip = !!this.shipSelectTarget.value;
    this.wrapperTarget.classList.toggle("hidden", !hasShip);
  }
}
