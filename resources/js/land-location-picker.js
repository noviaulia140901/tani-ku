import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const photonSearchEndpoint = 'https://photon.komoot.io/api/';
const minimumSearchLength = 3;
const searchDebounceMilliseconds = 450;
const maximumSuggestions = 5;

function validCoordinates(latitude, longitude) {
    return Number.isFinite(latitude)
        && Number.isFinite(longitude)
        && latitude >= -90
        && latitude <= 90
        && longitude >= -180
        && longitude <= 180;
}

function parseCoordinate(value) {
    return typeof value === 'string' && value.trim() !== '' ? Number(value) : Number.NaN;
}

function cleanAddressPart(value) {
    return typeof value === 'string' ? value.trim() : '';
}

function normalizedAddressPart(value) {
    return cleanAddressPart(value).toLocaleLowerCase('id-ID').replace(/\s+/g, ' ');
}

function addressParts(properties) {
    const street = [
        cleanAddressPart(properties.housenumber),
        cleanAddressPart(properties.street),
    ].filter(Boolean).join(' ');

    const parts = [
        street,
        properties.locality,
        properties.neighbourhood,
        properties.neighborhood,
        properties.suburb,
        properties.village,
        properties.hamlet,
        properties.district,
        properties.town,
        properties.city,
        properties.municipality,
        properties.county,
        properties.state,
        properties.postcode,
        properties.country,
    ].map(cleanAddressPart);
    const seen = new Set();

    return parts.filter((part) => {
        const normalizedPart = normalizedAddressPart(part);

        if (normalizedPart === '' || seen.has(normalizedPart)) {
            return false;
        }

        seen.add(normalizedPart);

        return true;
    });
}

function addressTitle(properties, details) {
    return cleanAddressPart(properties.name) || details[0] || '';
}

function formattedAddress(feature) {
    const properties = feature.properties;
    const details = addressParts(properties);
    const title = addressTitle(properties, details);
    const titleKey = normalizedAddressPart(title);
    const parts = [
        title,
        ...details.filter((part) => normalizedAddressPart(part) !== titleKey),
    ];

    return parts.filter(Boolean).join(', ').slice(0, 255).trim();
}

function suggestionDetails(feature) {
    const details = addressParts(feature.properties);
    const titleKey = normalizedAddressPart(addressTitle(feature.properties, details));

    return details
        .filter((part) => normalizedAddressPart(part) !== titleKey)
        .join(', ');
}

