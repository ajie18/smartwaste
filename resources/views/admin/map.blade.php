<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operations map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #17231d; background: #f3f6f4; }
        body { margin: 0; }
        header { align-items: center; background: #fff; border-bottom: 1px solid #dce5df; display: flex; gap: 20px; justify-content: space-between; padding: 14px 22px; }
        h1 { font-size: 1.15rem; margin: 0; }
        header p { color: #5e6f64; font-size: .85rem; margin: 3px 0 0; }
        button { background: #167447; border: 0; border-radius: 7px; color: #fff; cursor: pointer; font: inherit; padding: 9px 14px; }
        main { margin: 20px auto; max-width: 1440px; padding: 0 20px; }
        .stats { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
        .stats span { background: white; border: 1px solid #dce5df; border-radius: 20px; font-size: .9rem; padding: 7px 12px; }
        #map { background: #e7eee9; border: 1px solid #dce5df; border-radius: 12px; height: min(72vh, 760px); min-height: 380px; }
        .leaflet-popup-content { line-height: 1.5; }
        @media (max-width: 600px) { header { align-items: flex-start; } main { margin-top: 12px; padding: 0 12px; } #map { height: 68vh; min-height: 340px; } }
    </style>
</head>
<body>
<header>
    <div>
        <h1>SmartWaste operations map</h1>
        <p>Truck locations, collection routes, and MRF sites</p>
    </div>
    <form method="post" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Sign out</button>
    </form>
</header>
<main>
    <div class="stats" aria-live="polite">
        <span id="truck-count">Trucks: 0</span>
        <span id="route-count">Routes: 0</span>
        <span id="mrf-count">MRF sites: 0</span>
    </div>
    <div id="map" role="application" aria-label="Map of trucks, routes, and MRF sites"></div>
</main>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const data = {{ Illuminate\Support\Js::from($mapData) }};
    const tileUrl = {{ Illuminate\Support\Js::from($tileUrl) }};
    const map = L.map('map').setView([9.75, 118.74], 11);

    L.tileLayer(tileUrl, {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    const layers = {
        'Trucks': L.layerGroup().addTo(map),
        'Routes': L.layerGroup().addTo(map),
        'MRF sites': L.layerGroup().addTo(map),
    };
    const bounds = [];
    const point = (value) => {
        const latitude = Number(Array.isArray(value) ? value[0] : value?.latitude ?? value?.lat);
        const longitude = Number(Array.isArray(value) ? value[1] : value?.longitude ?? value?.lng ?? value?.lon);
        return Number.isFinite(latitude) && Number.isFinite(longitude)
            && latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180
            ? [latitude, longitude]
            : null;
    };

    data.trucks.forEach((truck) => {
        const position = point(truck);
        if (!position) return;
        bounds.push(position);
        L.marker(position).bindPopup(
            `<strong>${escapeHtml(truck.plate_number)}</strong><br>${escapeHtml(truck.model || 'Truck')}` +
            `<br>Updated: ${escapeHtml(truck.location_updated_at || 'Unknown')}`
        ).addTo(layers.Trucks);
    });

    data.routes.forEach((route) => {
        let waypoints = route.waypoints_json;
        if (typeof waypoints === 'string') {
            try { waypoints = JSON.parse(waypoints); } catch { waypoints = {}; }
        }
        if (Array.isArray(waypoints)) waypoints = { pins: waypoints };
        if (!waypoints || typeof waypoints !== 'object') return;

        const road = Array.isArray(waypoints.road) ? waypoints.road.map(point).filter(Boolean) : [];
        if (road.length > 1) {
            L.polyline(road, { color: '#167447', weight: 4, opacity: .8 })
                .bindPopup(`<strong>Route: ${escapeHtml(route.zone_name)}</strong>`)
                .addTo(layers.Routes);
            bounds.push(...road);
        }

        ['pins', 'mrf'].forEach((key) => {
            (Array.isArray(waypoints[key]) ? waypoints[key] : []).forEach((waypoint, index) => {
                const position = point(waypoint);
                if (!position) return;
                bounds.push(position);
                L.circleMarker(position, { color: '#a45b12', fillColor: '#f0a340', fillOpacity: .9, radius: 6 })
                    .bindPopup(`<strong>${escapeHtml(route.zone_name)}</strong><br>Waypoint ${index + 1}`)
                    .addTo(layers.Routes);
            });
        });
    });

    data.mrfLocations.forEach((location) => {
        const position = point(location);
        if (!position) return;
        bounds.push(position);
        L.marker(position).bindPopup(
            `<strong>${escapeHtml(location.name)}</strong><br>${escapeHtml(location.address || 'MRF site')}`
        ).addTo(layers['MRF sites']);
    });

    L.control.layers(null, layers).addTo(map);
    if (bounds.length > 0) map.fitBounds(bounds, { padding: [28, 28], maxZoom: 15 });

    document.getElementById('truck-count').textContent = `Trucks: ${data.trucks.length}`;
    document.getElementById('route-count').textContent = `Routes: ${data.routes.length}`;
    document.getElementById('mrf-count').textContent = `MRF sites: ${data.mrfLocations.length}`;

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        })[character]);
    }
</script>
</body>
</html>
