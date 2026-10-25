<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-emerald-800">Data kebun</p>
                    <h1 class="mt-1 text-3xl font-bold text-gray-950">Tanaman</h1>
                    <p class="mt-2 text-gray-600">Kelola tanaman yang ditanam di lahan Anda.</p>
                </div>
                <a href="{{ route('crops.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Tambah Tanaman
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

            @if ($crops->isEmpty())
                <section class="rounded-lg border border-gray-200 bg-white px-6 py-12 text-center shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900">Belum ada tanaman</h2>
                    <p class="mx-auto mt-2 max-w-lg text-gray-600">Tambahkan tanaman ke salah satu lahan Anda.</p>
                    <a href="{{ route('crops.create') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900">
                        Tambah Tanaman
                    </a>
                </section>
            @else
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <p>{{ $crops->total() }} tanaman</p>
                </div>

                <div class="space-y-3">
                    @foreach ($crops as $crop)
                        <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-bold text-gray-950">{{ $crop->name }}</h2>
                                        <span @class([
                                            'inline-flex min-h-7 items-center rounded-md px-2.5 text-xs font-semibold',
                                            'bg-amber-50 text-amber-900' => $crop->status === 'persiapan',
                                            'bg-emerald-50 text-emerald-900' => in_array($crop->status, ['ditanam', 'perawatan'], true),
                                            'bg-sky-50 text-sky-900' => $crop->status === 'siap_panen',
                                            'bg-gray-100 text-gray-700' => $crop->status === 'selesai',
                                        ])>
                                            {{ match ($crop->status) {
                                                'persiapan' => 'Persiapan',
                                                'ditanam' => 'Ditanam',
                                                'perawatan' => 'Perawatan',
                                                'siap_panen' => 'Siap panen',
                                                'selesai' => 'Selesai',
                                                default => 'Status tidak dikenal',
                                            } }}
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-700">Lahan: {{ $crop->land->name }}</p>
                                    <p class="text-sm text-gray-600">Varietas: {{ $crop->variety ?: 'Belum diisi' }}</p>
                                    <div class="grid grid-cols-1 gap-1 text-sm text-gray-600 sm:grid-cols-2">
                                        <p>Tanggal tanam: {{ $crop->planting_date?->format('d/m/Y') }}</p>
                                        <p>Perkiraan panen: {{ $crop->estimated_harvest_date?->format('d/m/Y') ?? 'Belum ditentukan' }}</p>
                                        <p>Jumlah benih: {{ $crop->seed_quantity !== null ? number_format((float) $crop->seed_quantity, 2, ',', '.') : 'Belum diisi' }}</p>
                                        <p>Satuan benih: {{ $crop->seed_unit ?: 'Belum diisi' }}</p>
                                    </div>
                                    @if ($crop->notes)
                                        <p class="whitespace-pre-line text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($crop->notes, 180) }}</p>
                                    @else
                                        <p class="text-sm text-gray-500">Belum ada catatan.</p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <a href="{{ route('crops.edit', $crop) }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('crops.destroy', $crop) }}" onsubmit="return confirm('Hapus tanaman ini? Tanaman yang memiliki aktivitas, pengeluaran, panen, atau catatan terkait tidak dapat dihapus.')">
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

                @if ($crops->hasPages())
                    <nav aria-label="Navigasi halaman tanaman" class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-600">Halaman {{ $crops->currentPage() }} dari {{ $crops->lastPage() }}</p>
                        <div class="flex gap-2">
                            @if ($crops->onFirstPage())
                                <span aria-disabled="true" class="inline-flex min-h-11 items-center rounded-md border border-gray-200 px-4 text-sm font-semibold text-gray-400">Sebelumnya</span>
                            @else
                                <a rel="prev" href="{{ $crops->previousPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Sebelumnya</a>
                            @endif

                            @if ($crops->hasMorePages())
                                <a rel="next" href="{{ $crops->nextPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Berikutnya</a>
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
