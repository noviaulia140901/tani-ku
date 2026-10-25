<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-emerald-800">Kegiatan kebun</p>
                    <h1 class="mt-1 text-3xl font-bold text-gray-950">Aktivitas dan perawatan</h1>
                    <p class="mt-2 text-gray-600">Jadwal perawatan tanaman Anda, diurutkan dari tanggal terdekat.</p>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('activities.calendar') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-800 px-4 text-sm font-semibold text-emerald-900 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                        Kalender
                    </a>
                    <a href="{{ route('activities.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                        Tambah Aktivitas
                    </a>
                </div>
            </header>

            @if (session('status'))
                <div role="status" class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($activities->isEmpty())
                <section class="rounded-lg border border-gray-200 bg-white px-6 py-12 text-center shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900">Belum ada aktivitas</h2>
                    <p class="mx-auto mt-2 max-w-lg text-gray-600">Buat jadwal perawatan untuk tanaman di lahan Anda.</p>
                    <a href="{{ route('activities.create') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900">
                        Tambah Aktivitas
                    </a>
                </section>
            @else
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <p>{{ $activities->total() }} jadwal aktivitas</p>
                </div>

                <div class="space-y-3">
                    @foreach ($activities as $activity)
                        <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-bold text-gray-950">{{ str_replace('_', ' ', ucfirst($activity->activity_type)) }}</h2>
                                        <span @class([
                                            'inline-flex min-h-7 items-center rounded-md px-2.5 text-xs font-semibold',
                                            'bg-amber-50 text-amber-900' => $activity->status === 'belum_dilakukan',
                                            'bg-emerald-50 text-emerald-900' => $activity->status === 'selesai',
                                            'bg-gray-100 text-gray-700' => $activity->status === 'dilewati',
                                        ])>
                                            {{ match ($activity->status) {
                                                'belum_dilakukan' => 'Belum dilakukan',
                                                'selesai' => 'Selesai',
                                                'dilewati' => 'Dilewati',
                                                default => 'Status tidak dikenal',
                                            } }}
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-700">{{ $activity->crop->name }} <span class="text-gray-400">di</span> {{ $activity->crop->land->name }}</p>
                                    <p class="text-sm text-gray-600">
                                        {{ $activity->scheduled_date->format('d/m/Y') }}
                                        @if ($activity->scheduled_time)
                                            pukul {{ $activity->scheduled_time->format('H:i') }}
                                        @endif
                                    </p>
                                    @if ($activity->product_name || $activity->dosage)
                                        <p class="text-sm text-gray-600">
                                            @if ($activity->product_name)
                                                {{ $activity->product_name }}
                                            @endif
                                            @if ($activity->dosage)
                                                @if ($activity->product_name) - @endif
                                                Dosis {{ $activity->dosage }}
                                            @endif
                                        </p>
                                    @endif
                                    @if ($activity->notes)
                                        <p class="whitespace-pre-line text-sm text-gray-600">{{ $activity->notes }}</p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <a href="{{ route('activities.edit', $activity) }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                                        Ubah
                                    </a>
                                    <form method="POST" action="{{ route('activities.destroy', $activity) }}" onsubmit="return confirm('Hapus jadwal aktivitas ini?')">
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

                @if ($activities->hasPages())
                    <nav aria-label="Navigasi halaman aktivitas" class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-600">Halaman {{ $activities->currentPage() }} dari {{ $activities->lastPage() }}</p>
                        <div class="flex gap-2">
                            @if ($activities->onFirstPage())
                                <span aria-disabled="true" class="inline-flex min-h-11 items-center rounded-md border border-gray-200 px-4 text-sm font-semibold text-gray-400">Sebelumnya</span>
                            @else
                                <a rel="prev" href="{{ $activities->previousPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Sebelumnya</a>
                            @endif

                            @if ($activities->hasMorePages())
                                <a rel="next" href="{{ $activities->nextPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Berikutnya</a>
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
