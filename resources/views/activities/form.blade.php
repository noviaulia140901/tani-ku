<div>
    <!-- Nothing in life is to be feared, it is only to be understood. Now is the time to understand more, so that we may fear less. - Maria Skłodowska-Curie -->
<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-8 sm:py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header>
                <p class="text-sm font-semibold uppercase text-emerald-800">Kegiatan kebun</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-950">{{ $activity->exists ? 'Edit Aktivitas' : 'Tambah Aktivitas' }}</h1>
                <p class="mt-2 text-gray-600">Isi jadwal perawatan tanaman dengan lengkap.</p>
            </header>

            @if ($crops->isEmpty())
                <section class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-950">
                    <h2 class="font-bold">Tanaman belum tersedia</h2>
                    <p class="mt-1 text-sm">Tambahkan tanaman ke lahan Anda sebelum membuat jadwal aktivitas.</p>
                    <a href="{{ route('activities.index') }}" class="mt-4 inline-flex min-h-10 items-center rounded-md border border-amber-300 bg-white px-4 text-sm font-semibold text-amber-950 hover:bg-amber-100">
                        Kembali ke Aktivitas
                    </a>
                </section>
            @else
                <form method="POST" action="{{ $activity->exists ? route('activities.update', $activity) : route('activities.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                    @csrf
                    @if ($activity->exists)
                        @method('PUT')
                    @endif

                    <div>
                        <x-input-label for="crop_id" value="Tanaman dan lahan" />
                        <select id="crop_id" name="crop_id" required aria-describedby="crop_id-error" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="">Pilih tanaman</option>
                            @foreach ($crops as $crop)
                                <option value="{{ $crop->id }}" @selected((string) old('crop_id', $activity->crop_id) === (string) $crop->id)>
                                    {{ $crop->name }} - {{ $crop->land->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error id="crop_id-error" class="mt-2" :messages="$errors->get('crop_id')" />
                    </div>

                    <div>
                        <x-input-label for="activity_type" value="Jenis aktivitas/perawatan" />
                        <x-text-input id="activity_type" name="activity_type" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('activity_type', $activity->activity_type)" required maxlength="255" placeholder="Contoh: Pemupukan" aria-describedby="activity_type-error" />
                        <x-input-error id="activity_type-error" class="mt-2" :messages="$errors->get('activity_type')" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="scheduled_date" value="Tanggal" />
                            <x-text-input id="scheduled_date" name="scheduled_date" type="date" class="mt-1 block min-h-12 w-full text-base" :value="old('scheduled_date', $activity->scheduled_date?->format('Y-m-d'))" required aria-describedby="scheduled_date-error" />
                            <x-input-error id="scheduled_date-error" class="mt-2" :messages="$errors->get('scheduled_date')" />
                        </div>
                        <div>
                            <x-input-label for="scheduled_time" value="Waktu (opsional)" />
                            <x-text-input id="scheduled_time" name="scheduled_time" type="time" class="mt-1 block min-h-12 w-full text-base" :value="old('scheduled_time', $activity->scheduled_time?->format('H:i'))" aria-describedby="scheduled_time-error" />
                            <x-input-error id="scheduled_time-error" class="mt-2" :messages="$errors->get('scheduled_time')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="product_name" value="Produk yang digunakan (opsional)" />
                            <x-text-input id="product_name" name="product_name" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('product_name', $activity->product_name)" maxlength="255" aria-describedby="product_name-error" />
                            <x-input-error id="product_name-error" class="mt-2" :messages="$errors->get('product_name')" />
                        </div>
                        <div>
                            <x-input-label for="dosage" value="Dosis (opsional)" />
                            <x-text-input id="dosage" name="dosage" type="text" class="mt-1 block min-h-12 w-full text-base" :value="old('dosage', $activity->dosage)" maxlength="255" aria-describedby="dosage-error" />
                            <x-input-error id="dosage-error" class="mt-2" :messages="$errors->get('dosage')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="status" value="Status aktivitas" />
                        <select id="status" name="status" required aria-describedby="status-error" class="mt-1 block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="belum_dilakukan" @selected(old('status', $activity->status) === 'belum_dilakukan')>Belum dilakukan</option>
                            <option value="selesai" @selected(old('status', $activity->status) === 'selesai')>Selesai</option>
                            <option value="dilewati" @selected(old('status', $activity->status) === 'dilewati')>Dilewati</option>
                        </select>
                        <x-input-error id="status-error" class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div>
                        <x-input-label for="notes" value="Catatan (opsional)" />
                        <textarea id="notes" name="notes" rows="4" aria-describedby="notes-error" class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('notes', $activity->notes) }}</textarea>
                        <x-input-error id="notes-error" class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ route('activities.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-5 text-base font-semibold text-gray-700 hover:bg-gray-50">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-md bg-emerald-800 px-5 text-base font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                            {{ $activity->exists ? 'Simpan Perubahan' : 'Simpan Aktivitas' }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
