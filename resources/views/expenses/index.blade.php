<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-emerald-800">Keuangan kebun</p>
                    <h1 class="mt-1 text-3xl font-bold text-gray-950">Pengeluaran</h1>
                    <p class="mt-2 text-gray-600">Kelola catatan biaya usaha tani Anda.</p>
                </div>
                <a href="{{ route('expenses.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Tambah Pengeluaran
                </a>
            </header>

            @if (session('status'))
                <div role="status" class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($expenses->isEmpty())
                <section class="rounded-lg border border-gray-200 bg-white px-6 py-12 text-center shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900">Belum ada pengeluaran</h2>
                    <p class="mx-auto mt-2 max-w-lg text-gray-600">Tambahkan catatan pengeluaran untuk memantau biaya usaha tani Anda.</p>
                    <a href="{{ route('expenses.create') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900">
                        Tambah Pengeluaran
                    </a>
                </section>
            @else
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <p>{{ $expenses->total() }} pengeluaran</p>
                </div>

                <div class="space-y-3">
                    @foreach ($expenses as $expense)
                        <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-bold text-gray-950">{{ $expense->description }}</h2>
                                        <span class="inline-flex min-h-7 items-center rounded-md bg-amber-50 px-2.5 text-xs font-semibold text-amber-900">
                                            {{ str_replace('_', ' ', ucfirst($expense->category)) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">Tanggal: {{ $expense->expense_date->format('d/m/Y') }}</p>
                                    <p class="text-xl font-bold text-gray-900">Rp {{ number_format((float) $expense->amount, 2, ',', '.') }}</p>
                                    @if ($expense->crop)
                                        <p class="text-sm text-gray-600">
                                            Tanaman: {{ $expense->crop->name }} · Lahan: {{ $expense->crop->land->name }}
                                        </p>
                                    @endif
                                    @if ($expense->product)
                                        <p class="text-sm text-gray-600">Produk: {{ $expense->product->name }}</p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <a href="{{ route('expenses.edit', $expense) }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Hapus catatan pengeluaran ini?')">
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

                @if ($expenses->hasPages())
                    <nav aria-label="Navigasi halaman pengeluaran" class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-600">Halaman {{ $expenses->currentPage() }} dari {{ $expenses->lastPage() }}</p>
                        <div class="flex gap-2">
                            @if ($expenses->onFirstPage())
                                <span aria-disabled="true" class="inline-flex min-h-11 items-center rounded-md border border-gray-200 px-4 text-sm font-semibold text-gray-400">Sebelumnya</span>
                            @else
                                <a rel="prev" href="{{ $expenses->previousPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Sebelumnya</a>
                            @endif

                            @if ($expenses->hasMorePages())
                                <a rel="next" href="{{ $expenses->nextPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Berikutnya</a>
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
