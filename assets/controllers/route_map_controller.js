import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['frame', 'overlay'];
  static values = {
    baseUrl: String,
  };

  connect() {
    // Show overlay on initial page load
    if (this.hasOverlayTarget) {
      this.overlayTarget.classList.remove('hidden');
    }
  }

  jump(event) {
    const hex = event.currentTarget.dataset.hex;
    const sector = event.currentTarget.dataset.sector;
    if (!hex || !this.hasFrameTarget) {
      return;
    }

    // Show loading overlay
    if (this.hasOverlayTarget) {
      this.overlayTarget.classList.remove('hidden');
    }

    const baseUrl = this.baseUrlValue || 'https://travellermap.com';
    const nextUrl = sector
      ? `${baseUrl}/go/${encodeURIComponent(sector)}/${encodeURIComponent(hex)}`
      : `${baseUrl}/?marker_hex=${encodeURIComponent(hex)}`;

    this.frameTarget.src = nextUrl;
  }

  hideOverlay() {
    // Keep overlay visible for at least 2 seconds to show the animation
    if (this.hasOverlayTarget) {
      setTimeout(() => {
        this.overlayTarget.classList.add('hidden');
      }, 2000); // 2 seconds delay
    }
  }
}
