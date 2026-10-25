<x-app-layout>
    @php
        $monthDate = \Carbon\CarbonImmutable::createFromFormat('!Y-m', $month, 'Asia/Jakarta');
        $calendarStart = $monthDate->startOfMonth()->startOfWeek(\Carbon\Carbon::MONDAY);
        $calendarEnd = $monthDate->endOfMonth()->endOfWeek(\Carbon\Carbon::MONDAY);
        $calendarDays = [];

        for ($date = $calendarStart; $date <= $calendarEnd; $date = $date->addDay()) {
            $calendarDays[] = $date;
        }

        $activityCountsByDate = $calendar_activities->countBy(
            fn ($activity) => $activity->scheduled_date->toDateString(),
        );
        $queryFilters = request()->query();
        unset($queryFilters['month'], $queryFilters['date'], $queryFilters['page']);
        $previousMonthUrl = route('activities.calendar', [
            ...$queryFilters,
            'month' => $monthDate->subMonth()->format('Y-m'),
        ]);
        $nextMonthUrl = route('activities.calendar', [
            ...$queryFilters,
            'month' => $monthDate->addMonth()->format('Y-m'),
        ]);
        $todayUrl = route('activities.calendar', [
            ...$queryFilters,
            'month' => substr($today, 0, 7),
            'date' => $today,
        ]);
        $weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    @endphp

    <div class="min-h-[calc(100vh-4rem)] bg-stone-50 py-6 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-3 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-emerald-800">Kegiatan kebun</p>
                    <h1 class="mt-1 text-3xl font-bold text-gray-950">Kalender Kegiatan</h1>
                    <p class="mt-2 text-gray-600">Atur jadwal perawatan dan lihat pengingat kegiatan Anda.</p>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex">
                    <a href="{{ route('activities.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-800 px-4 text-sm font-semibold text-emerald-900 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                        Daftar Aktivitas
                    </a>
                    <a href="{{ route('activities.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                        Tambah Aktivitas
                    </a>
                </div>
            </header>

            <form method="GET" action="{{ route('activities.calendar') }}" class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="date" value="{{ $selected_date }}">

                <div>
                    <label for="land_id" class="block text-sm font-semibold text-gray-800">Lahan</label>
                    <select
                        id="land_id"
                        name="land_id"
                        x-on:change="$el.form.elements.crop_id.value = ''; $el.form.requestSubmit()"
                        class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700"
                    >
                        <option value="">Semua lahan</option>
                        @foreach ($lands as $land)
                            <option value="{{ $land->id }}" @selected((string) request('land_id') === (string) $land->id)>
                                {{ $land->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="crop_id" class="block text-sm font-semibold text-gray-800">Tanaman</label>
                    <select id="crop_id" name="crop_id" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                        <option value="">Semua tanaman</option>
                        @foreach ($crops as $crop)
                            <option value="{{ $crop->id }}" @selected((string) request('crop_id') === (string) $crop->id)>
                                {{ $crop->name }} — {{ $crop->land->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold text-gray-800">Status</label>
                    <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                        <option value="">Semua status</option>
                        <option value="belum_dilakukan" @selected(request('status') === 'belum_dilakukan')>Belum dilakukan</option>
                        <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
                        <option value="dilewati" @selected(request('status') === 'dilewati')>Dilewati</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                        Terapkan
                    </button>
                    <a href="{{ route('activities.calendar', ['month' => $month]) }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                        Reset
                    </a>
                </div>
            </form>

            <section aria-label="Kalender bulanan" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <h2 class="text-xl font-bold capitalize text-gray-950">
                        {{ $monthDate->locale('id')->translatedFormat('F Y') }}
                    </h2>
                    <nav aria-label="Navigasi bulan" class="grid grid-cols-3 gap-2">
                        <a href="{{ $previousMonthUrl }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                            <span aria-hidden="true">&larr;</span>
                            <span class="ml-1">Sebelumnya</span>
                        </a>
                        <a href="{{ $todayUrl }}" class="inline-flex min-h-10 items-center justify-center rounded-md bg-emerald-50 px-3 text-sm font-semibold text-emerald-900 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                            Bulan ini
                        </a>
                        <a href="{{ $nextMonthUrl }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                            <span class="mr-1">Berikutnya</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </nav>
                </div>

                <div class="grid grid-cols-7 border-b border-gray-200 bg-stone-50" role="row">
                    @foreach ($weekdays as $weekday)
                        <div role="columnheader" class="px-1 py-2 text-center text-xs font-bold uppercase text-gray-600 sm:py-3 sm:text-sm">
                            {{ $weekday }}
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7">
                    @foreach ($calendarDays as $calendarDate)
                        @php
                            $dateValue = $calendarDate->toDateString();
                            $isCurrentMonth = $calendarDate->format('Y-m') === $month;
                            $isSelected = $dateValue === $selected_date;
                            $activityCount = $activityCountsByDate->get($dateValue, 0);
                            $selectedDateUrl = route('activities.calendar', [
                                ...$queryFilters,
                                'month' => $month,
                                'date' => $dateValue,
                            ]);
                        @endphp
                        <a
                            href="{{ $selectedDateUrl }}"
                            @if ($isSelected) aria-current="date" @endif
                            aria-label="{{ $calendarDate->locale('id')->translatedFormat('l, d F Y') }}{{ $activityCount > 0 ? ', '.$activityCount.' kegiatan' : '' }}"
                            class="flex min-h-[4.5rem] flex-col items-center justify-start gap-1 border-b border-r border-gray-100 px-0.5 py-2 text-sm transition hover:bg-emerald-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-700 sm:min-h-24 sm:gap-2 sm:px-2 sm:py-3 {{ $isSelected ? 'bg-emerald-50 font-bold text-emerald-950' : 'text-gray-800' }} {{ $isCurrentMonth ? '' : 'bg-gray-50 text-gray-400' }}"
                        >
                            <span class="flex h-7 w-7 items-center justify-center rounded-full {{ $dateValue === $today ? 'bg-emerald-800 text-white' : '' }}">
                                {{ $calendarDate->format('j') }}
                            </span>
                            @if ($activityCount > 0)
                                <span class="inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-emerald-100 px-1 text-[10px] font-bold leading-none text-emerald-900 sm:text-xs">
                                    {{ $activityCount }}
                                    <span class="sr-only">kegiatan</span>
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="selected-date-heading" class="space-y-3">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-emerald-800">Kegiatan terpilih</p>
                        <h2 id="selected-date-heading" class="text-xl font-bold text-gray-950">
                            {{ \Carbon\CarbonImmutable::parse($selected_date, 'Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}
                        </h2>
                    </div>
                    <p class="text-sm text-gray-600">{{ $selected_date_activities->count() }} kegiatan</p>
                </div>

                @if ($selected_date_activities->isEmpty())
                    <div class="rounded-lg border border-dashed border-gray-300 bg-white px-5 py-8 text-center">
                        <p class="font-semibold text-gray-800">Belum ada kegiatan pada tanggal ini</p>
                        <p class="mt-1 text-sm text-gray-600">Pilih tanggal lain atau tambahkan jadwal kegiatan baru.</p>
                        <a href="{{ route('activities.create') }}" class="mt-4 inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                            Tambah Aktivitas
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        @foreach ($selected_date_activities as $activity)
                            <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0 space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-lg font-bold text-gray-950">{{ str_replace('_', ' ', ucfirst($activity->activity_type)) }}</h3>
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
                                        @if ($activity->scheduled_time)
                                            <p class="text-sm text-gray-600">Pukul {{ $activity->scheduled_time->format('H:i') }}</p>
                                        @endif
                                        @if ($activity->product_name || $activity->dosage)
                                            <p class="text-sm text-gray-600">
                                                {{ $activity->product_name }}
                                                @if ($activity->product_name && $activity->dosage) — @endif
                                                @if ($activity->dosage) Dosis {{ $activity->dosage }} @endif
                                            </p>
                                        @endif
                                        @if ($activity->notes)
                                            <p class="whitespace-pre-line text-sm text-gray-600">{{ $activity->notes }}</p>
                                        @endif
                                    </div>
                                    <a href="{{ route('activities.edit', $activity) }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                                        Lihat / Ubah
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($today_activities->isNotEmpty() || $tomorrow_activities->isNotEmpty() || $overdue_activities->isNotEmpty())
                <section aria-labelledby="reminders-heading" class="space-y-4">
                    <div>
                        <p class="text-sm font-semibold text-emerald-800">Pengingat</p>
                        <h2 id="reminders-heading" class="text-2xl font-bold text-gray-950">Jadwal penting</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                        @if ($today_activities->isNotEmpty())
                            <section aria-labelledby="today-reminders-heading" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                                <h3 id="today-reminders-heading" class="font-bold text-emerald-950">Hari ini</h3>
                                <div class="mt-3 space-y-3">
                                    @foreach ($today_activities as $activity)
                                        <article class="rounded-md border border-emerald-100 bg-white p-3">
                                            <h4 class="font-semibold text-gray-950">{{ str_replace('_', ' ', ucfirst($activity->activity_type)) }}</h4>
                                            <p class="mt-1 text-sm text-gray-700">{{ $activity->crop->name }} · {{ $activity->crop->land->name }}</p>
                                            <p class="mt-1 text-xs font-semibold text-emerald-900">
                                                {{ $activity->scheduled_time ? $activity->scheduled_time->format('H:i') : 'Waktu belum ditentukan' }}
                                                · {{ $activity->status === 'belum_dilakukan' ? 'Belum dilakukan' : ($activity->status === 'selesai' ? 'Selesai' : 'Dilewati') }}
                                            </p>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if ($tomorrow_activities->isNotEmpty())
                            <section aria-labelledby="tomorrow-reminders-heading" class="rounded-lg border border-sky-200 bg-sky-50 p-4">
                                <h3 id="tomorrow-reminders-heading" class="font-bold text-sky-950">Besok</h3>
                                <div class="mt-3 space-y-3">
                                    @foreach ($tomorrow_activities as $activity)
                                        <article class="rounded-md border border-sky-100 bg-white p-3">
                                            <h4 class="font-semibold text-gray-950">{{ str_replace('_', ' ', ucfirst($activity->activity_type)) }}</h4>
                                            <p class="mt-1 text-sm text-gray-700">{{ $activity->crop->name }} · {{ $activity->crop->land->name }}</p>
                                            <p class="mt-1 text-xs font-semibold text-sky-900">
                                                {{ $activity->scheduled_time ? $activity->scheduled_time->format('H:i') : 'Waktu belum ditentukan' }}
                                                · {{ $activity->status === 'belum_dilakukan' ? 'Belum dilakukan' : ($activity->status === 'selesai' ? 'Selesai' : 'Dilewati') }}
                                            </p>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if ($overdue_activities->isNotEmpty())
                            <section aria-labelledby="overdue-reminders-heading" class="rounded-lg border border-rose-200 bg-rose-50 p-4">
                                <h3 id="overdue-reminders-heading" class="font-bold text-rose-950">Terlambat</h3>
                                <div class="mt-3 space-y-3">
                                    @foreach ($overdue_activities as $activity)
                                        <article class="rounded-md border border-rose-100 bg-white p-3">
                                            <h4 class="font-semibold text-gray-950">{{ str_replace('_', ' ', ucfirst($activity->activity_type)) }}</h4>
                                            <p class="mt-1 text-sm text-gray-700">{{ $activity->crop->name }} · {{ $activity->crop->land->name }}</p>
                                            <p class="mt-1 text-xs font-semibold text-rose-900">
                                                {{ $activity->scheduled_date->format('d/m/Y') }}
                                                @if ($activity->scheduled_time)
                                                    · {{ $activity->scheduled_time->format('H:i') }}
                                                @endif
                                                · Belum dilakukan
                                            </p>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
