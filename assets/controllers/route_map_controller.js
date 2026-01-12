import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['frame'];
  static values = {
    baseUrl: String,
  };

  jump(event) {
    const hex = event.currentTarget.dataset.hex;
    const sector = event.currentTarget.dataset.sector;
    if (!hex || !this.hasFrameTarget) {
      return;
    }

    const baseUrl = this.baseUrlValue || 'https://travellermap.com';
    const nextUrl = sector
      ? `${baseUrl}/go/${encodeURIComponent(sector)}/${encodeURIComponent(hex)}`
      : `${baseUrl}/?marker_hex=${encodeURIComponent(hex)}`;

    this.frameTarget.src = nextUrl;
  }
}
