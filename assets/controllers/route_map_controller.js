import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';
import 'leaflet/dist/leaflet.min.css';

// Fix icone Leaflet
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

export default class extends Controller {
  static targets = ['container', 'overlay'];
  static values = {
    baseUrl: String,
    currentHex: String,
    currentSector: String,
    waypoints: Array
  };

  connect() {
    this.initMap();
  }

  async initMap() {
    if (!this.hasContainerTarget) return;

    console.log('Nav-Fi Map: Initializing (Multi-Sector + Local Coord Calculation)...');

    this.containerTarget.style.backgroundColor = '#050505';
    this.sectorCache = {}; // Cache per fetchSectorBounds

    // 1. Inizializza Mappa
    this.map = L.map(this.containerTarget, {
      crs: L.CRS.Simple,
      minZoom: -2,
      maxZoom: 8,
      zoomControl: true,
      attributionControl: false,
      center: [0, 0],
      zoom: 1
    });

    try {
      // 2. Identifica i Settori da Caricare
      let sectorsToLoad = new Set();

      // Aggiungi settori dei waypoint
      if (this.hasWaypointsValue && this.waypointsValue.length > 0) {
        this.waypointsValue.forEach(w => {
          if (w.sector) sectorsToLoad.add(w.sector);
        });
      }
      // Fallback sul settore corrente se non ci sono waypoint
      else if (this.hasCurrentSectorValue) {
        sectorsToLoad.add(this.currentSectorValue);
      }

      const uniqueSectors = Array.from(sectorsToLoad);
      console.log('Nav-Fi Map: Sectors to load:', uniqueSectors);

      // 3. Carica ed aggiungi gli Overlay
      const sectorPromises = uniqueSectors.map(async sectorName => {
        const data = await this.fetchSectorBounds(sectorName);
        if (data) {
          const leafletBounds = [
            [-data.maxY, data.minX],
            [-data.minY, data.maxX]
          ];

          const posterUrl = `https://travellermap.com/api/poster?sector=${encodeURIComponent(sectorName)}&style=poster&options=895&scale=64`;

          L.imageOverlay(posterUrl, leafletBounds, {
            opacity: 1,
            className: 'sector-overlay'
          }).addTo(this.map);

          return leafletBounds;
        }
        return null;
      });

      await Promise.all(sectorPromises);

      // 4. Disegna Rotta e Waypoint
      let routePoints = [];

      if (this.hasWaypointsValue && this.waypointsValue.length > 0) {
        console.log('Nav-Fi Map: Processing Route Waypoints...');

        // Risolvi Coordinate (Localmente ora!)
        const waypointPromises = this.waypointsValue.map(async w => {
          if (!w.hex || !w.sector) return null;
          const coords = await this.resolveCoordinates(w.hex, w.sector);
          if (coords) {
            return {
              lat: -coords.y, // Inverti Y per Leaflet
              lng: coords.x,
              meta: w
            };
          }
          return null;
        });

        const results = await Promise.all(waypointPromises);
        routePoints = results.filter(p => p !== null);

        if (routePoints.length > 0) {
          const latLngs = routePoints.map(p => [p.lat, p.lng]);

          // Linea Rotta
          L.polyline(latLngs, {
            color: '#22d3ee', // cyan-400
            weight: 3,
            opacity: 0.9,
            dashArray: '5, 10',
            lineCap: 'round',
            className: 'route-line'
          }).addTo(this.map);

          // Markers
          routePoints.forEach((p, index) => {
            const isStart = index === 0;
            const isEnd = index === routePoints.length - 1;

            let color = '#22d3ee'; // Cyan (Intermedio)
            let radius = 4;

            if (isStart) { color = '#10b981'; radius = 6; } // Green
            if (isEnd) { color = '#ef4444'; radius = 6; } // Red

            L.circleMarker([p.lat, p.lng], {
              radius: radius,
              color: color,
              fillColor: '#000',
              fillOpacity: 1,
              weight: 2
            }).addTo(this.map)
              .bindPopup(`<div class="font-orbitron text-xs"><b>${index + 1}. ${p.meta.name || 'Waypt'}</b><br>${p.meta.hex} (${p.meta.sector})</div>`);
          });

          // 5. CENTRA SUL PRIMO WAYPOINT (Richiesta Utente)
          // Usa lo stesso livello di zoom (6) della funzione jump()
          const startPoint = routePoints[0];
          console.log('Nav-Fi Map: Centering on Start Point', startPoint);
          this.map.setView([startPoint.lat, startPoint.lng], 6);

          // Opzionale: Apri il popup del primo punto per evidenziarlo
          // Dobbiamo ritrovare il marker corrispondente se vogliamo aprire il popup
          this.map.eachLayer((layer) => {
            if (layer instanceof L.CircleMarker) {
              const latLng = layer.getLatLng();
              if (latLng.lat === startPoint.lat && latLng.lng === startPoint.lng) {
                layer.openPopup();
              }
            }
          });

        } else {
          console.warn('Nav-Fi Map: No valid route points resolved.');
        }
      }

    } catch (err) {
      console.error('Nav-Fi Map: Init Error', err);
    } finally {
      this.hideOverlay();
    }
  }

