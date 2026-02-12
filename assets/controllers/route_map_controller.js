import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';
import 'leaflet/dist/leaflet.min.css';

// Correzione per le icone dei marker predefinite in ambienti Webpack/Turbo/AssetMapper
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

/**
 * Coordinate System (CRS) Personalizzato per TravellerMap
 * 
 * TravellerMap usa un sistema di coordinate dove:
 * X (Lng) cresce verso destra (Trailing)
 * Y (Lat) cresce verso il basso (Rimward)
 * 
 * Leaflet CRS.Simple mappa di default (x, y) a (x, -y).
 * Questa estensione usa una trasformazione "Identità" (1, 0, 1, 0)
 * per mappare (x, y) direttamente a (x, y) senza inversione.
 */
const TravellerCRS = L.Util.extend({}, L.CRS.Simple, {
  transformation: new L.Transformation(1, 0, 1, 0),
});

class TravellerMapTileLayer extends L.TileLayer {
  getTileUrl(coords) {
    // API TravellerMap: https://travellermap.com/api/tile?x=...&y=...&scale=...&options=...
    // La scala è pixel per parsec.
    // Usiamo la convenzione Scale = 2^Zoom

    // Zoom 0 = Scale 1 (1 px/pc) -> Un tile copre 256 parsec
    // Zoom 6 = Scale 64 (64 px/pc) -> Un tile copre 4 parsec

    const scale = Math.pow(2, coords.z);

    // Lato del tile in pixel (sempre 256 in Leaflet)
    const tilePx = 256;

    // Lato del tile in Parsec (nello spazio Traveller)
    const pSide = tilePx / scale;

    // Coordinate del tile (indici interi)
    // Con il nostro CRS Identity:
    // Tile (0,0) copre [0, pSide] x [0, pSide] parsec
    // Tile (x,y) ha il centro a (x*pSide + pSide/2, y*pSide + pSide/2)

    // NOTA: Usiamo Math.floor per arrotondare eventuali imprecisioni di Leaflet
    const cx = (coords.x * pSide) + (pSide / 2);
    const cy = (coords.y * pSide) + (pSide / 2);

    // Costruzione URL
    // options=895 abilita rotte, confini, nomi, ecc.
    // style=poster è lo stile "dark sci-fi"
    return `https://travellermap.com/api/tile?x=${cx}&y=${cy}&scale=${scale}&options=895&style=poster`;
  }

  getTileSize() {
    return new L.Point(256, 256);
  }
}

export default class extends Controller {
  static targets = ['container', 'overlay'];
  static values = {
    baseUrl: String,
    currentHex: String, // es., "1910"
    currentSector: String, // es., "Spinward Marches"
  };

  connect() {
    console.log('Nav-Fi Map: Connesso.');
    this.initMap();
  }

  initMap() {
    if (!this.hasContainerTarget) return;

    console.log('Nav-Fi Map: Inizializzazione con TravellerCRS (Debug Mode)...');

    // Rimuovi eventuali stili precedenti
    this.containerTarget.style.backgroundColor = '';

    // INJECT DEBUG STYLE
    // Bordo verde per i tile per visualizzare la griglia e capire se ci sono buchi
    if (!document.getElementById('leaflet-debug-style')) {
      const style = document.createElement('style');
      style.id = 'leaflet-debug-style';
      style.innerHTML = `
          .leaflet-tile { 
            outline: 1px solid rgba(0, 255, 0, 0.3) !important; 
          }
          .leaflet-container {
            background-color: #050505; /* Sfondo quasi nero per contrasto */
          }
        `;
      document.head.appendChild(style);
    }

    // Inizializza la mappa
    this.map = L.map(this.containerTarget, {
      crs: TravellerCRS, // Usa il CRS senza inversione Y
      minZoom: -4,       // AMPIO margine di zoom out
      maxZoom: 9,        // AMPIO margine di zoom in
      zoomControl: true,
      attributionControl: false,
      center: [0, 0],    // Centro iniziale (0,0) parsec
      zoom: 0,
      worldCopyJump: false // Disabilita salti strani
    });

    // Aggiungi il layer dei tile
    // Rimuovi bounds e noWrap per vedere se il problema era lì
    this.map.addLayer(new TravellerMapTileLayer({
      noWrap: false, // Lasciamo wrappare per ora (default)
      bounds: null   // Nessun limite
    }));

    // Navigazione iniziale
    const startHex = this.hasCurrentHexValue ? this.currentHexValue : null;
    const startSector = this.hasCurrentSectorValue ? this.currentSectorValue : null;

    if (startHex && startSector) {
      console.log(`Nav-Fi Map: Risoluzione coordinate per ${startSector} ${startHex}...`);
      this.resolveCoordinates(startHex, startSector).then(coords => {
        if (coords) {
          const y = coords.y;
          const x = coords.x;

          console.log(`Nav-Fi Map: Setting view a Y:${y}, X:${x}`);

          // Imposta vista
          this.map.setView([y, x], 4);

          // Marker
          L.circleMarker([y, x], {
            radius: 8,
            color: '#10b981',
            fillColor: '#34d399',
            fillOpacity: 0.8
          }).addTo(this.map).bindPopup(`<b>${startSector}</b><br>${startHex}`);
        } else {
          console.warn('Nav-Fi Map: Coordinate non trovate.');
        }
      }).catch(err => {
        console.error('Nav-Fi Map: Errore risoluzione', err);
      }).finally(() => {
        this.hideOverlay();
      });
    } else {
      this.hideOverlay();
    }
  }

  async resolveCoordinates(hex, sector) {
    if (!hex || !sector) return null;
    try {
      const url = `https://travellermap.com/api/search?q=${encodeURIComponent(hex + ' ' + sector)}`;
      const res = await fetch(url);
      const data = await res.json();
      if (data.results && data.results.length > 0) {
        return data.results[0].coords;
      }
    } catch (e) {
      console.error('Nav-Fi Map: Errore API Search', e);
    }
    return null;
  }

  async jump(event) {
    const hex = event.currentTarget.dataset.hex;
    const sector = event.currentTarget.dataset.sector;

    if (sector && hex) {
      const coords = await this.resolveCoordinates(hex, sector);
      if (coords) {
        const y = coords.y;
        const x = coords.x;

        this.map.flyTo([y, x], 4, { duration: 1.5 });
        this.updateActiveStates(hex, sector);

        L.circleMarker([y, x], {
          radius: 8,
          color: '#10b981',
          fillColor: '#34d399',
          fillOpacity: 0.8
        }).addTo(this.map).bindPopup(`<b>${sector}</b><br>${hex}`).openPopup();
      }
    }
  }

  updateActiveStates(hex, sector) {
    const buttons = document.querySelectorAll('[data-route-map-target="button"]');
    buttons.forEach((btn) => {
      if (!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
      const isMatch = (btn.dataset.hex === hex && btn.dataset.sector === sector);

      if (isMatch) {
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
    if (this.hasOverlayTarget) {
      this.overlayTarget.classList.add('hidden');
    }
  }
}
