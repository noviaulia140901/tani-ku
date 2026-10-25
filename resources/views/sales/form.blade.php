<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header>
                <p class="text-sm font-semibold uppercase text-emerald-800">Keuangan kebun</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-950">{{ $sale->exists ? 'Edit Penjualan' : 'Tambah Penjualan' }}</h1>
                <p class="mt-2 text-gray-600">Catat transaksi penjualan hasil panen Anda.</p>
            </header>

            @if ($harvests->isEmpty())
                <section class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-950">
                    <h2 class="font-bold">Panen belum tersedia</h2>
                    <p class="mt-1 text-sm">Tambahkan data panen milik Anda sebelum mencatat penjualan.</p>
                    <a href="{{ route('harvests.create') }}" class="mt-4 inline-flex min-h-10 items-center rounded-md border border-amber-300 bg-white px-4 text-sm font-semibold text-amber-950 hover:bg-amber-100">
                        Tambah Panen
                    </a>
                </section>
            @else
                <form method="POST" action="{{ $sale->exists ? route('sales.update', $sale) : route('sales.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-7" x-data="{ unit: @js(old('unit', $sale->unit ?? $harvests->first()?->unit ?? '')) }">
                    @csrf
                    @if ($sale->exists)
                        @method('PUT')
                    @endif

                    <div>
                        <x-input-label for="harvest_id" value="Data panen *" />
                        <select id="harvest_id" name="harvest_id" required aria-describedby="harvest_id-error" x-on:change="unit = $event.target.selectedOptions[0].dataset.unit || ''" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="" data-unit="">Pilih data panen</option>
                            @foreach ($harvests as $harvest)
                                <option value="{{ $harvest->id }}" data-unit="{{ $harvest->unit }}" @selected((string) old('harvest_id', $sale->harvest_id) === (string) $harvest->id)>
                                    {{ $harvest->crop->name }} - {{ $harvest->crop->land->name }} · {{ $harvest->harvest_date->format('d/m/Y') }} · {{ number_format((float) $harvest->quantity, 2, ',', '.') }} {{ $harvest->unit }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error id="harvest_id-error" class="mt-2" :messages="$errors->get('harvest_id')" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="buyer_name" value="Nama pembeli *" />
                            <x-text-input id="buyer_name" name="buyer_name" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('buyer_name', $sale->buyer_name)" required maxlength="255" aria-describedby="buyer_name-error" />
                            <x-input-error id="buyer_name-error" class="mt-2" :messages="$errors->get('buyer_name')" />
                        </div>
                        <div>
                            <x-input-label for="sale_date" value="Tanggal penjualan *" />
                            <x-text-input id="sale_date" name="sale_date" type="date" class="mt-1 block min-h-12 w-full text-base" :value="old('sale_date', $sale->sale_date?->format('Y-m-d'))" required aria-describedby="sale_date-error" />
                            <x-input-error id="sale_date-error" class="mt-2" :messages="$errors->get('sale_date')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="quantity" value="Jumlah terjual *" />
                            <x-text-input id="quantity" name="quantity" type="number" step="0.01" min="0.01" max="99999999.99" class="mt-1 block min-h-12 w-full text-base" :value="old('quantity', $sale->quantity)" required aria-describedby="quantity-error" />
                            <x-input-error id="quantity-error" class="mt-2" :messages="$errors->get('quantity')" />
                        </div>
                        <div>
                            <x-input-label for="unit" value="Satuan *" />
                            <x-text-input id="unit" name="unit" type="text" class="mt-1 block min-h-12 w-full text-base" x-model="unit" required maxlength="255" aria-describedby="unit-error" />
                            <x-input-error id="unit-error" class="mt-2" :messages="$errors->get('unit')" />
                            <p class="mt-1 text-sm text-gray-500">Satuan harus sama dengan satuan pada data panen.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="price_per_unit" value="Harga per satuan (Rp) *" />
                            <x-text-input id="price_per_unit" name="price_per_unit" type="number" step="0.01" min="0.01" max="9999999999999.99" class="mt-1 block min-h-12 w-full text-base" :value="old('price_per_unit', $sale->price_per_unit)" required aria-describedby="price_per_unit-error" />
                            <x-input-error id="price_per_unit-error" class="mt-2" :messages="$errors->get('price_per_unit')" />
                        </div>
                        <div>
                            <x-input-label for="payment_status" value="Status pembayaran *" />
                            <select id="payment_status" name="payment_status" required aria-describedby="payment_status-error" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                                <option value="belum_lunas" @selected(old('payment_status', $sale->payment_status) === 'belum_lunas')>Belum lunas</option>
                                <option value="lunas" @selected(old('payment_status', $sale->payment_status) === 'lunas')>Lunas</option>
                            </select>
                            <x-input-error id="payment_status-error" class="mt-2" :messages="$errors->get('payment_status')" />
                        </div>
                    </div>

                    <p class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-950">Total penjualan dihitung otomatis dari jumlah terjual dan harga per satuan.</p>

                    <div>
                        <x-input-label for="notes" value="Catatan (opsional)" />
                        <textarea id="notes" name="notes" rows="4" maxlength="65535" aria-describedby="notes-error" class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('notes', $sale->notes) }}</textarea>
                        <x-input-error id="notes-error" class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ route('sales.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-5 text-base font-semibold text-gray-700 hover:bg-gray-50">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-md bg-emerald-800 px-5 text-base font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                            {{ $sale->exists ? 'Simpan Perubahan' : 'Simpan Penjualan' }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
