
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#26734D]">
                    TaniKu / Administrasi
                </p>
                <h2 class="mt-1 text-xl font-bold text-[#19382B]">
                    Tambah Akun
                </h2>
            </div>

            <span class="mt-2 inline-flex w-fit items-center gap-2 rounded-full border border-[#D6E5D9] bg-[#F0F7F1] px-3 py-1.5 text-xs font-semibold text-[#26734D] sm:mt-0">
                <span class="h-2 w-2 rounded-full bg-[#329765]"></span>
                Panel Administrator
            </span>
        </div>
    </x-slot>

    <div class="min-h-screen bg-[#F4F6F3] px-4 py-5 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl space-y-6">

            {{-- Judul halaman --}}
            <section>
                <p class="text-sm font-semibold text-[#26734D]">
                    MANAJEMEN PENGGUNA
                </p>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#19382B] sm:text-3xl">
                    Buat Akun Baru
                </h1>

                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Tambahkan akun untuk orang yang akan menggunakan aplikasi TaniKu.
                </p>
            </section>

            {{-- Form tambah akun --}}
            <section class="overflow-hidden rounded-2xl border border-[#E5EAE5] bg-white shadow-sm">

                <div class="border-b border-[#E5EAE5] bg-[#FAFBF9] px-5 py-4 sm:px-7">
                    <h2 class="font-bold text-[#19382B]">
                        Informasi Akun
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Lengkapi semua kolom yang diperlukan.
                    </p>
                </div>

                <div class="p-5 sm:p-7">

                    {{-- Pesan validasi --}}
                    @if ($errors->any())
                        <div
                            role="alert"
                            class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"
                        >
                            <p class="font-semibold">
                                Ada data yang perlu diperbaiki:
                            </p>

                            <ul class="mt-2 list-inside list-disc space-y-1 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('admin.users.store') }}"
                        class="space-y-5"
                    >
                        @csrf

                        {{-- Nama lengkap --}}
                        <div>
                            <label
                                for="name"
                                class="mb-2 block text-sm font-semibold text-[#19382B]"
                            >
                                Nama Lengkap
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                required
                                maxlength="255"
                                autocomplete="name"
                                autofocus
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-[#26734D] focus:ring-2 focus:ring-[#26734D]/20"
                                placeholder="Masukkan nama lengkap"
                                aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                            >

                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label
                                for="email"
                                class="mb-2 block text-sm font-semibold text-[#19382B]"
                            >
                                Alamat Email
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                maxlength="255"
                                autocomplete="email"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-[#26734D] focus:ring-2 focus:ring-[#26734D]/20"
                                placeholder="contoh@email.com"
                                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                            >

                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div>
                            <label
                                for="password"
                                class="mb-2 block text-sm font-semibold text-[#19382B]"
                            >
                                Password
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-[#26734D] focus:ring-2 focus:ring-[#26734D]/20"
                                placeholder="Minimal 8 karakter"
                                aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                            >

                            @error('password')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Konfirmasi password --}}
                        <div>
                            <label
                                for="password_confirmation"
                                class="mb-2 block text-sm font-semibold text-[#19382B]"
                            >
                                Konfirmasi Password
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-[#26734D] focus:ring-2 focus:ring-[#26734D]/20"
                                placeholder="Ulangi password"
                            >
                        </div>

                        {{-- Role --}}
                        <div>
                            <label
                                for="role"
                                class="mb-2 block text-sm font-semibold text-[#19382B]"
                            >
                                Peran Akun
                                <span class="text-red-600">*</span>
                            </label>

                            <select
                                id="role"
                                name="role"
                                required
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 focus:border-[#26734D] focus:ring-2 focus:ring-[#26734D]/20"
                            >
                                <option
                                    value="pengguna"
                                    @selected(old('role', 'pengguna') === 'pengguna')
                                >
                                    Pengguna — Mengelola kebun
                                </option>

                                <option
                                    value="admin"
                                    @selected(old('role') === 'admin')
                                >
                                    Admin — Mengelola akun dan administrasi
                                </option>
                            </select>

                            <p class="mt-2 text-xs leading-5 text-gray-500">
                                Berikan peran Admin hanya kepada orang yang dipercaya
                                karena peran ini memiliki akses administrasi.
                            </p>

                            @error('role')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tombol --}}
                        <div class="flex flex-col-reverse gap-3 border-t border-[#E5EAE5] pt-5 sm:flex-row sm:justify-end">
                            <a
                                href="{{ route('admin.users.index') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                            >
                                Batal
                            </a>

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#26734D] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#1D5B3C] focus:outline-none focus:ring-2 focus:ring-[#26734D] focus:ring-offset-2"
                            >
                                Simpan Akun
                                <span aria-hidden="true">&rarr;</span>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- Kembali --}}
            <div>
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-[#26734D] transition hover:text-[#19382B] focus:outline-none focus:ring-2 focus:ring-[#26734D] focus:ring-offset-2"
                >
                    <span aria-hidden="true">&larr;</span>
                    Kembali ke Dashboard Admin
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
