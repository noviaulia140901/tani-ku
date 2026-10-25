<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Admin TaniKu
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-6">
                <h3 class="text-2xl font-bold text-gray-800">
                    Selamat datang, Admin!
                </h3>
                <p class="mt-2 text-gray-600">
                    Pantau akun dan data keseluruhan aplikasi TaniKu dari sini.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500">Total Akun</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">
                        {{ $totalUsers }}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500">Total Lahan</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">
                        {{ $totalLands }}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500">Total Tanaman</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">
                        {{ $totalCrops }}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500">Total Produk</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">
                        {{ $totalProducts }}
                    </p>
                </div>

            </div>

            <div class="mt-8 bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800">
                    Panel Administrasi
                </h3>
                <p class="mt-2 text-gray-600">
                    Halaman ini khusus untuk akun dengan peran admin.
                    Fitur pengelolaan akun akan kita tambahkan pada tahap berikutnya.
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
