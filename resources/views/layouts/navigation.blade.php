
@php
    $isAdmin = auth()->check() && auth()->user()->isAdmin();

    $navigationItems = $isAdmin
        ? [
            [
                'label' => 'Dashboard Admin',
                'route' => 'admin.dashboard',
                'active' => 'admin.dashboard',
                'icon' => 'dashboard',
            ],
            [
                'label' => 'Pengelolaan Akun',
                'route' => 'admin.users.index',
                'active' => 'admin.users.*',
                'icon' => 'profile',
            ],
        ]
        : [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'dashboard'],
            ['label' => 'Lahan', 'route' => 'lands.index', 'active' => 'lands.*', 'icon' => 'land'],
            ['label' => 'Tanaman', 'route' => 'crops.index', 'active' => 'crops.*', 'icon' => 'crop'],
            ['label' => 'Aktivitas', 'route' => 'activities.index', 'active' => 'activities.*', 'icon' => 'activity'],
            ['label' => 'Kalender', 'route' => 'activities.calendar', 'active' => 'activities.calendar', 'icon' => 'calendar'],
            ['label' => 'Panen', 'route' => 'harvests.index', 'active' => 'harvests.*', 'icon' => 'harvest'],
            ['label' => 'Penjualan', 'route' => 'sales.index', 'active' => 'sales.*', 'icon' => 'sales'],
            ['label' => 'Pengeluaran', 'route' => 'expenses.index', 'active' => 'expenses.*', 'icon' => 'expense'],
            ['label' => 'Laporan', 'route' => 'reports.index', 'active' => 'reports.*', 'icon' => 'report'],
            ['label' => 'Produk', 'route' => 'products.index', 'active' => 'products.*', 'icon' => 'product'],
            ['label' => 'Catatan', 'route' => 'notes.index', 'active' => 'notes.*', 'icon' => 'note'],
        ];

    $brandRoute = $isAdmin
        ? route('admin.dashboard')
        : route('dashboard');
@endphp

<div x-data="{ open: false }">

    {{-- Header mobile --}}
    <header class="fixed inset-x-0 top-0 z-40 flex h-16 items-center justify-between border-b border-[#E5E8DE] bg-white px-4 lg:hidden">
        <a href="{{ $brandRoute }}" class="flex items-center gap-2.5 font-bold text-[#2F6B4F]">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#DDECCF]">
                <x-navigation-icon name="brand" class="h-6 w-6" />
            </span>

            {{ $isAdmin ? 'TaniKu Admin' : 'TaniKu' }}
        </a>

        <button
            type="button"
            @click="open = !open"
            :aria-expanded="open"
            aria-controls="mobile-navigation"
            aria-label="Buka navigasi"
            class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#2F6B4F]"
        >
            <svg x-show="!open" aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
            </svg>

            <svg x-show="open" x-cloak aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
            </svg>
        </button>
    </header>

    {{-- Latar belakang menu mobile --}}
    <button
        type="button"
        x-show="open"
        x-cloak
        @click="open = false"
        aria-label="Tutup navigasi"
        class="fixed inset-0 z-40 bg-gray-950/40 lg:hidden"
    ></button>

    {{-- Sidebar --}}
    <aside
        id="mobile-navigation"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r px-5 py-6 transition-transform duration-200 lg:translate-x-0
        @if($isAdmin)
            bg-[#172820] border-[#30483B]
        @else
            bg-[#DDECCF] border-[#D3E1CB]
        @endif"
    >

        {{-- Branding desktop --}}
        <a href="{{ $brandRoute }}" class="hidden items-center gap-3 px-2 lg:flex">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#26734D] text-white">
                <x-navigation-icon name="brand" class="h-7 w-7" />
            </span>

            <span>
                <span class="block text-xl font-bold tracking-tight
                    @if($isAdmin) text-white @else text-[#244C39] @endif">
                    {{ $isAdmin ? 'TaniKu Admin' : 'TaniKu' }}
                </span>

                <span class="block text-xs font-medium
                    @if($isAdmin) text-[#A8C5B2] @else text-[#547360] @endif">
                    {{ $isAdmin ? 'Panel Administrasi' : 'Kelola kebun lebih baik' }}
                </span>
            </span>
        </a>

        {{-- Branding mobile --}}
        <div class="mb-5 flex items-center gap-3 px-2 lg:hidden">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#26734D] text-white">
                <x-navigation-icon name="brand" class="h-7 w-7" />
            </span>

            <span>
                <span class="block text-xl font-bold tracking-tight
                    @if($isAdmin) text-white @else text-[#244C39] @endif">
                    {{ $isAdmin ? 'TaniKu Admin' : 'TaniKu' }}
                </span>

                <span class="block text-xs font-medium
                    @if($isAdmin) text-[#A8C5B2] @else text-[#547360] @endif">
                    {{ $isAdmin ? 'Panel Administrasi' : 'Kelola kebun lebih baik' }}
                </span>
            </span>
        </div>

        {{-- Judul navigasi --}}
        <p class="mb-3 px-3 text-[11px] font-bold uppercase tracking-[0.16em]
            @if($isAdmin) text-[#A8C5B2] @else text-[#547360] @endif">
            {{ $isAdmin ? 'Administrasi' : 'Menu utama' }}
        </p>

        {{-- Daftar menu --}}
        <nav aria-label="Navigasi utama" class="flex-1 space-y-1 overflow-y-auto">
            @foreach ($navigationItems as $item)
                @php
                    $active = request()->routeIs($item['active']);

                    if (!$isAdmin && $item['route'] === 'activities.index') {
                        $active = request()->routeIs(
                            'activities.index',
                            'activities.create',
                            'activities.edit'
                        );
                    }
                @endphp

                <a
                    href="{{ route($item['route']) }}"
                    @if($active) aria-current="page" @endif
                    @click="open = false"
                    class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition
                    @if($active)
                        bg-[#26734D] text-white shadow-sm
                    @elseif($isAdmin)
                        text-[#D2E4D8] hover:bg-white/10 hover:text-white
                    @else
                        text-[#355641] hover:bg-white/70 hover:text-[#244C39]
                    @endif"
                >
                    <x-navigation-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Profil dan logout --}}
        <div class="mt-5 border-t pt-4
            @if($isAdmin) border-[#30483B] @else border-[#C7D8BE] @endif">

            <a
                href="{{ route('profile.edit') }}"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition
                    @if($isAdmin) hover:bg-white/10 @else hover:bg-white/60 @endif"
            >
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold
                    @if($isAdmin)
                        bg-[#30483B] text-white
                    @else
                        bg-white text-[#2F6B4F]
                    @endif">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold
                        @if($isAdmin) text-white @else text-[#244C39] @endif">
                        {{ auth()->user()->name }}
                    </span>

                    <span class="block truncate text-xs
                        @if($isAdmin) text-[#A8C5B2] @else text-[#547360] @endif">
                        {{ auth()->user()->email }}
                    </span>
                </span>

                <x-navigation-icon
                    name="profile"
                    class="h-5 w-5 shrink-0 @if($isAdmin) text-[#A8C5B2] @else text-[#547360] @endif"
                />
            </a>

            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf

                <button
                    type="submit"
                    class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition
                    @if($isAdmin)
                        text-[#D2E4D8] hover:bg-white/10 hover:text-white
                    @else
                        text-[#355641] hover:bg-white/70 hover:text-[#244C39]
                    @endif"
                >
                    <x-navigation-icon name="logout" class="h-5 w-5 shrink-0" />
                    Keluar
                </button>
            </form>
        </div>
    </aside>
</div>
