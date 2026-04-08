(function () {
    'use strict';

    /**
     * Initialisiert die Detailansichts-Karte (einzelner Marker).
     * Erwartet ein <div id="rr-event-map" data-lat="..." data-lon="..." data-title="...">.
     */
    function initDetailMap() {
        var el = document.getElementById('rr-event-map');
        if (!el) return;

        var lat = parseFloat(el.getAttribute('data-lat'));
        var lon = parseFloat(el.getAttribute('data-lon'));
        var title = el.getAttribute('data-title') || '';
        if (!lat || !lon) return;

        var map = L.map(el).setView([lat, lon], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>-Mitwirkende',
            maxZoom: 19
        }).addTo(map);
        L.marker([lat, lon]).addTo(map).bindPopup(title).openPopup();
    }

    /**
     * Initialisiert die Listenansichts-Karte (mehrere Marker).
     * Erwartet ein <div id="rr-list-map" data-zoom="..."> mit kind-<span class="rr-map-event"
     * data-lat="..." data-lon="..." data-title="..." data-url="...">.
     */
    function initListMap() {
        var el = document.getElementById('rr-list-map');
        if (!el) return;

        var zoom = parseInt(el.getAttribute('data-zoom') || '11', 10);

        var map = L.map(el).setView([51.163375, 10.447683], zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>-Mitwirkende',
            maxZoom: 19
        }).addTo(map);

        var spans = el.querySelectorAll('.rr-map-event');
        var markers = [];

        spans.forEach(function (span) {
            var lat = parseFloat(span.getAttribute('data-lat'));
            var lon = parseFloat(span.getAttribute('data-lon'));
            var t   = span.getAttribute('data-title') || '';
            var url = span.getAttribute('data-url') || '';
            if (!lat || !lon) return;

            var popup = '<strong>' + t + '</strong>';
            if (url) {
                popup += '<br><a href="' + url + '">Details ansehen</a>';
            }

            var marker = L.marker([lat, lon]).bindPopup(popup);
            marker.addTo(map);
            markers.push(marker);
        });

        if (markers.length === 1) {
            map.setView(markers[0].getLatLng(), zoom);
        } else if (markers.length > 1) {
            var group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.15));
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof L === 'undefined') return;
        initDetailMap();
        initListMap();
    });
})();
