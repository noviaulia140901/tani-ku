<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header>
                <p class="text-sm font-semibold uppercase text-emerald-800">Dokumentasi kebun</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-950">{{ $note->exists ? 'Edit Catatan' : 'Tambah Catatan' }}</h1>
                <p class="mt-2 text-gray-600">Catat pengamatan tanaman dan tanggal pencatatannya.</p>
            </header>

            @if ($crops->isEmpty())
                <section class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-950">
                    <h2 class="font-bold">Tanaman belum tersedia</h2>
                    <p class="mt-1 text-sm">Tambahkan tanaman ke lahan Anda sebelum membuat catatan.</p>
                    <a href="{{ route('notes.index') }}" class="mt-4 inline-flex min-h-10 items-center rounded-md border border-amber-300 bg-white px-4 text-sm font-semibold text-amber-950 hover:bg-amber-100">
                        Kembali ke Catatan
                    </a>
                </section>
            @else
                <form method="POST" action="{{ $note->exists ? route('notes.update', $note) : route('notes.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                    @csrf
                    @if ($note->exists)
                        @method('PUT')
                    @endif

                    <div>
                        <x-input-label for="crop_id" value="Tanaman dan lahan *" />
                        <select id="crop_id" name="crop_id" required aria-describedby="crop_id-error" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="">Pilih tanaman</option>
                            @foreach ($crops as $crop)
                                <option value="{{ $crop->id }}" @selected((string) old('crop_id', $note->crop_id) === (string) $crop->id)>
                                    {{ $crop->name }} - {{ $crop->land->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error id="crop_id-error" class="mt-2" :messages="$errors->get('crop_id')" />
                    </div>

                    <div>
                        <x-input-label for="title" value="Judul catatan *" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('title', $note->title)" required maxlength="255" aria-describedby="title-error" />
                        <x-input-error id="title-error" class="mt-2" :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <x-input-label for="note_date" value="Tanggal catatan *" />
                        <x-text-input id="note_date" name="note_date" type="date" class="mt-1 block min-h-12 w-full text-base" :value="old('note_date', $note->note_date?->format('Y-m-d'))" required aria-describedby="note_date-error" />
                        <x-input-error id="note_date-error" class="mt-2" :messages="$errors->get('note_date')" />
                    </div>

                    <div>
                        <x-input-label for="content" value="Isi catatan *" />
                        <textarea id="content" name="content" rows="6" maxlength="65535" required aria-describedby="content-error" class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('content', $note->content) }}</textarea>
                        <x-input-error id="content-error" class="mt-2" :messages="$errors->get('content')" />
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ route('notes.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-5 text-base font-semibold text-gray-700 hover:bg-gray-50">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-md bg-emerald-800 px-5 text-base font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                            {{ $note->exists ? 'Simpan Perubahan' : 'Simpan Catatan' }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
