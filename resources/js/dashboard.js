import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const colors = {
    leaf: '#2F6B4F',
    leafLight: 'rgba(47, 107, 79, 0.12)',
    harvest: '#D79B32',
    harvestLight: 'rgba(232, 185, 93, 0.22)',
    expense: '#B85C4A',
    grid: '#E8E5DA',
    text: '#6B756D',
};

function parseChartData(element) {
    if (!element?.dataset.chart) {
        return null;
    }

    try {
        return JSON.parse(element.dataset.chart);
    } catch (error) {
        console.error('Data grafik dashboard tidak dapat dibaca.', error);

        return null;
    }
}

function initializeFinancialChart() {
    const canvas = document.querySelector('[data-financial-chart]');
    const chartData = parseChartData(canvas);

    if (!canvas || !chartData) {
        return;
    }

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: chartData.income,
                    borderColor: colors.leaf,
                    backgroundColor: colors.leafLight,
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: 'Pengeluaran',
                    data: chartData.expenses,
                    borderColor: colors.expense,
                    backgroundColor: 'rgba(184, 92, 74, 0.08)',
                    fill: true,
                    tension: 0.35,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${currency.format(context.parsed.y)}`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: colors.text } },
                y: {
                    beginAtZero: true,
                    grid: { color: colors.grid },
                    ticks: {
                        color: colors.text,
                        callback: (value) => currency.format(value),
                    },
                },
            },
        },
    });
}

function initializeHarvestChart() {
    const canvas = document.querySelector('[data-harvest-chart]');
    const chartData = parseChartData(canvas);

    if (!canvas || !chartData) {
        return;
    }

    const palette = ['#2F6B4F', '#D79B32', '#6C9FB0', '#9C7665', '#799B54'];

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: chartData.datasets.map((dataset, index) => ({
                ...dataset,
                backgroundColor: palette[index % palette.length],
                borderRadius: 5,
                maxBarThickness: 28,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${new Intl.NumberFormat('id-ID', {
                            maximumFractionDigits: 2,
                        }).format(context.parsed.y)} ${context.dataset.label}`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: colors.text } },
                y: { beginAtZero: true, grid: { color: colors.grid }, ticks: { color: colors.text } },
            },
        },
    });
}

function setStatus(element, message, isError = false) {
    if (!element) {
        return;
    }

    element.textContent = message;
    element.classList.toggle('text-rose-700', isError);
    element.classList.toggle('text-gray-600', !isError);
}

