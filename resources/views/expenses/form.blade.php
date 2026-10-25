<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header>
                <p class="text-sm font-semibold uppercase text-emerald-800">Keuangan kebun</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-950">{{ $expense->exists ? 'Edit Pengeluaran' : 'Tambah Pengeluaran' }}</h1>
                <p class="mt-2 text-gray-600">Catat biaya usaha tani Anda.</p>
            </header>

            <form method="POST" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                @csrf
                @if ($expense->exists)
                    @method('PUT')
                @endif

                <div>
                    <x-input-label for="crop_id" value="Tanaman terkait (opsional)" />
                    <select id="crop_id" name="crop_id" aria-describedby="crop_id-error" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                        <option value="">Tidak terkait tanaman</option>
                        @foreach ($crops as $crop)
                            <option value="{{ $crop->id }}" @selected((string) old('crop_id', $expense->crop_id) === (string) $crop->id)>
                                {{ $crop->name }} - {{ $crop->land->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error id="crop_id-error" class="mt-2" :messages="$errors->get('crop_id')" />
                </div>

                <div>
                    <x-input-label for="product_id" value="Produk terkait (opsional)" />
                    <select id="product_id" name="product_id" aria-describedby="product_id-error" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                        <option value="">Tidak terkait produk</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id', $expense->product_id) === (string) $product->id)>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error id="product_id-error" class="mt-2" :messages="$errors->get('product_id')" />
                </div>

                <div>
                    <x-input-label for="category" value="Kategori pengeluaran *" />
                    <x-text-input id="category" name="category" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('category', $expense->category)" required maxlength="255" placeholder="Contoh: bibit, pupuk, atau transportasi" aria-describedby="category-error" />
                    <x-input-error id="category-error" class="mt-2" :messages="$errors->get('category')" />
                </div>

                <div>
                    <x-input-label for="description" value="Nama atau deskripsi pengeluaran *" />
                    <textarea id="description" name="description" rows="4" required maxlength="65535" aria-describedby="description-error" class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('description', $expense->description) }}</textarea>
                    <x-input-error id="description-error" class="mt-2" :messages="$errors->get('description')" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="amount" value="Jumlah biaya (Rp) *" />
                        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" max="9999999999999.99" class="mt-1 block min-h-12 w-full text-base" :value="old('amount', $expense->amount)" required aria-describedby="amount-error" />
                        <x-input-error id="amount-error" class="mt-2" :messages="$errors->get('amount')" />
                    </div>
                    <div>
                        <x-input-label for="expense_date" value="Tanggal pengeluaran *" />
                        <x-text-input id="expense_date" name="expense_date" type="date" class="mt-1 block min-h-12 w-full text-base" :value="old('expense_date', $expense->expense_date?->format('Y-m-d'))" required aria-describedby="expense_date-error" />
                        <x-input-error id="expense_date-error" class="mt-2" :messages="$errors->get('expense_date')" />
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('expenses.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-5 text-base font-semibold text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-md bg-emerald-800 px-5 text-base font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                        {{ $expense->exists ? 'Simpan Perubahan' : 'Simpan Pengeluaran' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
