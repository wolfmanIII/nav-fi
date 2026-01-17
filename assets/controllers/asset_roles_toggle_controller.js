import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["wrapper", "assetSelect", "statusWrapper", "statusSelect", "assetRolesSelect", "statusHelper"];

  connect() {
    this.refresh();
  }

  refresh() {
    const hasAsset = !!this.assetSelectTarget.value;
    this.wrapperTarget.classList.toggle("hidden", !hasAsset);
    if (this.hasStatusWrapperTarget) {
      this.statusWrapperTarget.classList.toggle("hidden", !hasAsset);
      this.statusWrapperTarget.dataset.assetActive = hasAsset ? "true" : "false";
      this.statusWrapperTarget.dispatchEvent(new CustomEvent("crew-status-date:refresh"));
    }

    if (this.hasStatusHelperTarget) {
      this.statusHelperTarget.classList.toggle("hidden", hasAsset);
    }

    if (this.hasStatusSelectTarget) {
      this.statusSelectTarget.required = hasAsset;
    }

    if (this.hasAssetRolesSelectTarget) {
      this.assetRolesSelectTarget.required = hasAsset;
    }
  }
}
