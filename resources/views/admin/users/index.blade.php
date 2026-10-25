
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#26734D]">
                    TaniKu / Administrasi
                </p>
                <h2 class="mt-1 text-xl font-bold text-[#19382B]">
                    Pengelolaan Akun
                </h2>
            </div>

            <span class="mt-2 inline-flex w-fit items-center gap-2 rounded-full border border-[#D6E5D9] bg-[#F0F7F1] px-3 py-1.5 text-xs font-semibold text-[#26734D] sm:mt-0">
                <span class="h-2 w-2 rounded-full bg-[#329765]"></span>
                Panel Administrator
            </span>
        </div>
    </x-slot>

    <div class="min-h-screen bg-[#F4F6F3] px-4 py-5 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">

            {{-- Pesan sukses --}}
            @if (session('success'))
                <div
                    role="status"
                    class="flex items-start gap-3 rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800"
                >
                    <span class="mt-0.5 font-bold">&#10003;</span>
                    <p class="text-sm font-medium">
                        {{ session('success') }}
                    </p>
                </div>
            @endif

            {{-- Pesan error --}}
            @if (session('error'))
                <div
                    role="alert"
                    class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            {{-- Judul halaman dan tombol tambah --}}
            <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-[#26734D]">
                        MANAJEMEN PENGGUNA
                    </p>

                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#19382B] sm:text-3xl">
                        Daftar Akun
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                        Lihat dan kelola akun yang memiliki akses ke aplikasi TaniKu.
                    </p>
                </div>

                <a
                    href="{{ route('admin.users.create') }}"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-[#26734D] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#1D5B3C] focus:outline-none focus:ring-2 focus:ring-[#26734D] focus:ring-offset-2"
                >
                    <span class="text-lg leading-none" aria-hidden="true">+</span>
                    Tambah Akun
                </a>
            </section>

            {{-- Ringkasan jumlah akun pada halaman --}}
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <article class="flex items-center gap-4 rounded-2xl border border-[#E5EAE5] bg-white p-5 shadow-sm">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#E6F4EA] text-[#26734D]">
                        <x-navigation-icon name="profile" class="h-6 w-6" />
                    </span>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Akun pada halaman ini
                        </p>
                        <p class="mt-1 text-2xl font-bold text-[#19382B]">
                            {{ $users->count() }}
                        </p>
                    </div>
                </article>

                <article class="flex items-center gap-4 rounded-2xl border border-[#E5EAE5] bg-white p-5 shadow-sm">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#E9F1E1] text-[#52783B]">
                        <x-navigation-icon name="profile" class="h-6 w-6" />
                    </span>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Total seluruh akun
                        </p>
                        <p class="mt-1 text-2xl font-bold text-[#19382B]">
                            {{ $users->total() }}
                        </p>
                    </div>
                </article>
            </section>

            {{-- Tabel akun --}}
            <section class="overflow-hidden rounded-2xl border border-[#E5EAE5] bg-white shadow-sm">
                <div class="flex flex-col gap-1 border-b border-[#E5EAE5] px-5 py-4 sm:px-6">
                    <h2 class="font-bold text-[#19382B]">
                        Data Akun Terdaftar
                    </h2>
                    <p class="text-sm text-gray-500">
                        Informasi akun yang tersimpan dalam database TaniKu.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#E8ECE8]">
                        <thead class="bg-[#F7F9F6]">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 sm:px-6">
                                    No.
                                </th>

                                <th scope="col" class="whitespace-nowrap px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 sm:px-6">
                                    Nama Pengguna
                                </th>

                                <th scope="col" class="whitespace-nowrap px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 sm:px-6">
                                    Email
                                </th>

                                <th scope="col" class="whitespace-nowrap px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 sm:px-6">
                                    Peran
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[#E8ECE8] bg-white">
                            @forelse ($users as $user)
                                <tr class="transition-colors hover:bg-[#F8FAF7]">
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500 sm:px-6">
                                        {{ $users->firstItem() + $loop->index }}
                                    </td>

                                    <td class="px-5 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#EAF2E8] text-sm font-bold text-[#26734D]">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </span>

                                            <span class="min-w-0">
                                                <span class="block whitespace-nowrap text-sm font-semibold text-[#19382B]">
                                                    {{ $user->name }}
                                                </span>
                                                <span class="mt-1 block text-xs text-gray-500">
                                                    ID akun: {{ $user->id }}
                                                </span>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 sm:px-6">
                                        {{ $user->email }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                        @if ($user->role === 'admin')
                                            <span class="inline-flex items-center gap-2 rounded-full border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-purple-500"></span>
                                                Admin
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                                Pengguna
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-14 text-center sm:px-6">
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#EAF2E8] text-[#26734D]">
                                            <x-navigation-icon name="profile" class="h-7 w-7" />
                                        </div>

                                        <p class="mt-4 font-semibold text-[#19382B]">
                                            Belum ada akun
                                        </p>

                                        <p class="mt-1 text-sm text-gray-500">
                                            Belum ada data akun yang dapat ditampilkan.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($users->hasPages())
                    <div class="flex flex-col gap-3 border-t border-[#E5EAE5] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <p class="text-sm text-gray-500">
                            Menampilkan {{ $users->firstItem() }}
                            sampai {{ $users->lastItem() }}
                            dari {{ $users->total() }} akun
                        </p>

                        <div>
                            {{ $users->links() }}
                        </div>
                    </div>
                @endif
            </section>

            {{-- Kembali ke dashboard --}}
            <div>
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center gap-2 rounded-lg text-sm font-semibold text-[#26734D] transition hover:text-[#19382B] focus:outline-none focus:ring-2 focus:ring-[#26734D] focus:ring-offset-2"
                >
                    <span aria-hidden="true">&larr;</span>
                    Kembali ke Dashboard Admin
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