function updateWeather(weather, locationStatus) {
    const panel = document.querySelector('[data-weather-panel]');
    const message = document.querySelector('[data-weather-message]');

    if (!panel || !message) {
        return;
    }

    if (weather.status !== 'success') {
        const descriptions = {
            no_location: 'Tambahkan lahan untuk melihat cuaca.',
            no_coordinates: locationStatus === 'no_locations'
                ? 'Belum ada lahan pada akun ini.'
                : 'Pilih lahan dengan koordinat atau tambahkan titik pada halaman Lahan.',
            unavailable: weather.message ?? 'Data cuaca sementara tidak tersedia. Coba lagi sebentar lagi.',
        };
        panel.classList.add('hidden');
        setStatus(message, descriptions[weather.status] ?? 'Data cuaca belum tersedia.', weather.status === 'unavailable');

        return;
    }

    const weatherIcon = document.querySelector('[data-weather-icon]');
    const weatherIcons = {
        sun: '☀️',
        'cloud-sun': '🌤️',
        cloud: '☁️',
        rain: '🌧️',
        storm: '⛈️',
    };

    panel.classList.remove('hidden');
    const freshness = weather.stale
        ? 'Data tersimpan · mungkin kedaluwarsa'
        : weather.data_status === 'cached'
            ? 'Data tersimpan · masih baru'
            : 'Data terbaru dari Open-Meteo';
    setStatus(message, `${weather.location} · ${freshness}`, Boolean(weather.stale));
    if (weatherIcon) {
        weatherIcon.textContent = weatherIcons[weather.icon] ?? '🌡️';
    }
    document.querySelector('[data-weather-temperature]').textContent = weather.temperature === null
        ? '—'
        : `${weather.temperature}${weather.units?.temperature ?? '°C'}`;
    document.querySelector('[data-weather-condition]').textContent = weather.condition ?? 'Kondisi tidak tersedia';
    document.querySelector('[data-weather-humidity]').textContent = weather.humidity === null
        ? '—'
        : `${weather.humidity}%`;
    document.querySelector('[data-weather-rain]').textContent = weather.rain_probability === null
        ? '—'
        : `${weather.rain_probability}%`;
    document.querySelector('[data-weather-wind]').textContent = weather.wind_speed === null
        ? '—'
        : `${weather.wind_speed} ${weather.units?.wind_speed ?? 'km/jam'}${weather.wind_direction === null ? '' : ` · ${weather.wind_direction}°`}`;
    const apparentTemperature = document.querySelector('[data-weather-apparent]');
    if (apparentTemperature) {
        apparentTemperature.textContent = weather.apparent_temperature === null
            ? '—'
            : `${weather.apparent_temperature}${weather.units?.temperature ?? '°C'}`;
    }
    const precipitation = document.querySelector('[data-weather-precipitation]');
    if (precipitation) {
        precipitation.textContent = weather.precipitation === null
            ? '—'
            : `${weather.precipitation} ${weather.units?.precipitation ?? 'mm'}`;
    }

    const updatedAt = document.querySelector('[data-weather-updated]');

    if (updatedAt) {
        updatedAt.textContent = weather.fetched_at
            ? `Diambil ${new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short',
                timeZone: 'UTC',
            }).format(new Date(weather.fetched_at))} UTC${weather.timezone ? ` · waktu prakiraan ${weather.timezone}` : ''}`
            : 'Waktu pembaruan tidak tersedia';
    }

    const forecast = document.querySelector('[data-weather-forecast]');

    if (forecast) {
        const days = (weather.forecast ?? []).map((day) => {
            const item = document.createElement('li');
            item.className = 'rounded-xl bg-white/70 px-3 py-3 text-center';

            const date = document.createElement('span');
            date.className = 'block text-xs font-semibold text-gray-600';
            date.textContent = new Intl.DateTimeFormat('id-ID', {
                weekday: 'short',
                timeZone: 'UTC',
            }).format(new Date(`${day.date}T00:00:00Z`));

            const description = document.createElement('span');
            description.className = 'mt-1 block text-xs text-gray-700';
            description.textContent = day.rain_probability === null || day.rain_probability === undefined
                ? (day.condition ?? '—')
                : `${day.condition ?? 'Cuaca'} · Hujan ${day.rain_probability}% · ${day.precipitation ?? 0} mm`;

            const temperature = document.createElement('span');
            temperature.className = 'mt-1 block text-xs font-bold text-gray-900';
            temperature.textContent = day.temperature_max === null || day.temperature_min === null
                ? '—'
                : `${day.temperature_min}${weather.units?.temperature ?? '°C'} / ${day.temperature_max}${weather.units?.temperature ?? '°C'}`;

            item.append(date, description, temperature);

            return item;
        });

        if (days.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'col-span-full rounded-xl bg-white/70 px-3 py-4 text-center text-xs text-gray-600';
            empty.textContent = 'Prakiraan beberapa hari belum tersedia.';
            days.push(empty);
        }

        forecast.replaceChildren(...days);
    }

    const hourlyForecast = document.querySelector('[data-weather-hourly]');
    if (hourlyForecast) {
        const hours = (weather.hourly ?? []).map((hour) => {
            const item = document.createElement('li');
            item.className = 'min-w-24 rounded-xl bg-white/70 px-3 py-3 text-center';
            const time = document.createElement('span');
            time.className = 'block text-xs font-semibold text-gray-600';
            time.textContent = hour.time.slice(11, 16);
            const temperature = document.createElement('span');
            temperature.className = 'mt-1 block text-sm font-bold text-gray-900';
            temperature.textContent = hour.temperature === null
                ? '—'
                : `${hour.temperature}${weather.units?.temperature ?? '°C'}`;
            const rain = document.createElement('span');
            rain.className = 'mt-1 block text-xs text-gray-600';
            rain.textContent = hour.rain_probability === null ? 'Hujan —' : `Hujan ${hour.rain_probability}%`;
            item.append(time, temperature, rain);
            return item;
        });
        hourlyForecast.replaceChildren(...hours);
    }

    const advice = document.querySelector('[data-weather-advice]');
    if (advice) {
        const today = (weather.forecast ?? []).find((day) => day.date === weather.current_time?.slice(0, 10));
        if (!today || today.rain_probability === null) {
            advice.textContent = 'Panduan hujan belum tersedia karena prakiraan belum lengkap.';
        } else if (today.rain_probability >= 60 || (today.precipitation ?? 0) >= 5) {
            advice.textContent = `Prakiraan menunjukkan peluang hujan ${today.rain_probability}% dan curah hujan ${today.precipitation ?? 0} mm. Pertimbangkan menunda penyemprotan atau panen terbuka.`;
        } else if (today.rain_probability >= 30 || (today.precipitation ?? 0) > 0) {
            advice.textContent = `Ada kemungkinan hujan (${today.rain_probability}%, sekitar ${today.precipitation ?? 0} mm). Pantau perubahan cuaca sebelum penyemprotan.`;
        } else {
            advice.textContent = 'Prakiraan hari ini menunjukkan peluang hujan rendah. Tetap pantau cuaca lokal sebelum kegiatan penting.';
        }
    }
}

