{{-- Map coordinates of the delivery location (used to cross-check project timesheet GPS).
     Params: $prefix (unique id prefix), $lat, $lng (current values).
     The "Paste" box accepts "-6.2441, 106.7993" or a full Google Maps link that
     contains the coordinates (@lat,lng / ?q=lat,lng / !3dlat!4dlng). --}}
@php
    $prefix = $prefix ?? 'loc';
    $lat    = $lat ?? null;
    $lng    = $lng ?? null;
@endphp
<div class="md:col-span-2 lg:col-span-3">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-1">
            <label for="{{ $prefix }}_paste" class="block text-sm font-medium text-gray-900 mb-1">Paste from Google Maps</label>
            <input type="text" id="{{ $prefix }}_paste" autocomplete="off"
                   placeholder="-6.2441, 106.7993 or a Google Maps link"
                   class="block w-full py-2.5 px-3 border border-gray-300 rounded-md shadow-sm text-sm primary-focus">
            <p id="{{ $prefix }}_paste_hint" class="mt-1 text-xs text-gray-500">In Google Maps, right-click the place and click the coordinates to copy them. Short links (maps.app.goo.gl) cannot be read.</p>
        </div>
        <div>
            <label for="{{ $prefix }}_lat" class="block text-sm font-medium text-gray-900 mb-1">Latitude</label>
            <input type="number" step="any" min="-90" max="90" name="location_latitude" id="{{ $prefix }}_lat"
                   value="{{ old('location_latitude', $lat) }}" placeholder="-6.2441000"
                   class="block w-full py-2.5 px-3 border border-gray-300 rounded-md shadow-sm text-sm primary-focus">
        </div>
        <div>
            <label for="{{ $prefix }}_lng" class="block text-sm font-medium text-gray-900 mb-1">Longitude</label>
            <input type="number" step="any" min="-180" max="180" name="location_longitude" id="{{ $prefix }}_lng"
                   value="{{ old('location_longitude', $lng) }}" placeholder="106.7993000"
                   class="block w-full py-2.5 px-3 border border-gray-300 rounded-md shadow-sm text-sm primary-focus">
        </div>
    </div>
</div>
<script>
(function () {
    const paste = document.getElementById(@json($prefix . '_paste'));
    const lat   = document.getElementById(@json($prefix . '_lat'));
    const lng   = document.getElementById(@json($prefix . '_lng'));
    const hint  = document.getElementById(@json($prefix . '_paste_hint'));
    if (!paste || !lat || !lng) return;

    function parse(text) {
        try { text = decodeURIComponent(String(text || '').trim()); } catch (e) { text = String(text || '').trim(); }
        const patterns = [
            /!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/,                                // place link data part
            /@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/,                                  // .../@lat,lng,zoom
            /[?&](?:q|ll|query|destination)=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/,    // ?q=lat,lng
            /^\s*(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)\s*$/                    // plain "lat, lng"
        ];
        for (const re of patterns) {
            const m = text.match(re);
            if (m) {
                const a = parseFloat(m[1]), b = parseFloat(m[2]);
                if (Math.abs(a) <= 90 && Math.abs(b) <= 180) return [a, b];
            }
        }
        return null;
    }

    paste.addEventListener('input', function () {
        const raw = paste.value.trim();
        if (!raw) { hint.className = 'mt-1 text-xs text-gray-500'; return; }
        const r = parse(raw);
        if (r) {
            lat.value = r[0].toFixed(7);
            lng.value = r[1].toFixed(7);
            hint.textContent = 'Coordinates filled in: ' + lat.value + ', ' + lng.value;
            hint.className = 'mt-1 text-xs text-green-600';
        } else {
            hint.textContent = 'No coordinates found. Paste "latitude, longitude" or a full Google Maps link.';
            hint.className = 'mt-1 text-xs text-red-600';
        }
    });
})();
</script>
