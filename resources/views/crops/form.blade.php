<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header>
                <p class="text-sm font-semibold uppercase text-emerald-800">Data kebun</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-950">{{ $crop->exists ? 'Edit Tanaman' : 'Tambah Tanaman' }}</h1>
                <p class="mt-2 text-gray-600">Isi informasi tanaman yang Anda budidayakan.</p>
            </header>

            @if ($lands->isEmpty())
                <section class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-950">
                    <h2 class="font-bold">Lahan belum tersedia</h2>
                    <p class="mt-1 text-sm">Tambahkan lahan terlebih dahulu sebelum mencatat tanaman.</p>
                    <a href="{{ route('lands.create') }}" class="mt-4 inline-flex min-h-10 items-center rounded-md border border-amber-300 bg-white px-4 text-sm font-semibold text-amber-950 hover:bg-amber-100">
                        Tambah Lahan
                    </a>
                </section>
            @else
                <form method="POST" action="{{ $crop->exists ? route('crops.update', $crop) : route('crops.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                    @csrf
                    @if ($crop->exists)
                        @method('PUT')
                    @endif

                    <div>
                        <x-input-label for="land_id" value="Lahan *" />
                        <select id="land_id" name="land_id" required aria-describedby="land_id-error" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="">Pilih lahan</option>
                            @foreach ($lands as $land)
                                <option value="{{ $land->id }}" @selected((string) old('land_id', $crop->land_id) === (string) $land->id)>
                                    {{ $land->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error id="land_id-error" class="mt-2" :messages="$errors->get('land_id')" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="name" value="Nama tanaman *" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('name', $crop->name)" required maxlength="255" aria-describedby="name-error" />
                            <x-input-error id="name-error" class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <x-input-label for="variety" value="Varietas (opsional)" />
                            <x-text-input id="variety" name="variety" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('variety', $crop->variety)" maxlength="255" aria-describedby="variety-error" />
                            <x-input-error id="variety-error" class="mt-2" :messages="$errors->get('variety')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="planting_date" value="Tanggal tanam *" />
                            <x-text-input id="planting_date" name="planting_date" type="date" class="mt-1 block min-h-12 w-full text-base" :value="old('planting_date', $crop->planting_date?->format('Y-m-d'))" required aria-describedby="planting_date-error" />
                            <x-input-error id="planting_date-error" class="mt-2" :messages="$errors->get('planting_date')" />
                        </div>
                        <div>
                            <x-input-label for="estimated_harvest_date" value="Perkiraan tanggal panen" />
                            <x-text-input id="estimated_harvest_date" name="estimated_harvest_date" type="date" class="mt-1 block min-h-12 w-full text-base" :value="old('estimated_harvest_date', $crop->estimated_harvest_date?->format('Y-m-d'))" aria-describedby="estimated_harvest_date-error" />
                            <x-input-error id="estimated_harvest_date-error" class="mt-2" :messages="$errors->get('estimated_harvest_date')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="status" value="Status *" />
                        <select id="status" name="status" required aria-describedby="status-error" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="">Pilih status</option>
                            @foreach ([
                                'persiapan' => 'Persiapan',
                                'ditanam' => 'Ditanam',
                                'perawatan' => 'Perawatan',
                                'siap_panen' => 'Siap panen',
                                'selesai' => 'Selesai',
                            ] as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" @selected(old('status', $crop->status) === $statusValue)>
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error id="status-error" class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="seed_quantity" value="Jumlah benih (opsional)" />
                            <x-text-input id="seed_quantity" name="seed_quantity" type="number" step="0.01" min="0" class="mt-1 block min-h-12 w-full text-base" :value="old('seed_quantity', $crop->seed_quantity)" aria-describedby="seed_quantity-error" />
                            <x-input-error id="seed_quantity-error" class="mt-2" :messages="$errors->get('seed_quantity')" />
                        </div>
                        <div>
                            <x-input-label for="seed_unit" value="Satuan benih (opsional)" />
                            <x-text-input id="seed_unit" name="seed_unit" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('seed_unit', $crop->seed_unit)" maxlength="255" placeholder="Contoh: gram atau kilogram" aria-describedby="seed_unit-error" />
                            <x-input-error id="seed_unit-error" class="mt-2" :messages="$errors->get('seed_unit')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="notes" value="Catatan (opsional)" />
                        <textarea id="notes" name="notes" rows="4" aria-describedby="notes-error" class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('notes', $crop->notes) }}</textarea>
                        <x-input-error id="notes-error" class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ route('crops.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-5 text-base font-semibold text-gray-700 hover:bg-gray-50">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-md bg-emerald-800 px-5 text-base font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                            {{ $crop->exists ? 'Simpan Perubahan' : 'Simpan Tanaman' }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
