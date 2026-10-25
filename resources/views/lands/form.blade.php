<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header>
                <p class="text-sm font-semibold uppercase text-emerald-800">Data kebun</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-950">{{ $land->exists ? 'Edit Lahan' : 'Tambah Lahan' }}</h1>
                <p class="mt-2 text-gray-600">Isi informasi lahan perkebunan Anda.</p>
            </header>

            <form method="POST" action="{{ $land->exists ? route('lands.update', $land) : route('lands.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                @csrf
                @if ($land->exists)
                    @method('PUT')
                @endif

                <div>
                    <x-input-label for="name" value="Nama lahan *" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('name', $land->name)" required maxlength="255" aria-describedby="name-error" />
                    <x-input-error id="name-error" class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div data-land-location-picker>
                    <x-input-label for="location" value="Lokasi *" />
                    <x-text-input
                        id="location"
                        name="location"
                        type="text"
                        class="mt-1 block min-h-12 w-full text-base"
                        :value="old('location', $land->location)"
                        required
                        maxlength="255"
                        autocomplete="off"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="land-location-suggestions"
                        aria-describedby="location-help location-error"
                        data-land-location-search
                    />
                    <p id="location-help" class="mt-2 text-sm text-gray-500">
                        Ketik minimal 3 karakter untuk mencari alamat. Teks pencarian dikirim ke Photon berbasis OpenStreetMap hanya setelah Anda mulai mencari.
                        <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer" class="font-medium text-emerald-800 underline">© OpenStreetMap contributors</a>
                    </p>
                    <ul
                        id="land-location-suggestions"
                        role="listbox"
                        aria-label="Saran lokasi"
                        data-land-location-suggestions
                        class="mt-1 hidden max-h-64 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg"
                    ></ul>
                    <p data-land-location-status role="status" aria-live="polite" class="mt-2 min-h-5 text-sm text-gray-600"></p>
                    <x-input-error id="location-error" class="mt-2" :messages="$errors->get('location')" />

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="latitude" value="Latitude (opsional)" />
                            <x-text-input
                                id="latitude"
                                name="latitude"
                                type="number"
                                step="any"
                                min="-90"
                                max="90"
                                inputmode="decimal"
                                class="mt-1 block min-h-12 w-full text-base"
                                :value="old('latitude', $land->latitude)"
                                data-land-latitude
                                data-saved-latitude="{{ $land->latitude }}"
                                aria-describedby="coordinates-help latitude-error"
                            />
                            <x-input-error id="latitude-error" class="mt-2" :messages="$errors->get('latitude')" />
                        </div>
                        <div>
                            <x-input-label for="longitude" value="Longitude (opsional)" />
                            <x-text-input
                                id="longitude"
                                name="longitude"
                                type="number"
                                step="any"
                                min="-180"
                                max="180"
                                inputmode="decimal"
                                class="mt-1 block min-h-12 w-full text-base"
                                :value="old('longitude', $land->longitude)"
                                data-land-longitude
                                data-saved-longitude="{{ $land->longitude }}"
                                aria-describedby="coordinates-help longitude-error"
                            />
                            <x-input-error id="longitude-error" class="mt-2" :messages="$errors->get('longitude')" />
                        </div>
                    </div>
                    <p id="coordinates-help" class="mt-2 text-sm text-gray-500">Masukkan koordinat secara manual, pilih pada peta, atau gunakan lokasi perangkat. Latitude dan longitude harus diisi berpasangan.</p>
                    <button type="button" data-land-geolocation class="mt-3 inline-flex min-h-11 items-center justify-center rounded-xl border border-emerald-800 px-4 text-sm font-semibold text-emerald-900 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                        Gunakan lokasi perangkat
                    </button>
                    <button type="button" data-land-satellite-map class="mt-3 inline-flex min-h-11 items-center justify-center rounded-xl border border-emerald-800 px-4 text-sm font-semibold text-emerald-900 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                        Lihat Satelit di Google Maps
                    </button>

                    @if ($land->exists && $land->latitude !== null && $land->longitude !== null)
                        <label class="mt-3 flex min-h-11 items-center gap-3 text-sm text-gray-700">
                            <input
                                type="checkbox"
                                name="clear_coordinates"
                                value="1"
                                @checked(old('clear_coordinates'))
                                data-clear-land-coordinates
                                class="rounded border-gray-300 text-emerald-800 shadow-sm focus:ring-emerald-700"
                            >
                            Hapus titik lokasi tersimpan
                        </label>
                    @endif

                    <div
                        data-land-map
                        @class([
                            'relative isolate mt-4 h-72 min-h-[18rem] w-full overflow-hidden rounded-xl border border-gray-200 bg-[#EAF0E5] sm:h-80 sm:min-h-[20rem]',
                            'hidden' => ! is_numeric(old('latitude', $land->latitude))
                                || ! is_numeric(old('longitude', $land->longitude))
                                || old('clear_coordinates'),
                        ])
                        role="application"
                        aria-label="Peta untuk memilih titik lokasi lahan"
                    ></div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="area" value="Luas *" />
                        <x-text-input id="area" name="area" type="number" step="0.01" class="mt-1 block min-h-12 w-full text-base" :value="old('area', $land->area)" required aria-describedby="area-error" />
                        <x-input-error id="area-error" class="mt-2" :messages="$errors->get('area')" />
                    </div>
                    <div>
                        <x-input-label for="area_unit" value="Satuan luas *" />
                        <x-text-input id="area_unit" name="area_unit" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('area_unit', $land->area_unit)" required maxlength="255" placeholder="Contoh: m² atau hektare" aria-describedby="area_unit-error" />
                        <x-input-error id="area_unit-error" class="mt-2" :messages="$errors->get('area_unit')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="soil_type" value="Jenis tanah (opsional)" />
                    <x-text-input id="soil_type" name="soil_type" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('soil_type', $land->soil_type)" maxlength="255" aria-describedby="soil_type-error" />
                    <x-input-error id="soil_type-error" class="mt-2" :messages="$errors->get('soil_type')" />
                </div>

                <div>
                    <x-input-label for="status" value="Status *" />
                    <select id="status" name="status" required aria-describedby="status-error" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                        <option value="aktif" @selected(old('status', $land->status) === 'aktif')>Aktif</option>
                        <option value="tidak_aktif" @selected(old('status', $land->status) === 'tidak_aktif')>Tidak aktif</option>
                        <option value="persiapan" @selected(old('status', $land->status) === 'persiapan')>Persiapan</option>
                    </select>
                    <x-input-error id="status-error" class="mt-2" :messages="$errors->get('status')" />
                </div>

                <div>
                    <x-input-label for="description" value="Deskripsi (opsional)" />
                    <textarea id="description" name="description" rows="4" aria-describedby="description-error" class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('description', $land->description) }}</textarea>
                    <x-input-error id="description-error" class="mt-2" :messages="$errors->get('description')" />
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('lands.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-5 text-base font-semibold text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-md bg-emerald-800 px-5 text-base font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                        {{ $land->exists ? 'Simpan Perubahan' : 'Simpan Lahan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
