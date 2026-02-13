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
        this.onRouteUpdatedBound = this.onRouteUpdated.bind(this);
        window.addEventListener('navfi:route-updated', this.onRouteUpdatedBound);
    }

    disconnect() {
        if (this.onRouteUpdatedBound) {
            window.removeEventListener('navfi:route-updated', this.onRouteUpdatedBound);
        }
        if (this.map) {
            this.map.remove();
        }
    }

    onRouteUpdated(event) {
        console.log('Nav-Fi RouteTileMap: Received route update', event.detail);
        if (event.detail && event.detail.waypoints) {
            this.waypointsValue = event.detail.waypoints;
        }
    }

    waypointsValueChanged(value, previousValue) {
        if (!this.map) return;

        // Prevent redraw on initial load if map isn't ready or if value hasnt truly changed (deep check optional but simple provided checks suffice)
        if (JSON.stringify(value) === JSON.stringify(previousValue)) return;

        console.log('Nav-Fi RouteTileMap: Waypoints changed, redrawing...');

        // Clear existing layers
        if (this.routePolyline) {
            this.routePolyline.remove();
            this.routePolyline = null;
        }
        if (this.routeMarkers) {
            this.routeMarkers.forEach(m => m.remove());
        }
        this.routeMarkers = [];

        this.drawRoute();
    }

    async initMap() {
        if (!this.hasContainerTarget) return;

        console.log('Nav-Fi TileMap: Initializing...');

        this.containerTarget.style.backgroundColor = '#050505';

        // 1. Setup Custom CRS and Map
        this.map = L.map(this.containerTarget, {
            crs: L.CRS.Simple, // Iniziamo con Simple
            minZoom: -4,
            maxZoom: 10,
            zoomControl: true,
            attributionControl: false,
            center: [0, 0],
            zoom: 0
        });

        // 2. Define Custom TileLayer for TravellerMap
        const TravellerTileLayer = L.TileLayer.extend({
            getTileUrl: function (coords) {
                let z = coords.z;
                let scale = Math.pow(2, z);
                if (scale < 1) scale = 1;

                return `https://travellermap.com/api/tile?x=${coords.x}&y=${coords.y}&scale=${scale}&options=895&style=poster`;
            }
        });

        new TravellerTileLayer('', {
            maxZoom: 10,
            minZoom: 0,
            tileSize: 256,
            noWrap: true,
            bounds: [[-5000, -5000], [5000, 5000]]
        }).addTo(this.map);

        // 3. Disegna Elementi (Markers/Route)
        // Initial draw relies on waypointsValue being already set or set shortly
        if (this.waypointsValue.length > 0) {
            await this.drawRoute();
        }

        // Nascondi overlay dopo che la mappa è pronta (4 secondi di delay come richiesto)
        // Spostato qui per garantire che venga eseguito anche se non ci sono waypoint
        setTimeout(() => {
            if (this.hasOverlayTarget) {
                this.overlayTarget.classList.add('opacity-0', 'pointer-events-none'); // Tailwind/CSS fade
                // Rimuovi dal DOM dopo la transizione
                setTimeout(() => {
                    if (this.hasOverlayTarget) this.overlayTarget.remove();
                }, 1000); // 1s wait for transition
            }
        }, 3000);
    }

    async getCoordinates(sectorName, hexCode) {
        if (!sectorName || !hexCode) return null;

        const sectorData = await this._fetchSectorData(sectorName);
        if (!sectorData) return null;

        const hx = parseInt(hexCode.substring(0, 2), 10);
        const hy = parseInt(hexCode.substring(2, 4), 10);

        const SECTOR_WIDTH = 32;
        const SECTOR_HEIGHT = 40;
        const REFERENCE_SECTOR_X = 0;
        const REFERENCE_SECTOR_Y = 0;
        const REFERENCE_HEX_X = 1;
        const REFERENCE_HEX_Y = 40;

        // 1. World Space
        const worldX = (sectorData.sx - REFERENCE_SECTOR_X) * SECTOR_WIDTH + (hx - REFERENCE_HEX_X);
        const worldY = (sectorData.sy - REFERENCE_SECTOR_Y) * SECTOR_HEIGHT + (hy - REFERENCE_HEX_Y);

        // 2. Map Space
        const isEven = (n) => (n % 2) === 0;
        const PARSEC_SCALE_X = Math.cos(Math.PI / 6);
        const PARSEC_SCALE_Y = 1;

        var ix_adj = worldX - 0.5;
        var iy_adj = isEven(worldX) ? worldY - 0.5 : worldY;

        var mapX = ix_adj * PARSEC_SCALE_X;
        var mapY = iy_adj * -PARSEC_SCALE_Y;

        // 3. Leaflet Space (Lat = MapY, Lng = MapX)
        return { lat: mapY, lng: mapX };
    }

    // Helper interno per fetch sector
    async _fetchSectorData(name) {
        if (!this.sectorCache) this.sectorCache = {};
        if (this.sectorCache[name]) return this.sectorCache[name];

        console.log(`Nav-Fi RouteTileMap: Fetching sector '${name}'...`);
        try {
            const res = await fetch(`https://travellermap.com/api/search?q=${encodeURIComponent(name)}`);
            const data = await res.json();
            // console.log(`Nav-Fi RouteTileMap: API response for '${name}':`, data);

            const item = data.Results?.Items?.find(i => i.Sector);
            if (item) {
                const res = { sx: item.Sector.SectorX, sy: item.Sector.SectorY };
                this.sectorCache[name] = res;
                return res;
            } else {
                console.warn(`Nav-Fi RouteTileMap: Sector '${name}' not found in API results.`);
            }
        } catch (e) {
            console.error('Nav-Fi TileMap: Error fetching sector', e);
        }
        return null;
    }

    async drawRoute() {
        if (!this.hasWaypointsValue || this.waypointsValue.length === 0) return;

        console.log('Nav-Fi RouteTileMap: Drawing Route...');
        const routePoints = [];

        for (const w of this.waypointsValue) {
            const coords = await this.getCoordinates(w.sector, w.hex);
            if (coords) {
                routePoints.push({ ...coords, meta: w });
            }
        }

        if (routePoints.length > 0) {
            // Disegna
            const latLngs = routePoints.map(p => [p.lat, p.lng]);

            // Cyan color: #22d3ee (Tailwind cyan-400), Dashed
            this.routePolyline = L.polyline(latLngs, {
                color: '#22d3ee',
                weight: 4,
                className: 'tile-route',
                dashArray: '10, 10', // Dashed line
                opacity: 0.8
            }).addTo(this.map);

            this.routeMarkers = []; // Reset array
            routePoints.forEach(p => {
                const marker = L.circleMarker([p.lat, p.lng], {
                    radius: 6,
                    color: '#22d3ee',
                    fillColor: '#0891b2', // Darker cyan fill
                    fillOpacity: 1,
                    weight: 2
                }).addTo(this.map).bindPopup(`${p.meta.name} (${p.meta.hex}) <br> Lat: ${p.lat}, Lng: ${p.lng}`);
                this.routeMarkers.push(marker);
            });

            // Centra su start con animazione
            const start = routePoints[0];
            console.log(`Nav-Fi RouteTileMap: Initial flyTo(${start.lat}, ${start.lng})...`);
            this.map.flyTo([start.lat, start.lng], 6, { animate: true, duration: 2.0 });
        }
    }

    async jump(event) {
        const { hex, sector } = event.currentTarget.dataset;
        if (!hex || !sector) {
            console.error("Nav-Fi RouteTileMap: Missing hex or sector in dataset", event.currentTarget.dataset);
            return;
        }

        console.log(`Nav-Fi RouteTileMap: Jumping to ${sector} ${hex}`);

        try {
            const coords = await this.getCoordinates(sector, hex);

            if (coords) {
                console.log(`Nav-Fi RouteTileMap: Executing flyTo(${coords.lat}, ${coords.lng}, 6)...`);
                this.map.flyTo([coords.lat, coords.lng], 6, { animate: true, duration: 1.5 });

                L.popup()
                    .setLatLng([coords.lat, coords.lng])
                    .setContent(`${sector} ${hex}`)
                    .openOn(this.map);
            } else {
                console.warn('Nav-Fi RouteTileMap: Could not resolve coordinates for jump.');
            }
        } catch (err) {
            console.error("Nav-Fi RouteTileMap: Error during jump", err);
        }
    }
}
