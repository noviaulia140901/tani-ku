<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header>
                <p class="text-sm font-semibold uppercase text-emerald-800">Data kebun</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-950">{{ $harvest->exists ? 'Edit Panen' : 'Tambah Panen' }}</h1>
                <p class="mt-2 text-gray-600">Catat hasil panen tanaman Anda.</p>
            </header>

            @if ($crops->isEmpty())
                <section class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-950">
                    <h2 class="font-bold">Tanaman belum tersedia</h2>
                    <p class="mt-1 text-sm">Tambahkan tanaman di lahan Anda sebelum mencatat hasil panen.</p>
                    <a href="{{ route('crops.create') }}" class="mt-4 inline-flex min-h-10 items-center rounded-md border border-amber-300 bg-white px-4 text-sm font-semibold text-amber-950 hover:bg-amber-100">
                        Tambah Tanaman
                    </a>
                </section>
            @else
                <form method="POST" action="{{ $harvest->exists ? route('harvests.update', $harvest) : route('harvests.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                    @csrf
                    @if ($harvest->exists)
                        @method('PUT')
                    @endif

                    <div>
                        <x-input-label for="crop_id" value="Tanaman *" />
                        <select id="crop_id" name="crop_id" required aria-describedby="crop_id-error" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="">Pilih tanaman</option>
                            @foreach ($crops as $crop)
                                <option value="{{ $crop->id }}" @selected((string) old('crop_id', $harvest->crop_id) === (string) $crop->id)>
                                    {{ $crop->name }} - {{ $crop->land->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error id="crop_id-error" class="mt-2" :messages="$errors->get('crop_id')" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="harvest_date" value="Tanggal panen *" />
                            <x-text-input id="harvest_date" name="harvest_date" type="date" class="mt-1 block min-h-12 w-full text-base" :value="old('harvest_date', $harvest->harvest_date?->format('Y-m-d'))" required aria-describedby="harvest_date-error" />
                            <x-input-error id="harvest_date-error" class="mt-2" :messages="$errors->get('harvest_date')" />
                        </div>
                        <div>
                            <x-input-label for="quantity" value="Jumlah hasil *" />
                            <x-text-input id="quantity" name="quantity" type="number" step="0.01" min="0.01" max="99999999.99" class="mt-1 block min-h-12 w-full text-base" :value="old('quantity', $harvest->quantity)" required aria-describedby="quantity-error" />
                            <x-input-error id="quantity-error" class="mt-2" :messages="$errors->get('quantity')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="unit" value="Satuan *" />
                        <x-text-input id="unit" name="unit" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('unit', $harvest->unit)" required maxlength="255" placeholder="Contoh: kilogram atau ikat" aria-describedby="unit-error" />
                        <x-input-error id="unit-error" class="mt-2" :messages="$errors->get('unit')" />
                    </div>

                    <div>
                        <x-input-label for="quality" value="Kualitas (opsional)" />
                        <x-text-input id="quality" name="quality" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('quality', $harvest->quality)" maxlength="255" aria-describedby="quality-error" />
                        <x-input-error id="quality-error" class="mt-2" :messages="$errors->get('quality')" />
                    </div>

                    <div>
                        <x-input-label for="notes" value="Catatan (opsional)" />
                        <textarea id="notes" name="notes" rows="4" maxlength="65535" aria-describedby="notes-error" class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('notes', $harvest->notes) }}</textarea>
                        <x-input-error id="notes-error" class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ route('harvests.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-5 text-base font-semibold text-gray-700 hover:bg-gray-50">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-md bg-emerald-800 px-5 text-base font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                            {{ $harvest->exists ? 'Simpan Perubahan' : 'Simpan Panen' }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