function initializeLandLocationPicker() {
    const container = document.querySelector('[data-land-location-picker]');

    if (!container) {
        return;
    }

    const locationInput = container.querySelector('[data-land-location-search]');
    const suggestionsList = container.querySelector('[data-land-location-suggestions]');
    const statusElement = container.querySelector('[data-land-location-status]');
    const latitudeInput = container.querySelector('[data-land-latitude]');
    const longitudeInput = container.querySelector('[data-land-longitude]');
    const mapElement = container.querySelector('[data-land-map]');
    const clearCoordinatesInput = container.querySelector('[data-clear-land-coordinates]');
    const geolocationButton = container.querySelector('[data-land-geolocation]');
    const satelliteMapButton = container.querySelector('[data-land-satellite-map]');

    if (!locationInput || !suggestionsList || !statusElement || !latitudeInput || !longitudeInput || !mapElement) {
        return;
    }

    const originalCoordinates = {
        latitude: parseCoordinate(latitudeInput.dataset.savedLatitude),
        longitude: parseCoordinate(longitudeInput.dataset.savedLongitude),
    };
    let map = null;
    let marker = null;
    let searchTimer = null;
    let searchController = null;
    let suggestions = [];
    let activeSuggestionIndex = -1;
    let tileFailureShown = false;
    let searchTimeout = null;
    let searchTimedOut = false;

    function setStatus(message, isError = false) {
        statusElement.textContent = message;
        statusElement.classList.toggle('text-red-700', isError);
        statusElement.classList.toggle('text-gray-600', !isError);
    }

    function hideSuggestions() {
        suggestionsList.replaceChildren();
        suggestionsList.classList.add('hidden');
        locationInput.setAttribute('aria-expanded', 'false');
        locationInput.removeAttribute('aria-activedescendant');
        suggestions = [];
        activeSuggestionIndex = -1;
    }

    function showSuggestions(features) {
        suggestions = features;
        activeSuggestionIndex = -1;
        suggestionsList.replaceChildren(...features.map((feature, index) => {
            const option = document.createElement('li');
            option.id = `land-location-option-${index}`;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.tabIndex = -1;
            option.className = 'cursor-pointer border-b border-gray-100 px-4 py-3 last:border-0 hover:bg-emerald-50 focus:bg-emerald-50';

            const title = document.createElement('p');
            title.className = 'font-semibold text-gray-900';
            title.textContent = feature.properties.name || formattedAddress(feature);

            const details = document.createElement('p');
            details.className = 'mt-1 text-sm text-gray-600';
            details.textContent = suggestionDetails(feature);

            option.append(title, details);
            option.addEventListener('mousedown', (event) => event.preventDefault());
            option.addEventListener('click', () => selectSuggestion(index));

            return option;
        }));
        suggestionsList.classList.remove('hidden');
        locationInput.setAttribute('aria-expanded', 'true');
    }

    function setActiveSuggestion(index) {
        if (suggestions.length === 0) {
            return;
        }

        activeSuggestionIndex = (index + suggestions.length) % suggestions.length;
        [...suggestionsList.children].forEach((option, optionIndex) => {
            const isActive = optionIndex === activeSuggestionIndex;
            option.setAttribute('aria-selected', String(isActive));
            option.classList.toggle('bg-emerald-50', isActive);
        });

        const activeOption = suggestionsList.children[activeSuggestionIndex];
        locationInput.setAttribute('aria-activedescendant', activeOption.id);
        activeOption.scrollIntoView({ block: 'nearest' });
    }

    function setCoordinates(latitude, longitude, message, updateFormCoordinates = true) {
        if (!validCoordinates(latitude, longitude)) {
            setStatus('Koordinat lokasi tidak valid. Pilih saran lain atau klik titik yang berbeda pada peta.', true);

            return;
        }

        if (updateFormCoordinates) {
            latitudeInput.value = latitude.toFixed(7);
            longitudeInput.value = longitude.toFixed(7);
        }

        if (clearCoordinatesInput) {
            clearCoordinatesInput.checked = false;
        }

        mapElement.classList.remove('hidden');

        if (!map) {
            map = L.map(mapElement, { scrollWheelZoom: false }).setView([latitude, longitude], 16);
            const tileLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
            }).addTo(map);
            marker = L.marker([latitude, longitude], { draggable: true, autoPan: true }).addTo(map);
            tileLayer.on('tileerror', () => {
                if (!tileFailureShown) {
                    tileFailureShown = true;
                    setStatus('Sebagian ubin peta gagal dimuat. Anda tetap dapat memilih lokasi dengan marker.', true);
                }
            });
            map.on('click', (event) => {
                setCoordinates(event.latlng.lat, event.latlng.lng, 'Titik kebun diperbarui dari lokasi yang Anda pilih pada peta.');
            });
            marker.on('dragend', () => {
                const position = marker.getLatLng();
                setCoordinates(position.lat, position.lng, 'Titik kebun diperbarui dari marker yang Anda geser.');
            });
            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(() => {
                    map.invalidateSize({ pan: false });
                });
            });
        } else {
            marker.setLatLng([latitude, longitude]);
            map.setView([latitude, longitude], Math.max(map.getZoom(), 16));
            window.requestAnimationFrame(() => {
                map.invalidateSize({ pan: false });
            });
        }

        setStatus(message);
    }

    function selectSuggestion(index) {
        const feature = suggestions[index];
        const coordinates = feature?.geometry?.coordinates;

        if (!Array.isArray(coordinates)
            || !Number.isFinite(coordinates[0])
            || !Number.isFinite(coordinates[1])
        ) {
            hideSuggestions();
            setStatus('Saran lokasi tidak memiliki koordinat yang valid. Silakan pilih hasil lain.', true);

            return;
        }

        locationInput.value = formattedAddress(feature);
        hideSuggestions();
        setCoordinates(coordinates[1], coordinates[0], 'Lokasi terpilih. Geser marker atau klik peta untuk menyesuaikan titik kebun.');
    }

    async function searchLocations(query) {
        searchController = new AbortController();
        searchTimedOut = false;
        searchTimeout = window.setTimeout(() => {
            searchTimedOut = true;
            searchController?.abort();
        }, 8000);
        const requestUrl = new URL(photonSearchEndpoint);
        requestUrl.searchParams.set('q', query);
        requestUrl.searchParams.set('limit', String(maximumSuggestions));
        requestUrl.searchParams.set('countrycode', 'ID');

        try {
            const response = await fetch(requestUrl, {
                headers: { Accept: 'application/json' },
                signal: searchController.signal,
            });

            if (!response.ok) {
                throw new Error(`Pencarian lokasi gagal (${response.status}).`);
            }

            const result = await response.json();

            if (!Array.isArray(result.features)) {
                throw new Error('Respons pencarian lokasi tidak sesuai format.');
            }

            const validFeatures = result.features.filter((feature) => {
                const coordinates = feature?.geometry?.coordinates;
                const hasAddress = feature?.properties
                    && (cleanAddressPart(feature.properties.name) !== ''
                        || addressParts(feature.properties).length > 0);

                return hasAddress
                    && Array.isArray(coordinates)
                    && validCoordinates(coordinates[1], coordinates[0]);
            }).slice(0, maximumSuggestions);

            if (validFeatures.length === 0) {
                hideSuggestions();
                setStatus('Tidak ada saran lokasi yang ditemukan. Coba nama desa, kecamatan, atau kota lain.');

                return;
            }

            showSuggestions(validFeatures);
            setStatus(`${validFeatures.length} saran lokasi tersedia. Gunakan tombol panah dan Enter untuk memilih.`);
        } catch (error) {
            if (error.name === 'AbortError') {
                if (searchTimedOut) {
                    hideSuggestions();
                    setStatus('Pencarian lokasi melewati batas waktu. Periksa koneksi atau masukkan koordinat secara manual.', true);
                }

                return;
            }

            console.error('Pencarian alamat Photon gagal.', error);
            hideSuggestions();
            setStatus('Saran alamat tidak dapat dimuat. Periksa koneksi lalu coba lagi.', true);
        } finally {
            window.clearTimeout(searchTimeout);
            searchTimeout = null;
        }
    }

    function updateMapFromManualCoordinates() {
        const latitude = parseCoordinate(latitudeInput.value);
        const longitude = parseCoordinate(longitudeInput.value);

        if (latitudeInput.value.trim() === '' && longitudeInput.value.trim() === '') {
            mapElement.classList.add('hidden');
            if (clearCoordinatesInput) {
                clearCoordinatesInput.checked = false;
            }
            setStatus('Koordinat dikosongkan. Simpan formulir untuk menghapus titik tersimpan.');

            return;
        }

        if (!validCoordinates(latitude, longitude)) {
            setStatus('Masukkan latitude dan longitude yang valid secara berpasangan.', true);

            return;
        }

        setCoordinates(latitude, longitude, 'Titik lokasi diperbarui dari koordinat yang dimasukkan.');
    }

    locationInput.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        window.clearTimeout(searchTimeout);
        searchController?.abort();
        hideSuggestions();

        const query = locationInput.value.trim();

        if (query.length < minimumSearchLength) {
            setStatus(`Ketik minimal ${minimumSearchLength} karakter untuk mencari saran alamat.`);

            return;
        }

        setStatus('Mencari saran alamat…');
        searchTimer = window.setTimeout(() => searchLocations(query), searchDebounceMilliseconds);
    });

    locationInput.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' && suggestions.length > 0) {
            event.preventDefault();
            setActiveSuggestion(activeSuggestionIndex + 1);
        } else if (event.key === 'ArrowUp' && suggestions.length > 0) {
            event.preventDefault();
            setActiveSuggestion(activeSuggestionIndex < 0 ? suggestions.length - 1 : activeSuggestionIndex - 1);
        } else if (event.key === 'Enter' && activeSuggestionIndex >= 0) {
            event.preventDefault();
            selectSuggestion(activeSuggestionIndex);
        } else if (event.key === 'Escape') {
            hideSuggestions();
        }
    });

    locationInput.addEventListener('blur', () => {
        window.setTimeout(hideSuggestions, 150);
    });

    latitudeInput.addEventListener('change', updateMapFromManualCoordinates);
    longitudeInput.addEventListener('change', updateMapFromManualCoordinates);

    satelliteMapButton?.addEventListener('click', () => {
        const latitude = parseCoordinate(latitudeInput.value);
        const longitude = parseCoordinate(longitudeInput.value);

        if (!validCoordinates(latitude, longitude)) {
            setStatus('Masukkan latitude dan longitude yang valid sebelum membuka Google Maps.', true);

            return;
        }

        const mapsUrl = new URL('https://www.google.com/maps');
        mapsUrl.searchParams.set('api', '1');
        mapsUrl.searchParams.set('map_action', 'map');
        mapsUrl.searchParams.set('center', `${latitude},${longitude}`);
        mapsUrl.searchParams.set('zoom', '18');
        mapsUrl.searchParams.set('basemap', 'satellite');

        window.open(mapsUrl.toString(), '_blank', 'noopener,noreferrer');
        setStatus('Membuka tampilan satelit Google Maps pada koordinat yang dipilih.');
    });

    geolocationButton?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setStatus('Browser ini tidak mendukung akses lokasi. Masukkan koordinat secara manual.', true);

            return;
        }

        geolocationButton.disabled = true;
        geolocationButton.textContent = 'Mengambil lokasi…';
        setStatus('Izinkan akses lokasi pada browser. Perangkat harus menggunakan koneksi aman (HTTPS).');
        navigator.geolocation.getCurrentPosition(
            (position) => {
                setCoordinates(
                    position.coords.latitude,
                    position.coords.longitude,
                    'Lokasi perangkat berhasil diambil. Geser marker jika perlu menyesuaikan titik.',
                );
                geolocationButton.disabled = false;
                geolocationButton.textContent = 'Gunakan lokasi perangkat';
            },
            (error) => {
                const messages = {
                    1: 'Izin lokasi ditolak. Izinkan lokasi pada browser atau masukkan koordinat manual.',
                    2: 'Lokasi perangkat tidak tersedia. Coba lagi atau masukkan koordinat manual.',
                    3: 'Pengambilan lokasi terlalu lama. Coba lagi atau masukkan koordinat manual.',
                };
                setStatus(messages[error.code] ?? 'Lokasi perangkat gagal dibaca. Masukkan koordinat secara manual.', true);
                geolocationButton.disabled = false;
                geolocationButton.textContent = 'Gunakan lokasi perangkat';
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 300000 },
        );
    });

    clearCoordinatesInput?.addEventListener('change', () => {
        if (clearCoordinatesInput.checked) {
            latitudeInput.value = '';
            longitudeInput.value = '';
            mapElement.classList.add('hidden');
            setStatus('Titik tersimpan akan dihapus setelah formulir disimpan.');

            return;
        }

        if (validCoordinates(originalCoordinates.latitude, originalCoordinates.longitude)) {
            setCoordinates(originalCoordinates.latitude, originalCoordinates.longitude, 'Titik tersimpan dipulihkan.');
        } else {
            updateMapFromManualCoordinates();
        }
    });

    const initialCoordinates = {
        latitude: parseCoordinate(latitudeInput.value),
        longitude: parseCoordinate(longitudeInput.value),
    };

    if (!clearCoordinatesInput?.checked && validCoordinates(initialCoordinates.latitude, initialCoordinates.longitude)) {
        setCoordinates(initialCoordinates.latitude, initialCoordinates.longitude, 'Titik lokasi tersimpan. Geser marker atau klik peta untuk menyesuaikannya.', false);
    } else {
        mapElement.classList.add('hidden');
        setStatus(`Ketik minimal ${minimumSearchLength} karakter untuk mencari saran alamat.`);
    }
}

initializeLandLocationPicker();