  async fetchSectorBounds(sectorName) {
    if (this.sectorCache && this.sectorCache[sectorName]) {
      return this.sectorCache[sectorName];
    }

    const url = `https://travellermap.com/api/search?q=${encodeURIComponent(sectorName)}`;
    try {
      const res = await fetch(url);
      const data = await res.json();

      if (data.Results && data.Results.Items && data.Results.Items.length > 0) {
        const item = data.Results.Items.find(i => i.Sector);
        if (item && item.Sector) {
          const sx = item.Sector.SectorX;
          const sy = item.Sector.SectorY;

          const minX = sx * 32;
          const minY = sy * 40;

          const result = {
            minX: minX,
            maxX: minX + 32,
            minY: minY,
            maxY: minY + 40,
            sx: sx,
            sy: sy
          };

          this.sectorCache[sectorName] = result;
          return result;
        }
      }
    } catch (e) {
      console.error('Nav-Fi Map: Sector Search Error', e);
    }
    return null;
  }

  async resolveCoordinates(hex, sectorName) {
    if (!hex || !sectorName) return null;

    const sectorData = await this.fetchSectorBounds(sectorName);
    if (!sectorData) return null;

    const hx = parseInt(hex.substring(0, 2), 10);
    const hy = parseInt(hex.substring(2, 4), 10);

    if (isNaN(hx) || isNaN(hy)) return null;

    // 3. Calcolo Coordinate Globali Parsec
    // Le coordinate Hex (1-32, 1-40) sono 1-based.
    // Il grid Leaflet/ImageOverlay che abbiamo impostato va da 0 a 32 (X) e 0 a -40 (Y/Lat).

    // X: (sx * 32) + hx - 0.5 (Per centrare nella colonna)
    const gx = (sectorData.sx * 32) + hx - 0.5;

    // Y: (sy * 40) + hy - 0.5 (Per centrare nella riga)
    // STAGGER: Le colonne PARI (2, 4...) sono shiftate in basso di 0.5
    const stagger = (hx % 2 === 0) ? 0.5 : 0;
    const gy = (sectorData.sy * 40) + hy - 0.5 + stagger;

    // Lat è negativa (va verso il basso)
    return { x: gx, y: gy };
  }

  // Jump rimane invariato ma usa la nuova logica di zoom se necessario
  async jump(event) {
    const hex = event.currentTarget.dataset.hex;
    const sector = event.currentTarget.dataset.sector;

    if (sector && hex) {
      const coords = await this.resolveCoordinates(hex, sector);
      if (coords) {
        const lat = -coords.y;
        const lng = coords.x;

        this.map.flyTo([lat, lng], 6); // Zoom più alto (vicino)

        this.updateActiveStates(hex, sector);

        L.circleMarker([lat, lng], {
          radius: 8,
          color: '#10b981',
          fillColor: '#34d399',
          fillOpacity: 1
        }).addTo(this.map).bindPopup(`<div class="font-orbitron text-xs"><b>${sector}</b><br>${hex}</div>`).openPopup();
      }
    }
  }

  updateActiveStates(hex, sector) {
    const buttons = document.querySelectorAll('[data-route-map-target="button"]');
    buttons.forEach((btn) => {
      if (!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
      if (btn.dataset.hex === hex && btn.dataset.sector === sector) {
        btn.classList.add('opacity-50', 'pointer-events-none');
        btn.innerHTML = `ACTIVE`;
      } else {
        btn.classList.remove('opacity-50', 'pointer-events-none');
        btn.innerHTML = btn.dataset.originalHtml;
      }
    });
  }

  hideOverlay() {
    if (this.hasOverlayTarget) this.overlayTarget.classList.add('hidden');
  }
}
