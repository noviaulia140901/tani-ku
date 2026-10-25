<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header>
                <p class="text-sm font-semibold uppercase text-emerald-800">Persediaan kebun</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-950">{{ $product->exists ? 'Edit Produk' : 'Tambah Produk' }}</h1>
                <p class="mt-2 text-gray-600">Catat informasi produk pertanian, stok, dan harga belinya.</p>
            </header>

            <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                @csrf
                @if ($product->exists)
                    @method('PUT')
                @endif

                <div>
                    <x-input-label for="name" value="Nama produk *" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('name', $product->name)" required maxlength="255" aria-describedby="name-error" />
                    <x-input-error id="name-error" class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="category" value="Kategori *" />
                        <select id="category" name="category" required aria-describedby="category-error" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="">Pilih kategori</option>
                            <option value="pupuk" @selected(old('category', $product->category) === 'pupuk')>Pupuk</option>
                            <option value="pestisida" @selected(old('category', $product->category) === 'pestisida')>Pestisida</option>
                            <option value="herbisida" @selected(old('category', $product->category) === 'herbisida')>Herbisida</option>
                            <option value="fungisida" @selected(old('category', $product->category) === 'fungisida')>Fungisida</option>
                            <option value="obat_lainnya" @selected(old('category', $product->category) === 'obat_lainnya')>Obat lainnya</option>
                        </select>
                        <x-input-error id="category-error" class="mt-2" :messages="$errors->get('category')" />
                    </div>
                    <div>
                        <x-input-label for="unit" value="Satuan *" />
                        <x-text-input id="unit" name="unit" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('unit', $product->unit)" required maxlength="255" aria-describedby="unit-error" />
                        <x-input-error id="unit-error" class="mt-2" :messages="$errors->get('unit')" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="stock" value="Stok saat ini *" />
                        <x-text-input id="stock" name="stock" type="number" step="0.01" min="0" max="99999999.99" class="mt-1 block min-h-12 w-full text-base" :value="old('stock', $product->stock)" required aria-describedby="stock-error" />
                        <x-input-error id="stock-error" class="mt-2" :messages="$errors->get('stock')" />
                    </div>
                    <div>
                        <x-input-label for="minimum_stock" value="Batas stok minimum *" />
                        <x-text-input id="minimum_stock" name="minimum_stock" type="number" step="0.01" min="0" max="99999999.99" class="mt-1 block min-h-12 w-full text-base" :value="old('minimum_stock', $product->minimum_stock)" required aria-describedby="minimum_stock-error" />
                        <x-input-error id="minimum_stock-error" class="mt-2" :messages="$errors->get('minimum_stock')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="purchase_price" value="Harga beli (Rp) *" />
                    <x-text-input id="purchase_price" name="purchase_price" type="number" step="0.01" min="0" max="9999999999999.99" class="mt-1 block min-h-12 w-full text-base" :value="old('purchase_price', $product->purchase_price)" required aria-describedby="purchase_price-error" />
                    <x-input-error id="purchase_price-error" class="mt-2" :messages="$errors->get('purchase_price')" />
                </div>

                <div>
                    <x-input-label for="description" value="Deskripsi (opsional)" />
                    <textarea id="description" name="description" rows="4" maxlength="65535" aria-describedby="description-error" class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('description', $product->description) }}</textarea>
                    <x-input-error id="description-error" class="mt-2" :messages="$errors->get('description')" />
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('products.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-5 text-base font-semibold text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-md bg-emerald-800 px-5 text-base font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                        {{ $product->exists ? 'Simpan Perubahan' : 'Simpan Produk' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
