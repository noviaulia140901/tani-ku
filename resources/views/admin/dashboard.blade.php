
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#26734D]">
                    TaniKu / Administrasi
                </p>
                <h2 class="mt-1 text-xl font-bold text-[#19382B]">
                    Dashboard Admin
                </h2>
            </div>

            <span class="mt-2 inline-flex w-fit items-center gap-2 rounded-full border border-[#D6E5D9] bg-[#F0F7F1] px-3 py-1.5 text-xs font-semibold text-[#26734D] sm:mt-0">
                <span class="h-2 w-2 rounded-full bg-[#329765]"></span>
                Administrator
            </span>
        </div>
    </x-slot>

    <div class="min-h-screen bg-[#F4F6F3] px-4 py-5 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">

            {{-- Banner sambutan --}}
            <section class="relative overflow-hidden rounded-3xl bg-[#19382B] p-6 text-white shadow-sm sm:p-7">
                <div class="relative z-10 max-w-2xl">
                    <p class="text-sm font-semibold text-[#B8D9C3]">
                        PANEL ADMINISTRASI
                    </p>

                    <h1 class="mt-3 text-2xl font-bold leading-tight sm:text-3xl">
                        Selamat datang, {{ auth()->user()->name }}!
                    </h1>

                    <p class="mt-3 max-w-xl text-sm leading-6 text-[#D5E5D9] sm:text-base">
                        Kelola akun dan pantau perkembangan data aplikasi TaniKu
                        melalui satu halaman administrasi.
                    </p>

                    <a
                        href="{{ route('admin.users.index') }}"
                        class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#8FD5A8] px-5 py-3 text-sm font-bold text-[#19382B] transition hover:bg-[#B2E7C2] focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#19382B]"
                    >
                        Kelola Akun
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>

                <div
                    aria-hidden="true"
                    class="absolute -right-12 -top-16 h-64 w-64 rounded-full border-[35px] border-white/5"
                ></div>

                <div
                    aria-hidden="true"
                    class="absolute -bottom-24 right-24 h-48 w-48 rounded-full bg-[#26734D]/40 blur-2xl"
                ></div>
            </section>

            {{-- Ringkasan statistik --}}
            <section>
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-[#19382B]">
                            Ringkasan Data
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Gambaran keseluruhan data yang tersimpan di TaniKu.
                        </p>
                    </div>

                    <p class="mt-2 text-xs text-gray-500 sm:mt-0">
                        Data aktual dari database
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                    {{-- Total akun --}}
                    <article class="rounded-2xl border border-[#E5EAE5] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Total Akun
                                </p>
                                <p class="mt-4 text-3xl font-bold tracking-tight text-[#19382B]">
                                    {{ $totalUsers }}
                                </p>
                            </div>

                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#E6F4EA] text-[#26734D]">
                                <x-navigation-icon name="profile" class="h-6 w-6" />
                            </span>
                        </div>

                        <p class="mt-4 text-xs text-gray-500">
                            Seluruh akun terdaftar
                        </p>
                    </article>

                    {{-- Total lahan --}}
                    <article class="rounded-2xl border border-[#E5EAE5] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Total Lahan
                                </p>
                                <p class="mt-4 text-3xl font-bold tracking-tight text-[#19382B]">
                                    {{ $totalLands }}
                                </p>
                            </div>

                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#F8F0DE] text-[#997126]">
                                <x-navigation-icon name="land" class="h-6 w-6" />
                            </span>
                        </div>

                        <p class="mt-4 text-xs text-gray-500">
                            Data lahan tersimpan
                        </p>
                    </article>

                    {{-- Total tanaman --}}
                    <article class="rounded-2xl border border-[#E5EAE5] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Total Tanaman
                                </p>
                                <p class="mt-4 text-3xl font-bold tracking-tight text-[#19382B]">
                                    {{ $totalCrops }}
                                </p>
                            </div>

                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#E9F1E1] text-[#52783B]">
                                <x-navigation-icon name="crop" class="h-6 w-6" />
                            </span>
                        </div>

                        <p class="mt-4 text-xs text-gray-500">
                            Data tanaman tersimpan
                        </p>
                    </article>

                    {{-- Total produk --}}
                    <article class="rounded-2xl border border-[#E5EAE5] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Total Produk
                                </p>
                                <p class="mt-4 text-3xl font-bold tracking-tight text-[#19382B]">
                                    {{ $totalProducts }}
                                </p>
                            </div>

                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#E8EDF9] text-[#526CA6]">
                                <x-navigation-icon name="product" class="h-6 w-6" />
                            </span>
                        </div>

                        <p class="mt-4 text-xs text-gray-500">
                            Data produk tersimpan
                        </p>
                    </article>

                </div>
            </section>

            {{-- Pengelolaan akun --}}
            <section class="rounded-2xl border border-[#E5EAE5] bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#E6F4EA] text-[#26734D]">
                            <x-navigation-icon name="profile" class="h-6 w-6" />
                        </span>

                        <div>
                            <h2 class="text-lg font-bold text-[#19382B]">
                                Pengelolaan Akun
                            </h2>

                            <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                                Lihat daftar pengguna yang terdaftar dan kelola
                                akun melalui halaman administrasi.
                            </p>
                        </div>
                    </div>

                    <a
                        href="{{ route('admin.users.index') }}"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-[#26734D] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1D5B3C] focus:outline-none focus:ring-2 focus:ring-[#26734D] focus:ring-offset-2"
                    >
                        Buka Pengelolaan
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </section>

            {{-- Catatan keamanan --}}
            <p class="px-1 text-xs leading-5 text-gray-500">
                Halaman ini ditujukan untuk administrator. Akses pengelolaan
                tetap dilindungi oleh middleware admin.
            </p>

        </div>
    </div>
</x-app-layout>
