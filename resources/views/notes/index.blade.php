<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-emerald-800">Dokumentasi kebun</p>
                    <h1 class="mt-1 text-3xl font-bold text-gray-950">Catatan Tanaman</h1>
                    <p class="mt-2 text-gray-600">Simpan pengamatan dan informasi penting untuk setiap tanaman.</p>
                </div>
                <a href="{{ route('notes.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Tambah Catatan
                </a>
            </header>

            @if (session('status'))
                <div role="status" class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($notes->isEmpty())
                <section class="rounded-lg border border-gray-200 bg-white px-6 py-12 text-center shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900">Belum ada catatan</h2>
                    <p class="mx-auto mt-2 max-w-lg text-gray-600">Tambahkan catatan untuk menyimpan pengamatan perkembangan tanaman Anda.</p>
                    <a href="{{ route('notes.create') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900">
                        Tambah Catatan
                    </a>
                </section>
            @else
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <p>{{ $notes->total() }} catatan</p>
                </div>

                <div class="space-y-3">
                    @foreach ($notes as $note)
                        <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <h2 class="text-lg font-bold text-gray-950">{{ $note->title }}</h2>
                                        <time class="text-sm text-gray-500" datetime="{{ $note->note_date->format('Y-m-d') }}">
                                            {{ $note->note_date->format('d/m/Y') }}
                                        </time>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        Tanaman: <span class="font-semibold text-gray-900">{{ $note->crop->name }}</span>
                                        · Lahan: <span class="font-semibold text-gray-900">{{ $note->crop->land->name }}</span>
                                    </p>
                                    <p class="whitespace-pre-line text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($note->content, 500) }}</p>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <a href="{{ route('notes.edit', $note) }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('notes.destroy', $note) }}" onsubmit="return confirm('Hapus catatan ini?')">
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

                @if ($notes->hasPages())
                    <nav aria-label="Navigasi halaman catatan" class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-600">Halaman {{ $notes->currentPage() }} dari {{ $notes->lastPage() }}</p>
                        <div class="flex gap-2">
                            @if ($notes->onFirstPage())
                                <span aria-disabled="true" class="inline-flex min-h-11 items-center rounded-md border border-gray-200 px-4 text-sm font-semibold text-gray-400">Sebelumnya</span>
                            @else
                                <a rel="prev" href="{{ $notes->previousPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Sebelumnya</a>
                            @endif

                            @if ($notes->hasMorePages())
                                <a rel="next" href="{{ $notes->nextPageUrl() }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-white">Berikutnya</a>
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
