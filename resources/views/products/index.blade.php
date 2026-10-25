<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-emerald-800">Persediaan kebun</p>
                    <h1 class="mt-1 text-3xl font-bold text-gray-950">Produk Pertanian</h1>
                    <p class="mt-2 text-gray-600">Kelola stok dan harga beli produk pertanian Anda.</p>
                </div>
                <a href="{{ route('products.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Tambah Produk
                </a>
            </header>

            @if (session('status'))
                <div role="status" class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div role="alert" class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-950">
                    {{ session('error') }}
                </div>
            @endif

            @if ($products->isEmpty())
                <section class="rounded-lg border border-gray-200 bg-white px-6 py-12 text-center shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900">Belum ada produk</h2>
                    <p class="mx-auto mt-2 max-w-lg text-gray-600">Tambahkan produk pertanian untuk memantau stok dan biaya pembelian.</p>
                    <a href="{{ route('products.create') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900">
                        Tambah Produk
                    </a>
                </section>
            @else
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <p>{{ $products->total() }} produk</p>
                </div>

                <div class="space-y-3">
                    @foreach ($products as $product)
                        <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-bold text-gray-950">{{ $product->name }}</h2>
                                        <span class="inline-flex min-h-7 items-center rounded-md bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-900">
                                            {{ ucfirst(str_replace('_', ' ', $product->category)) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        Stok: <span class="font-semibold text-gray-900">{{ number_format((float) $product->stock, 2, ',', '.') }} {{ $product->unit }}</span>
                                        · Batas minimum: {{ number_format((float) $product->minimum_stock, 2, ',', '.') }} {{ $product->unit }}
                                    </p>
                                    <p class="text-sm font-semibold text-gray-800">Harga beli: Rp {{ number_format((float) $product->purchase_price, 2, ',', '.') }}</p>
                                    @if ($product->description)
                                        <p class="whitespace-pre-line text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($product->description, 180) }}</p>
                                    @else
                                        <p class="text-sm text-gray-500">Belum ada deskripsi.</p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <a href="{{ route('products.edit', $product) }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Hapus produk ini? Produk yang digunakan pada catatan pengeluaran tidak dapat dihapus.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-md border border-rose-200 px-4 text-sm font-semibold text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-700">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($products->hasPages())
                    <nav aria-label="Navigasi halaman produk" class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-600">Halaman {{ $products->currentPage() }} dari {{ $products->lastPage() }}</p>
                        <div class="flex gap-2">
                            @if ($products->onFirstPage())
                                <span aria-disabled="true" class="inline-flex min-h-11 items-center rounded-md border border-gray-200 px-4 text-sm font-semibold text-gray-400">Sebelumnya</span>
                            @else
                                <a rel="prev" href="{{ $products->previousPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Sebelumnya</a>
                            @endif

                            @if ($products->hasMorePages())
                                <a rel="next" href="{{ $products->nextPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Berikutnya</a>
                            @else
                                <span aria-disabled="true" class="inline-flex min-h-11 items-center rounded-md border border-gray-200 px-4 text-sm font-semibold text-gray-400">Berikutnya</span>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
