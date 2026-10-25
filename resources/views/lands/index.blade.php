<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-emerald-800">Data kebun</p>
                    <h1 class="mt-1 text-3xl font-bold text-gray-950">Lahan</h1>
                    <p class="mt-2 text-gray-600">Kelola informasi lahan perkebunan Anda.</p>
                </div>
                <a href="{{ route('lands.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Tambah Lahan
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

            @if ($lands->isEmpty())
                <section class="rounded-lg border border-gray-200 bg-white px-6 py-12 text-center shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900">Belum ada lahan</h2>
                    <p class="mx-auto mt-2 max-w-lg text-gray-600">Tambahkan lahan untuk mulai mencatat tanaman dan aktivitas kebun.</p>
                    <a href="{{ route('lands.create') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900">
                        Tambah Lahan
                    </a>
                </section>
            @else
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <p>{{ $lands->total() }} lahan</p>
                </div>

                <div class="space-y-3">
                    @foreach ($lands as $land)
                        <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-bold text-gray-950">{{ $land->name }}</h2>
                                        <span @class([
                                            'inline-flex min-h-7 items-center rounded-md px-2.5 text-xs font-semibold',
                                            'bg-emerald-50 text-emerald-900' => $land->status === 'aktif',
                                            'bg-gray-100 text-gray-700' => $land->status === 'tidak_aktif',
                                            'bg-amber-50 text-amber-900' => $land->status === 'persiapan',
                                        ])>
                                            {{ match ($land->status) {
                                                'aktif' => 'Aktif',
                                                'tidak_aktif' => 'Tidak aktif',
                                                'persiapan' => 'Persiapan',
                                                default => 'Status tidak dikenal',
                                            } }}
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-700">{{ $land->location }}</p>
                                    <p class="text-sm text-gray-600">
                                        Luas: {{ number_format((float) $land->area, 2, ',', '.') }} {{ $land->area_unit }}
                                    </p>
                                    <p class="text-sm text-gray-600">Jenis tanah: {{ $land->soil_type ?: 'Belum diisi' }}</p>
                                    @if ($land->description)
                                        <p class="whitespace-pre-line text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($land->description, 180) }}</p>
                                    @else
                                        <p class="text-sm text-gray-500">Belum ada deskripsi.</p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <a href="{{ route('lands.edit', $land) }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('lands.destroy', $land) }}" onsubmit="return confirm('Hapus lahan ini? Lahan yang masih memiliki tanaman tidak dapat dihapus.')">
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

                @if ($lands->hasPages())
                    <nav aria-label="Navigasi halaman lahan" class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-600">Halaman {{ $lands->currentPage() }} dari {{ $lands->lastPage() }}</p>
                        <div class="flex gap-2">
                            @if ($lands->onFirstPage())
                                <span aria-disabled="true" class="inline-flex min-h-11 items-center rounded-md border border-gray-200 px-4 text-sm font-semibold text-gray-400">Sebelumnya</span>
                            @else
                                <a rel="prev" href="{{ $lands->previousPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Sebelumnya</a>
                            @endif

                            @if ($lands->hasMorePages())
                                <a rel="next" href="{{ $lands->nextPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Berikutnya</a>
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
