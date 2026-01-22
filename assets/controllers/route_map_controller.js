import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['frame', 'overlay', 'button'];
  static values = {
    baseUrl: String,
    currentHex: String,
    currentSector: String,
  };

  connect() {
    // Mostra l'overlay al primo caricamento pagina
    if (this.hasOverlayTarget) {
      this.overlayTarget.classList.remove('hidden');
    }

    this.updateActiveStates(this.currentHexValue, this.currentSectorValue);
  }

  jump(event) {
    const hex = event.currentTarget.dataset.hex;
    const sector = event.currentTarget.dataset.sector;
    if (!hex || !this.hasFrameTarget) {
      return;
    }

    // Mostra overlay di caricamento
    if (this.hasOverlayTarget) {
      this.overlayTarget.classList.remove('hidden');
    }

    const baseUrl = this.baseUrlValue || 'https://travellermap.com';
    const nextUrl = sector
      ? `${baseUrl}/go/${encodeURIComponent(sector)}/${encodeURIComponent(hex)}`
      : `${baseUrl}/?marker_hex=${encodeURIComponent(hex)}`;

    this.frameTarget.src = nextUrl;

    this.updateActiveStates(hex, sector);
  }

  updateActiveStates(hex, sector) {
    if (!this.hasButtonTarget) return;

    this.buttonTargets.forEach((btn) => {
      // Salva l'HTML originale se non è già salvato
      if (!btn.dataset.originalHtml) {
        btn.dataset.originalHtml = btn.innerHTML;
      }

      const matchHex = btn.dataset.hex === hex;
      // Gestisci il caso in cui sector possa essere null o stringa vuota
      const btnSector = btn.dataset.sector || null;
      const targetSector = sector || null;
      const matchSector = btnSector === targetSector;

      if (matchHex && matchSector) {
        btn.classList.add('opacity-50', 'pointer-events-none', 'border-emerald-500/50', 'bg-emerald-500/20', 'text-emerald-400');
        btn.classList.remove('text-slate-400');
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> ACTIVE`;
      } else {
        btn.classList.remove('opacity-50', 'pointer-events-none', 'border-emerald-500/50', 'bg-emerald-500/20', 'text-emerald-400');
        btn.classList.add('text-slate-400');
        btn.innerHTML = btn.dataset.originalHtml;
      }
    });
  }

  hideOverlay() {
    // Mantieni l'overlay visibile per almeno 2 secondi per mostrare l'animazione
    if (this.hasOverlayTarget) {
      setTimeout(() => {
        this.overlayTarget.classList.add('hidden');
      }, 2000); // 2 seconds delay
    }
  }
}
