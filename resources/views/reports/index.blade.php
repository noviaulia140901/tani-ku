<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-[#F6F2E8] px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10">
        <div class="mx-auto max-w-[1600px] space-y-6 sm:space-y-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#2F6B4F] sm:text-sm">Ringkasan usaha tani</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#203A2C] sm:text-4xl">Laporan Terpadu</h1>
                    <p class="mt-2 text-sm text-gray-600 sm:text-base">Pantau pemasukan, pengeluaran, dan hasil panen dalam satu periode.</p>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:shrink-0">
                    <a href="{{ route('reports.export', ['format' => 'pdf', 'from' => $from, 'to' => $to]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-[#2F6B4F] bg-white px-3 text-sm font-bold text-[#2F6B4F] transition hover:bg-[#E7F1E2] focus:outline-none focus:ring-2 focus:ring-[#2F6B4F] focus:ring-offset-2 sm:px-4">
                        Unduh PDF
                    </a>
                    <a href="{{ route('reports.export', ['format' => 'xls', 'from' => $from, 'to' => $to]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[#2F6B4F] px-3 text-sm font-bold text-white transition hover:bg-[#244C39] focus:outline-none focus:ring-2 focus:ring-[#2F6B4F] focus:ring-offset-2 sm:px-4">
                        Export Excel
                    </a>
                </div>
            </header>

            <form method="GET" action="{{ route('reports.index') }}" class="rounded-2xl border border-[#E9E7DE] bg-white p-5 shadow-sm sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                    <div>
                        <label for="from" class="block text-sm font-semibold text-gray-700">Dari tanggal</label>
                        <input id="from" name="from" type="date" value="{{ old('from', $from) }}" required class="mt-1 min-h-11 w-full rounded-xl border-gray-300 text-sm focus:border-[#2F6B4F] focus:ring-[#2F6B4F]">
                        @error('from')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="to" class="block text-sm font-semibold text-gray-700">Sampai tanggal</label>
                        <input id="to" name="to" type="date" value="{{ old('to', $to) }}" required class="mt-1 min-h-11 w-full rounded-xl border-gray-300 text-sm focus:border-[#2F6B4F] focus:ring-[#2F6B4F]">
                        @error('to')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[#2F6B4F] px-5 text-sm font-bold text-white transition hover:bg-[#244C39] focus:outline-none focus:ring-2 focus:ring-[#2F6B4F] focus:ring-offset-2">
                        Terapkan filter
                    </button>
                </div>
            </form>

            <section aria-label="Ringkasan keuangan" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <x-dashboard.stat-card
                    label="Pemasukan penjualan"
                    :value="'Rp '.number_format($totalIncome, 2, ',', '.')"
                    detail="Total penjualan pada periode ini"
                    icon="sales"
                    tone="green"
                    compact
                />
                <x-dashboard.stat-card
                    label="Total pengeluaran"
                    :value="'Rp '.number_format($totalExpenses, 2, ',', '.')"
                    detail="Total biaya pada periode ini"
                    icon="expense"
                    tone="red"
                    compact
                />
                <x-dashboard.stat-card
                    label="Saldo bersih"
                    :value="'Rp '.number_format($netBalance, 2, ',', '.')"
                    detail="Pemasukan dikurangi pengeluaran"
                    icon="report"
                    :tone="$netBalance < 0 ? 'red' : 'amber'"
                    compact
                />
            </section>

            <section class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
                <article class="rounded-2xl border border-[#E9E7DE] bg-white p-5 shadow-[0_5px_22px_rgba(44,61,45,0.04)] sm:p-6">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 sm:text-lg">Pemasukan vs pengeluaran</h2>
                        <p class="mt-1 text-xs text-gray-500 sm:text-sm">Perbandingan bulanan untuk {{ \Illuminate\Support\Carbon::parse($from)->locale('id')->translatedFormat('d F Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->locale('id')->translatedFormat('d F Y') }}</p>
                    </div>
                    @if ($hasFinancialData)
                        <div class="mt-5 h-72 sm:h-80">
                            <canvas
                                data-report-chart
                                data-chart="{{ json_encode($financialChart, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                                role="img"
                                aria-label="Grafik pemasukan penjualan dan pengeluaran bulanan dalam Rupiah"
                            ></canvas>
                        </div>
                        <p data-report-chart-status role="status" class="sr-only"></p>
                    @else
                        <div class="mt-5 flex min-h-72 flex-col items-center justify-center rounded-xl bg-[#F8F8F3] px-6 text-center">
                            <x-navigation-icon name="report" class="h-9 w-9 text-[#78927E]" />
                            <p class="mt-3 text-sm font-semibold text-gray-800 sm:text-base">Belum ada transaksi pada periode ini.</p>
                            <p class="mt-1 max-w-sm text-xs text-gray-500 sm:text-sm">Penjualan dan pengeluaran Anda akan dirangkum setelah dicatat.</p>
                        </div>
                    @endif
                </article>

                <article class="rounded-2xl border border-[#E9E7DE] bg-white p-5 shadow-[0_5px_22px_rgba(44,61,45,0.04)] sm:p-6">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 sm:text-lg">Ringkasan panen</h2>
                        <p class="mt-1 text-xs text-gray-500 sm:text-sm">{{ $harvestCount }} catatan panen pada periode ini</p>
                    </div>
                    @if ($harvestSummary->isEmpty())
                        <div class="mt-5 flex min-h-48 flex-col items-center justify-center rounded-xl bg-[#F8F8F3] px-5 text-center">
                            <x-navigation-icon name="harvest" class="h-9 w-9 text-[#78927E]" />
                            <p class="mt-3 text-sm font-semibold text-gray-800 sm:text-base">Belum ada panen pada periode ini.</p>
                            <p class="mt-1 text-xs text-gray-500 sm:text-sm">Catatan panen akan dirangkum berdasarkan satuannya.</p>
                        </div>
                    @else
                        <dl class="mt-5 divide-y divide-[#E9E7DE]">
                            @foreach ($harvestSummary as $harvest)
                                <div class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                                    <dt class="text-xs font-medium text-gray-600 sm:text-sm">{{ $harvest->unit }}</dt>
                                    <dd class="text-base font-bold tabular-nums text-[#203A2C] sm:text-lg">{{ number_format((float) $harvest->quantity, 2, ',', '.') }} {{ $harvest->unit }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
