<x-app-layout>
    <div data-dashboard class="min-h-screen bg-[#F6F2E8] px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-7">
        <div class="mx-auto max-w-[1440px] space-y-5">
            <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.15em] text-[#2F6B4F]">Ringkasan kebun</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight text-[#203A2C] sm:text-4xl">Halo, {{ auth()->user()->name }}</h1>
                    <p class="mt-2 text-gray-600">Pantau kondisi lahan dan usaha tani Anda hari ini.</p>
                </div>
                <p class="inline-flex w-fit items-center gap-2 rounded-full border border-[#E7E3D8] bg-white/80 px-4 py-2 text-sm font-semibold text-gray-600">
                    <x-navigation-icon name="activity" class="h-4 w-4 text-[#2F6B4F]" />
                    {{ $dashboardDate->locale('id')->translatedFormat('l, d F Y') }}
                </p>
            </header>

            <section class="relative isolate overflow-hidden rounded-[1.75rem] bg-[#2F6B4F] px-5 py-5 text-white shadow-sm sm:px-7 sm:py-6">
                <div class="relative z-10 max-w-2xl">
                    <p class="text-sm font-semibold text-[#DDECCF]">Ruang kendali pertanian Anda</p>
                    <h2 class="mt-2 text-xl font-bold tracking-tight sm:text-2xl">Tumbuh lebih terencana, panen lebih bermakna.</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/80">Satu tempat untuk memantau lahan, kegiatan, hasil panen, dan catatan usaha tani.</p>
                    <a href="{{ route('lands.index') }}" class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-xl bg-[#DDECCF] px-4 text-sm font-bold text-[#244C39] transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#2F6B4F]">
                        Lihat lahan
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
                <svg class="absolute -right-5 -top-10 -z-0 h-64 w-64 opacity-20 sm:right-12 sm:top-1/2 sm:h-72 sm:w-72 sm:-translate-y-1/2" viewBox="0 0 240 240" fill="none" aria-hidden="true">
                    <circle cx="120" cy="120" r="91" stroke="#DDECCF" stroke-width="2" />
                    <circle cx="120" cy="120" r="65" stroke="#DDECCF" stroke-width="2" />
                    <path d="M120 207V73m0 55c-38 0-59-21-59-59 38 0 59 21 59 59Zm0-28c0-38 21-59 59-59 0 38-21 59-59 59Z" stroke="#DDECCF" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </section>

            <section aria-label="Ringkasan keuangan dan kebun" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-dashboard.stat-card
                    label="Total pemasukan"
                    :value="'Rp '.number_format((float) $income, 0, ',', '.')"
                    :detail="$salesCount > 0 ? 'Dari seluruh penjualan Anda' : 'Belum ada penjualan'"
                    icon="sales"
                    tone="green"
                />
                <x-dashboard.stat-card
                    label="Total pengeluaran"
                    :value="'Rp '.number_format((float) $expenseTotal, 0, ',', '.')"
                    :detail="$expenseCount > 0 ? 'Dari seluruh biaya tercatat' : 'Belum ada pengeluaran'"
                    icon="expense"
                    tone="red"
                />
                <x-dashboard.stat-card
                    :label="$profit > 0 ? 'Keuntungan' : ($profit < 0 ? 'Kerugian' : 'Impas')"
                    :value="'Rp '.number_format((float) $profit, 0, ',', '.')"
                    detail="Pemasukan dikurangi pengeluaran"
                    icon="sales"
                    :tone="$profit < 0 ? 'red' : 'amber'"
                />
                <x-dashboard.stat-card
                    label="Lahan aktif"
                    :value="$activeLandCount"
                    :detail="$activeLandCount > 0 ? 'Lahan berstatus aktif' : 'Belum ada lahan aktif'"
                    icon="land"
                    tone="green"
                />
                <x-dashboard.stat-card
                    label="Tanaman ditanam"
                    :value="$plantedCropCount"
                    :detail="$plantedCropCount > 0 ? 'Dalam tahap tanam atau perawatan' : 'Belum ada tanaman yang sedang ditanam'"
                    icon="crop"
                    tone="blue"
                />
                <x-dashboard.stat-card
                    label="Perkiraan panen"
                    :value="$upcomingHarvestCount"
                    :detail="$upcomingHarvestCount > 0 ? 'Dalam 30 hari ke depan' : 'Belum ada tanaman yang akan panen dalam 30 hari'"
                    icon="harvest"
                    tone="amber"
                />
                <x-dashboard.stat-card
                    label="Produk terdaftar"
                    :value="$totalProductCount"
                    :detail="$lowStockProductCount > 0 ? $lowStockProductCount.' produk perlu ditambah' : 'Stok produk tercatat'"
                    icon="product"
                    :tone="$lowStockProductCount > 0 ? 'red' : 'green'"
                />
            </section>

            <section aria-label="Grafik usaha tani" class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <article class="rounded-2xl border border-[#E9E7DE] bg-white p-5 shadow-[0_5px_22px_rgba(44,61,45,0.04)] sm:p-6">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Pemasukan &amp; pengeluaran</h2>
                            <p class="mt-1 text-sm text-gray-500">Ringkasan bulanan · {{ $chartPeriodLabel }}</p>
                        </div>
                        <span class="mt-1 inline-flex w-fit rounded-full bg-[#E7F1E2] px-3 py-1 text-xs font-semibold text-[#2F6B4F]">6 bulan terakhir</span>
                    </div>
                    @if (array_sum($financialChart['income']) + array_sum($financialChart['expenses']) > 0)
                        <div class="mt-5 h-64 sm:h-72">
                            <canvas
                                data-financial-chart
                                data-chart="{{ json_encode($financialChart, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                                role="img"
                                aria-label="Grafik pemasukan penjualan dan pengeluaran bulanan dalam Rupiah"
                            ></canvas>
                        </div>
                    @else
                        <div class="mt-5 flex min-h-64 flex-col items-center justify-center rounded-xl bg-[#F8F8F3] px-6 text-center">
                            <x-navigation-icon name="sales" class="h-9 w-9 text-[#78927E]" />
                            <p class="mt-3 font-semibold text-gray-800">Belum ada transaksi pada periode ini.</p>
                            <p class="mt-1 max-w-sm text-sm text-gray-500">Penjualan dan pengeluaran yang tercatat akan ditampilkan di sini.</p>
                        </div>
                    @endif
                </article>

                <article class="rounded-2xl border border-[#E9E7DE] bg-white p-5 shadow-[0_5px_22px_rgba(44,61,45,0.04)] sm:p-6">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Hasil panen</h2>
                            <p class="mt-1 text-sm text-gray-500">Jumlah panen per satuan · {{ $chartPeriodLabel }}</p>
                        </div>
                        <a href="{{ route('harvests.index') }}" class="mt-1 text-sm font-semibold text-[#2F6B4F] hover:underline">Lihat catatan panen</a>
                    </div>
                    @if (count($harvestChart['datasets']) > 0)
                        <div class="mt-5 h-64 sm:h-72">
                            <canvas
                                data-harvest-chart
                                data-chart="{{ json_encode($harvestChart, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                                role="img"
                                aria-label="Grafik jumlah panen per satuan dari waktu ke waktu"
                            ></canvas>
                        </div>
                    @else
                        <div class="mt-5 flex min-h-64 flex-col items-center justify-center rounded-xl bg-[#F8F8F3] px-6 text-center">
                            <x-navigation-icon name="harvest" class="h-9 w-9 text-[#78927E]" />
                            <p class="mt-3 font-semibold text-gray-800">Belum ada hasil panen pada periode ini.</p>
                            <p class="mt-1 max-w-sm text-sm text-gray-500">Jumlah panen dikelompokkan menurut satuan agar hasilnya dapat dibandingkan dengan tepat.</p>
                        </div>
                    @endif
                </article>
            </section>

            <section aria-label="Peta lahan dan cuaca" class="grid grid-cols-1 gap-4 xl:grid-cols-[1.3fr_0.7fr]">
                <article class="overflow-hidden rounded-2xl border border-[#E9E7DE] bg-white shadow-[0_5px_22px_rgba(44,61,45,0.04)]">
                    <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Peta lahan</h2>
                            <p class="mt-1 text-sm text-gray-500">Titik koordinat lahan yang tersimpan di akun Anda</p>
                        </div>
                        <div class="flex flex-col gap-2 sm:min-w-64">
                            <label for="weather-land-select" class="text-xs font-semibold text-gray-700">Lahan untuk prakiraan cuaca</label>
                            <select id="weather-land-select" data-weather-land-select class="min-h-11 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-[#2F6B4F] focus:ring-[#2F6B4F]">
                                @foreach ($landsForMap->whereNotNull('latitude')->whereNotNull('longitude') as $land)
                                    <option value="{{ $land->id }}">{{ $land->name }} · {{ $land->location }}</option>
                                @endforeach
                                @if ($landsForMap->whereNotNull('latitude')->whereNotNull('longitude')->isEmpty())
                                    <option value="">Belum ada lahan berkoordinat</option>
                                @endif
                            </select>
                            <button
                                type="button"
                                data-load-location
                                @disabled($landsForMap->isEmpty())
                                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#2F6B4F] px-4 text-sm font-bold text-white transition hover:bg-[#244C39] focus:outline-none focus:ring-2 focus:ring-[#2F6B4F] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-300"
                            >
                                <x-navigation-icon name="land" class="h-4 w-4" />
                                Muat peta &amp; cuaca
                            </button>
                        </div>
                    </div>
                    <div class="px-5 pb-3 sm:px-6">
                        <p class="text-xs leading-5 text-gray-500">Peta dan cuaca menggunakan koordinat yang Anda simpan pada halaman Lahan; alamat teks tidak dikirim untuk ditebak menjadi titik. Permintaan lokasi dan cuaca eksternal hanya dilakukan setelah tombol ditekan; tile peta dasar dimuat dari OpenStreetMap saat halaman dibuka.</p>
                        <p data-location-message aria-live="polite" class="mt-2 min-h-5 text-sm text-gray-600">
                            @if ($landsForMap->isEmpty())
                                Tambahkan lahan untuk menampilkan lokasi pada peta.
                            @else
                                Peta dan cuaca belum dimuat. Daftar lahan Anda tetap tersedia di bawah.
                            @endif
                        </p>
                    </div>
                    <div class="relative mx-5 mb-4 overflow-hidden rounded-xl bg-[#EAF0E5] sm:mx-6">
                        <div class="absolute right-2 top-2 z-[500] flex gap-2">
                            <button type="button" data-map-fit class="min-h-9 rounded-lg bg-white/95 px-3 text-xs font-semibold text-gray-800 shadow">Tampilkan semua</button>
                            <button type="button" data-map-fullscreen class="min-h-9 rounded-lg bg-white/95 px-3 text-xs font-semibold text-gray-800 shadow">Layar penuh</button>
                        </div>
                        <button type="button" data-map-satellite class="absolute bottom-2 left-2 z-[500] min-h-9 rounded-lg bg-white/95 px-3 text-xs font-semibold text-gray-800 shadow">
                            Lihat Satelit di Google Maps
                        </button>
                        <div
                            data-farm-map
                            data-endpoint="{{ route('dashboard.location-data') }}"
                            class="h-72 w-full sm:h-80"
                            role="application"
                            aria-label="Peta interaktif lahan milik Anda"
                        ></div>
                    </div>
                    <div class="mx-5 mb-4 flex flex-wrap items-center justify-between gap-3 sm:mx-6">
                        <p data-map-fallback aria-live="polite" class="text-sm text-gray-600">
                            @if ($landsForMap->isEmpty())
                                Belum ada lahan untuk ditampilkan. Tampilan awal peta Indonesia bukan lokasi lahan atau cuaca.
                            @elseif ($landsForMap->whereNotNull('latitude')->whereNotNull('longitude')->isEmpty())
                                Belum ada titik koordinat. Tambahkan atau perbarui titik pada halaman Lahan.
                            @else
                                Koordinat lahan belum dimuat. Tampilan awal peta Indonesia bukan lokasi lahan atau cuaca.
                            @endif
                        </p>
                        @if ($landsForMap->isEmpty())
                            <a href="{{ route('lands.create') }}" class="inline-flex min-h-10 items-center rounded-xl bg-[#2F6B4F] px-4 text-sm font-semibold text-white hover:bg-[#244C39]">Tambah lahan</a>
                        @endif
                    </div>
                    <p data-tile-message aria-live="polite" class="hidden px-5 pb-3 text-sm text-amber-800 sm:px-6"></p>
                    <div class="px-5 pb-5 sm:px-6">
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <label class="sr-only" for="map-land-search">Cari lahan</label>
                            <input id="map-land-search" data-map-search type="search" placeholder="Cari nama atau lokasi lahan" class="min-h-11 min-w-0 flex-1 rounded-xl border-gray-300 text-sm shadow-sm focus:border-[#2F6B4F] focus:ring-[#2F6B4F]">
                            <label class="sr-only" for="map-land-status">Filter status lahan</label>
                            <select id="map-land-status" data-map-status-filter class="min-h-11 rounded-xl border-gray-300 text-sm shadow-sm focus:border-[#2F6B4F] focus:ring-[#2F6B4F]">
                                <option value="">Semua status</option>
                                <option value="aktif">Aktif</option>
                                <option value="persiapan">Persiapan</option>
                                <option value="tidak_aktif">Tidak aktif</option>
                            </select>
                        </div>
                        <h3 class="mt-4 text-sm font-bold text-gray-800">Daftar lahan</h3>
                        <ul data-map-location-list class="mt-2 max-h-52 divide-y divide-gray-100 overflow-y-auto">
                            @forelse ($landsForMap as $land)
                                <li class="flex items-start justify-between gap-3 border-t border-gray-100 py-3 first:border-0">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $land->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $land->location }}</p>
                                        <a href="{{ route('lands.edit', $land) }}" class="mt-1 inline-block text-xs font-semibold text-emerald-800 underline">Edit lahan</a>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">{{ $land->status === 'aktif' ? 'Aktif' : str_replace('_', ' ', ucfirst($land->status)) }}{{ $land->latitude === null || $land->longitude === null ? ' · tanpa titik' : '' }}</span>
                                </li>
                            @empty
                                <li class="border-t border-gray-100 py-4 text-sm text-gray-500">Belum ada lahan yang terdaftar.</li>
                            @endforelse
                        </ul>
                    </div>
                </article>

                <article class="rounded-2xl border border-[#E9E7DE] bg-[#DCEAF0] p-5 shadow-[0_5px_22px_rgba(44,61,45,0.04)] sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[#426C7B]">Cuaca di sekitar lahan</p>
                            <h2 class="mt-1 text-lg font-bold text-[#203A2C]">Prakiraan lokal</h2>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/80 text-2xl text-[#426C7B]">
                            <span data-weather-icon aria-hidden="true">🌤️</span>
                        </span>
                    </div>
                    <p data-weather-message aria-live="polite" class="mt-3 text-sm text-gray-600">Tekan “Muat peta &amp; cuaca” untuk meminta cuaca aktual dari Open-Meteo.</p>
                    <div data-weather-panel class="mt-5 hidden">
                        <div class="rounded-2xl bg-white/80 p-4">
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <p data-weather-condition class="font-semibold text-[#315B6B]">—</p>
                                    <p data-weather-temperature class="mt-1 text-5xl font-bold tracking-tight text-[#203A2C]">—</p>
                                </div>
                                <p class="pb-1 text-right text-xs font-medium text-gray-500">Suhu saat ini</p>
                            </div>
                            <div class="mt-4 grid grid-cols-3 gap-2 border-t border-gray-100 pt-4 text-center">
                                <div>
                                    <p class="text-xs text-gray-500">Kelembapan</p>
                                    <p data-weather-humidity class="mt-1 text-sm font-bold text-gray-800">—</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Peluang hujan</p>
                                    <p data-weather-rain class="mt-1 text-sm font-bold text-gray-800">—</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Angin · arah</p>
                                    <p data-weather-wind class="mt-1 text-sm font-bold text-gray-800">—</p>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 border-t border-gray-100 pt-3 text-center">
                                <div>
                                    <p class="text-xs text-gray-500">Terasa seperti</p>
                                    <p data-weather-apparent class="mt-1 text-sm font-bold text-gray-800">—</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Curah hujan saat ini</p>
                                    <p data-weather-precipitation class="mt-1 text-sm font-bold text-gray-800">—</p>
                                </div>
                            </div>
                        </div>
                        <h3 class="mt-5 text-sm font-bold text-gray-800">Prakiraan per jam (24 jam)</h3>
                        <ul data-weather-hourly class="mt-2 flex gap-2 overflow-x-auto pb-2"></ul>
                        <h3 class="mt-4 text-sm font-bold text-gray-800">Prakiraan 7 hari</h3>
                        <ul data-weather-forecast class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-2 2xl:grid-cols-3"></ul>
                        <p data-weather-advice class="mt-4 rounded-xl border border-[#BFD5DD] bg-white/70 p-3 text-sm leading-6 text-[#315B6B]"></p>
                        <p data-weather-updated class="mt-4 text-xs text-gray-500"></p>
                    </div>
                    <div class="mt-5 rounded-xl border border-[#BFD5DD] bg-white/40 px-4 py-3 text-xs leading-5 text-[#426C7B]">
                        Sumber data: Open-Meteo. Prakiraan diambil untuk titik lahan yang dipilih. Panduan kegiatan mengikuti prakiraan, bukan pengganti pengamatan cuaca setempat.
                    </div>
                </article>
            </section>

            <section aria-label="Jadwal dan hasil pertanian" class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <article class="rounded-2xl border border-[#E9E7DE] bg-white shadow-[0_5px_22px_rgba(44,61,45,0.04)]">
                    <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Pengingat aktivitas</h2>
                            <p class="mt-1 text-sm text-gray-500">Aktivitas hari ini, mendatang, dan terlambat</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('activities.index') }}" class="inline-flex min-h-10 items-center rounded-xl border border-gray-200 px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Lihat semua</a>
                            <a href="{{ route('activities.create') }}" class="inline-flex min-h-10 items-center rounded-xl bg-[#2F6B4F] px-3 text-sm font-semibold text-white hover:bg-[#244C39]">Tambah aktivitas</a>
                        </div>
                    </div>
                    @foreach ([
                        [
                            'label' => 'Hari ini',
                            'activities' => $todayActivities,
                            'count' => $todayActivitiesCount,
                            'tone' => 'bg-[#E7F1E2] text-[#2F6B4F]',
                            'empty' => 'Tidak ada aktivitas yang dijadwalkan hari ini.',
                        ],
                        [
                            'label' => 'Mendatang',
                            'activities' => $futureActivities,
                            'count' => $futureActivitiesCount,
                            'tone' => 'bg-[#FBF0D9] text-[#855D16]',
                            'empty' => 'Belum ada aktivitas mendatang.',
                        ],
                        [
                            'label' => 'Terlambat',
                            'activities' => $overdueActivities,
                            'count' => $overdueActivitiesCount,
                            'tone' => 'bg-rose-50 text-rose-800',
                            'empty' => 'Tidak ada aktivitas yang terlambat.',
                        ],
                    ] as $reminderGroup)
                        <section class="border-b border-gray-100 last:border-0">
                            <div class="flex items-center justify-between gap-3 px-5 py-3 sm:px-6">
                                <h3 class="text-sm font-bold text-gray-800">{{ $reminderGroup['label'] }}</h3>
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $reminderGroup['tone'] }}">{{ $reminderGroup['count'] }}</span>
                            </div>
                            @forelse ($reminderGroup['activities'] as $activity)
                                <div class="flex flex-col gap-2 border-t border-gray-100 px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-gray-900">
                                            <a href="{{ route('activities.edit', $activity) }}" class="rounded-sm underline decoration-transparent underline-offset-2 hover:decoration-current focus:outline-none focus:ring-2 focus:ring-[#2F6B4F]">
                                                {{ str_replace('_', ' ', ucfirst($activity->activity_type)) }} · {{ $activity->crop->name }}
                                            </a>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-600">Lahan: {{ $activity->crop->land->name }}</p>
                                        <p class="mt-1 text-xs font-medium text-gray-500">
                                            Status: {{ match ($activity->status) {
                                                'belum_dilakukan' => 'Belum dilakukan',
                                                'selesai' => 'Selesai',
                                                'dilewati' => 'Dilewati',
                                                default => 'Status tidak dikenal',
                                            } }}
                                        </p>
                                    </div>
                                    <p class="shrink-0 text-sm font-medium text-gray-600">
                                        {{ $activity->scheduled_date->format('d/m/Y') }}
                                        @if ($activity->scheduled_time)
                                            · {{ $activity->scheduled_time->format('H:i') }}
                                        @endif
                                    </p>
                                </div>
                            @empty
                                <p class="px-5 pb-4 text-sm text-gray-500 sm:px-6">{{ $reminderGroup['empty'] }}</p>
                            @endforelse
                        </section>
                    @endforeach
                </article>

                <article class="rounded-2xl border border-[#E9E7DE] bg-white shadow-[0_5px_22px_rgba(44,61,45,0.04)]">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-5 sm:px-6">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Perkiraan panen</h2>
                            <p class="mt-1 text-sm text-gray-500">Tanaman yang diperkirakan siap dalam 30 hari</p>
                        </div>
                        <span class="rounded-full bg-[#FBF0D9] px-3 py-1.5 text-sm font-bold text-[#855D16]">{{ $upcomingHarvestCount }}</span>
                    </div>
                    @forelse ($upcomingHarvests as $crop)
                        <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-4 last:border-0 sm:px-6">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $crop->name }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $crop->land->name }}</p>
                                <p class="mt-1 text-sm text-gray-600">{{ $dashboardDate->startOfDay()->diffInDays(\Carbon\CarbonImmutable::parse($crop->estimated_harvest_date->toDateString(), $dashboardDate->timezone)) }} hari lagi</p>
                            </div>
                            <p class="shrink-0 text-sm font-semibold text-gray-700">{{ $crop->estimated_harvest_date->format('d/m/Y') }}</p>
                        </div>
                    @empty
                        <p class="px-6 py-9 text-center text-sm text-gray-500">Belum ada perkiraan panen dalam 30 hari.</p>
                    @endforelse
                </article>
            </section>

            <section aria-label="Stok dan aktivitas terbaru" class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <article class="rounded-2xl border border-[#E9E7DE] bg-white shadow-[0_5px_22px_rgba(44,61,45,0.04)]">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-5 sm:px-6">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Peringatan stok</h2>
                            <p class="mt-1 text-sm text-gray-500">Produk pada atau di bawah batas minimum</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="rounded-full bg-rose-50 px-3 py-1.5 text-sm font-bold text-rose-800">{{ $lowStockProductCount }}</span>
                            <a href="{{ route('products.index') }}" class="inline-flex min-h-10 items-center rounded-xl border border-gray-200 px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Produk</a>
                        </div>
                    </div>
                    @forelse ($lowStockProducts as $product)
                        <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $product->name }}</p>
                                <p class="mt-1 text-sm text-gray-600">
                                    Stok {{ number_format((float) $product->stock, 2, ',', '.') }} {{ $product->unit }}
                                    <span class="text-gray-400">·</span>
                                    Batas minimum {{ number_format((float) $product->minimum_stock, 2, ',', '.') }} {{ $product->unit }}
                                </p>
                            </div>
                            @if ((float) $product->stock <= 0)
                                <span class="inline-flex w-fit shrink-0 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-800">Stok habis</span>
                            @else
                                <span class="inline-flex w-fit shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800">Stok menipis</span>
                            @endif
                        </div>
                    @empty
                        <p class="px-6 py-9 text-center text-sm text-gray-500">Tidak ada peringatan stok. Semua produk masih di atas batas minimum.</p>
                    @endforelse
                </article>

                <article class="rounded-2xl border border-[#E9E7DE] bg-white shadow-[0_5px_22px_rgba(44,61,45,0.04)]">
                    <div class="border-b border-gray-100 px-5 py-5 sm:px-6">
                        <h2 class="text-lg font-bold text-gray-900">Aktivitas terbaru</h2>
                        <p class="mt-1 text-sm text-gray-500">Catatan pekerjaan kebun terakhir</p>
                    </div>
                    @forelse ($recentActivities as $activity)
                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 last:border-0 sm:px-6">
                            <div>
                                <p class="font-semibold text-gray-900">{{ str_replace('_', ' ', ucfirst($activity->activity_type)) }} · {{ $activity->crop->name }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $activity->crop->land->name }} · {{ $activity->scheduled_date->format('d/m/Y') }}</p>
                                <p class="mt-1 text-sm text-gray-600">Status: {{ str_replace('_', ' ', ucfirst($activity->status)) }}</p>
                            </div>
                            <a href="{{ route('activities.edit', $activity) }}" class="shrink-0 text-sm font-semibold text-[#2F6B4F] hover:underline">Lihat</a>
                        </div>
                    @empty
                        <p class="px-6 py-9 text-center text-sm text-gray-500">Belum ada aktivitas tercatat.</p>
                    @endforelse
                </article>

                <article class="rounded-2xl border border-[#E9E7DE] bg-white shadow-[0_5px_22px_rgba(44,61,45,0.04)]">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-5 sm:px-6">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Panen terbaru</h2>
                            <p class="mt-1 text-sm text-gray-500">Hasil panen yang tercatat</p>
                        </div>
                        <a href="{{ route('harvests.index') }}" class="text-sm font-semibold text-[#2F6B4F] hover:underline">Lihat semua</a>
                    </div>
                    @forelse ($recentHarvests as $harvest)
                        <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-4 last:border-0 sm:px-6">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $harvest->crop->name }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $harvest->crop->land->name }} · {{ $harvest->harvest_date->format('d/m/Y') }}</p>
                            </div>
                            <p class="shrink-0 font-bold text-[#2F6B4F]">{{ number_format((float) $harvest->quantity, 2, ',', '.') }} {{ $harvest->unit }}</p>
                        </div>
                    @empty
                        <p class="px-6 py-9 text-center text-sm text-gray-500">Belum ada hasil panen tercatat.</p>
                    @endforelse
                </article>

                <article class="rounded-2xl border border-[#E9E7DE] bg-white shadow-[0_5px_22px_rgba(44,61,45,0.04)]">
                    <div class="border-b border-gray-100 px-5 py-5 sm:px-6">
                        <h2 class="text-lg font-bold text-gray-900">Penjualan terbaru</h2>
                        <p class="mt-1 text-sm text-gray-500">Catatan transaksi terakhir</p>
                    </div>
                    @forelse ($recentSales as $sale)
                        <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $sale->harvest->crop->name }} - {{ $sale->buyer_name }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $sale->sale_date->format('d/m/Y') }} · {{ number_format((float) $sale->quantity, 2, ',', '.') }} {{ $sale->unit }}</p>
                            </div>
                            <p class="shrink-0 font-bold tabular-nums text-[#2F6B4F]">Rp {{ number_format((float) $sale->total, 0, ',', '.') }}</p>
                        </div>
                    @empty
                        <p class="px-6 py-9 text-center text-sm text-gray-500">Belum ada penjualan tercatat.</p>
                    @endforelse
                </article>

                <article class="rounded-2xl border border-[#E9E7DE] bg-white shadow-[0_5px_22px_rgba(44,61,45,0.04)] xl:col-span-2">
                    <div class="border-b border-gray-100 px-5 py-5 sm:px-6">
                        <h2 class="text-lg font-bold text-gray-900">Pengeluaran terbaru</h2>
                        <p class="mt-1 text-sm text-gray-500">Catatan biaya terakhir</p>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($recentExpenses as $expense)
                            <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $expense->description }}</p>
                                    <p class="mt-1 text-sm text-gray-500">{{ $expense->expense_date->format('d/m/Y') }} · {{ str_replace('_', ' ', ucfirst($expense->category)) }}</p>
                                    @if ($expense->product)
                                        <p class="mt-1 text-sm text-gray-500">Produk: {{ $expense->product->name }}</p>
                                    @endif
                                </div>
                                <p class="shrink-0 font-bold tabular-nums text-gray-800">Rp {{ number_format((float) $expense->amount, 0, ',', '.') }}</p>
                            </div>
                        @empty
                            <p class="px-6 py-9 text-center text-sm text-gray-500">Belum ada pengeluaran tercatat.</p>
                        @endforelse
                    </div>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