function makePopup(land) {
    const content = document.createElement('div');
    const title = document.createElement('strong');
    title.textContent = land.name;
    const location = document.createElement('p');
    location.textContent = land.location;
    const details = document.createElement('p');
    details.textContent = `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(land.area)} ${land.area_unit} · ${land.status.replaceAll('_', ' ')}`;
    const crops = document.createElement('p');
    crops.textContent = land.crops?.length ? `Tanaman: ${land.crops.join(', ')}` : 'Belum ada tanaman';
    const edit = document.createElement('a');
    edit.href = land.edit_url;
    edit.textContent = 'Edit lahan';
    edit.className = 'mt-2 inline-block font-semibold text-emerald-800 underline';
    const maps = document.createElement('a');
    maps.href = `https://www.google.com/maps?q=${encodeURIComponent(`${land.latitude},${land.longitude}`)}`;
    maps.target = '_blank';
    maps.rel = 'noopener noreferrer';
    maps.textContent = 'Buka di Google Maps';
    maps.className = 'ml-3 inline-block font-semibold text-emerald-800 underline';
    content.append(title, location, details, crops, edit, maps);

    return content;
}

const defaultMapCenter = [-2.5, 118];
const defaultMapZoom = 5;

function initializeFarmMap() {
    const button = document.querySelector('[data-load-location]');
    const mapElement = document.querySelector('[data-farm-map]');

    if (!button || !mapElement) {
        return;
    }

    const statusElement = document.querySelector('[data-location-message]');
    const mapFallback = document.querySelector('[data-map-fallback]');
    const tileMessage = document.querySelector('[data-tile-message]');
    const locationList = document.querySelector('[data-map-location-list]');
    const landSelect = document.querySelector('[data-weather-land-select]');
    const listSearch = document.querySelector('[data-map-search]');
    const statusFilter = document.querySelector('[data-map-status-filter]');
    const fitMapButton = document.querySelector('[data-map-fit]');
    const fullscreenButton = document.querySelector('[data-map-fullscreen]');
    const satelliteMapButton = document.querySelector('[data-map-satellite]');
    let map = null;
    let markerLayer = null;
    let lands = [];
    let displayedMapLand = null;

    function initializeMap() {
        if (map) {
            return;
        }

        map = L.map(mapElement, { scrollWheelZoom: false }).setView(defaultMapCenter, defaultMapZoom);
        markerLayer = L.featureGroup().addTo(map);
        const osmLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
        }).addTo(map);
        let tileErrorLogged = false;
        osmLayer.on('tileerror', (event) => {
            if (!tileErrorLogged) {
                console.error('Tile OpenStreetMap gagal dimuat.', {
                    url: event.tile?.src,
                    error: event.error,
                    guidance: 'Periksa koneksi serta HTTP status, CSP, atau mixed-content error pada DevTools Network dan Console.',
                });
                tileErrorLogged = true;
            }

            if (tileMessage) {
                tileMessage.textContent = 'Ubin OpenStreetMap gagal dimuat. Periksa koneksi dan DevTools Network/Console untuk mengetahui error HTTP, CSP, atau mixed content. Penanda dan daftar lahan tetap tersedia.';
                tileMessage.classList.remove('hidden');
            }
        });
    }

    initializeMap();
    window.requestAnimationFrame(() => map.invalidateSize({ pan: false }));

    function renderLandList() {
        if (!locationList) {
            return;
        }

        const search = listSearch?.value.trim().toLocaleLowerCase('id-ID') ?? '';
        const status = statusFilter?.value ?? '';
        const visibleLands = lands.filter((land) => {
            const matchesSearch = `${land.name} ${land.location}`.toLocaleLowerCase('id-ID').includes(search);
            return matchesSearch && (!status || land.status === status);
        });

        if (visibleLands.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'py-4 text-sm text-gray-500';
            empty.textContent = lands.length === 0
                ? 'Belum ada lahan. Tambahkan lahan untuk memulai.'
                : 'Tidak ada lahan yang sesuai dengan pencarian.';
            locationList.replaceChildren(empty);
            return;
        }

        locationList.replaceChildren(...visibleLands.map((land) => {
            const item = document.createElement('li');
            item.className = 'flex flex-col gap-2 border-t border-gray-100 py-3 first:border-0 sm:flex-row sm:items-start sm:justify-between';
            const details = document.createElement('div');
            const name = document.createElement('button');
            name.type = 'button';
            name.className = 'text-left font-semibold text-gray-900 underline decoration-transparent underline-offset-2 hover:decoration-current';
            name.textContent = land.name;
            name.addEventListener('click', () => {
                if (land.has_coordinates) {
                    centerOnLand(land);
                }
            });
            const location = document.createElement('p');
            location.className = 'text-sm text-gray-500';
            location.textContent = `${land.location} · ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(land.area)} ${land.area_unit}`;
            const links = document.createElement('div');
            links.className = 'mt-1 flex flex-wrap gap-3 text-xs font-semibold';
            const edit = document.createElement('a');
            edit.href = land.edit_url;
            edit.textContent = 'Edit lahan';
            edit.className = 'text-emerald-800 underline';
            links.append(edit);
            if (land.has_coordinates) {
                const maps = document.createElement('a');
                maps.href = `https://www.google.com/maps?q=${encodeURIComponent(`${land.latitude},${land.longitude}`)}`;
                maps.target = '_blank';
                maps.rel = 'noopener noreferrer';
                maps.textContent = 'Google Maps';
                maps.className = 'text-emerald-800 underline';
                links.append(maps);
            }
            details.append(name, location, links);
            const state = document.createElement('span');
            const active = land.status === 'aktif';
            const tone = active
                ? 'bg-emerald-50 text-emerald-800'
                : land.status === 'persiapan'
                    ? 'bg-amber-50 text-amber-800'
                    : 'bg-gray-100 text-gray-700';
            state.className = `shrink-0 rounded-full px-2 py-1 text-xs font-semibold ${tone}`;
            state.textContent = `${land.status.replaceAll('_', ' ')}${land.has_coordinates ? '' : ' · tanpa titik'}`;
            item.append(details, state);
            return item;
        }));
    }

    function renderMarkers() {
        const mappedLands = lands.filter((land) => land.has_coordinates);
        if (mappedLands.length === 0) {
            markerLayer.clearLayers();
            displayedMapLand = null;
            map.setView(defaultMapCenter, defaultMapZoom);
            if (mapFallback) {
                mapFallback.classList.remove('hidden');
                mapFallback.textContent = lands.length
                    ? 'Belum ada titik koordinat. Tambahkan atau perbarui titik pada halaman Lahan.'
                    : 'Belum ada lahan untuk ditampilkan. Tampilan awal peta Indonesia bukan lokasi lahan atau cuaca.';
            }
            return;
        }

        mapFallback?.classList.add('hidden');
        markerLayer.clearLayers();
        mappedLands.forEach((land) => {
            const markerColor = land.status === 'aktif' ? '#2F6B4F' : land.status === 'persiapan' ? '#D79B32' : '#6B7280';
            L.circleMarker([land.latitude, land.longitude], {
                radius: 9,
                color: '#FFFFFF',
                weight: 2,
                fillColor: markerColor,
                fillOpacity: 0.95,
            }).bindPopup(makePopup(land)).addTo(markerLayer);
        });
        displayedMapLand = mappedLands.find((land) => String(land.id) === landSelect?.value) ?? mappedLands[0];
        map.fitBounds(markerLayer.getBounds(), { padding: [24, 24], maxZoom: 13 });
        window.requestAnimationFrame(() => map.invalidateSize({ pan: false }));
    }

    function centerOnLand(land) {
        if (!land.has_coordinates) {
            return;
        }
        displayedMapLand = land;
        renderMarkers();
        displayedMapLand = land;
        map.setView([land.latitude, land.longitude], Math.max(map.getZoom(), 15));
        markerLayer.eachLayer((marker) => {
            const point = marker.getLatLng();
            if (point.lat === land.latitude && point.lng === land.longitude) {
                marker.openPopup();
            }
        });
    }

    function updateLandSelect(selectedLandId) {
        if (!landSelect) {
            return;
        }
        const coordinateLands = lands.filter((land) => land.has_coordinates);
        landSelect.replaceChildren(...coordinateLands.map((land) => {
            const option = document.createElement('option');
            option.value = String(land.id);
            option.textContent = `${land.name} · ${land.location}`;
            option.selected = land.id === selectedLandId;
            return option;
        }));
        landSelect.disabled = coordinateLands.length === 0;
    }

    async function loadLocationData(selectedLandId = null) {
        button.disabled = true;
        button.textContent = 'Memuat peta dan cuaca…';
        setStatus(statusElement, 'Memuat koordinat lahan tersimpan dan prakiraan cuaca…');
        try {
            const body = new URLSearchParams();
            if (selectedLandId) {
                body.set('land_id', String(selectedLandId));
            }
            const response = await fetch(mapElement.dataset.endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body,
            });
            if (!response.ok) {
                const error = new Error(`Permintaan dashboard gagal (${response.status}).`);
                error.status = response.status;
                throw error;
            }
            const result = await response.json();
            if (!result || !Array.isArray(result.locations) || typeof result.weather?.status !== 'string') {
                throw new Error('Respons dashboard tidak sesuai kontrak.');
            }
            lands = result.locations;
            updateLandSelect(result.selected_land_id);
            renderLandList();
            renderMarkers();
            const messages = {
                no_locations: 'Tambahkan lahan untuk menampilkan peta dan cuaca.',
                no_coordinates: 'Belum ada koordinat tersimpan. Tambahkan titik pada halaman Lahan.',
                partial: 'Peta menampilkan semua lahan yang memiliki koordinat tersimpan.',
                success: 'Peta menampilkan semua lahan yang memiliki koordinat tersimpan.',
            };
            setStatus(statusElement, messages[result.location_status] ?? 'Status lokasi tidak tersedia.');
            updateWeather(result.weather, result.location_status);
            button.textContent = 'Perbarui peta dan cuaca';
            if (result.location_status === 'partial' || result.weather.status === 'unavailable') {
                setStatus(statusElement, `${messages[result.location_status] ?? ''} ${result.weather.message ?? ''}`.trim(), result.weather.status === 'unavailable');
            }
        } catch (error) {
            console.error('Peta atau cuaca dashboard gagal dimuat.', error);
            const isRateLimited = error.status === 429;
            setStatus(statusElement, isRateLimited
                ? 'Permintaan terlalu sering. Tunggu sebentar sebelum mencoba lagi.'
                : 'Peta atau cuaca tidak dapat dimuat. Periksa koneksi, lalu coba lagi.', true);
            updateWeather({ status: 'unavailable' }, 'unavailable');
            button.textContent = 'Coba lagi';
        } finally {
            button.disabled = false;
        }
    }

    button.addEventListener('click', () => loadLocationData(landSelect?.value || null));
    landSelect?.addEventListener('change', () => loadLocationData(landSelect.value));
    listSearch?.addEventListener('input', renderLandList);
    statusFilter?.addEventListener('change', renderLandList);
    fitMapButton?.addEventListener('click', () => {
        if (map && markerLayer?.getLayers().length) {
            map.fitBounds(markerLayer.getBounds(), { padding: [24, 24], maxZoom: 13 });
        }
    });
    fullscreenButton?.addEventListener('click', async () => {
        try {
            if (!document.fullscreenElement) {
                await mapElement.requestFullscreen();
            } else {
                await document.exitFullscreen();
            }
            window.requestAnimationFrame(() => map?.invalidateSize({ pan: false }));
        } catch (error) {
            console.error('Mode layar penuh peta tidak tersedia.', error);
            setStatus(statusElement, 'Mode layar penuh tidak didukung oleh browser ini.', true);
        }
    });
    satelliteMapButton?.addEventListener('click', () => {
        const selectedLand = landSelect?.value
            ? lands.find((land) => String(land.id) === landSelect.value)
            : displayedMapLand;

        if (!selectedLand?.has_coordinates
            || !Number.isFinite(selectedLand.latitude)
            || !Number.isFinite(selectedLand.longitude)
            || selectedLand.latitude < -90
            || selectedLand.latitude > 90
            || selectedLand.longitude < -180
            || selectedLand.longitude > 180
        ) {
            setStatus(statusElement, 'Pilih lahan dengan koordinat valid atau tampilkan lokasinya pada peta terlebih dahulu.', true);

            return;
        }

        const mapsUrl = new URL('https://www.google.com/maps');
        mapsUrl.searchParams.set('api', '1');
        mapsUrl.searchParams.set('map_action', 'map');
        mapsUrl.searchParams.set('center', `${selectedLand.latitude},${selectedLand.longitude}`);
        mapsUrl.searchParams.set('zoom', '18');
        mapsUrl.searchParams.set('basemap', 'satellite');

        window.open(mapsUrl.toString(), '_blank', 'noopener,noreferrer');
    });
}

initializeFinancialChart();
initializeHarvestChart();
initializeFarmMap();
