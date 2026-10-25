<?php

use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00:00', 'Asia/Jakarta'));
});

it('shows the signed-in users dashboard summaries without other users data', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $land = $user->lands()->create([
        'name' => 'Kebun Utama',
        'location' => 'Desa Makmur',
        'area' => 2.5,
        'status' => 'aktif',
    ]);
    $user->lands()->create([
        'name' => 'Lahan Istirahat',
        'location' => 'Desa Makmur',
        'area' => 1.0,
        'status' => 'tidak_aktif',
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Kebun Tetangga',
        'location' => 'Desa Sebelah',
        'area' => 3.0,
        'status' => 'aktif',
    ]);

    $crop = $land->crops()->create([
        'name' => 'Jagung Manis',
        'planting_date' => today()->subDays(40),
        'estimated_harvest_date' => today()->addDays(12),
        'status' => 'perawatan',
    ]);
    $land->crops()->create([
        'name' => 'Cabai Merah',
        'planting_date' => today()->subDays(10),
        'estimated_harvest_date' => today()->addDays(45),
        'status' => 'ditanam',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Padi Tetangga',
        'planting_date' => today()->subDays(40),
        'estimated_harvest_date' => today()->addDays(5),
        'status' => 'siap_panen',
    ]);
    foreach (range(1, 5) as $cropNumber) {
        $land->crops()->create([
            'name' => 'Panen Tambahan '.$cropNumber,
            'planting_date' => today()->subDays(30),
            'estimated_harvest_date' => today()->addDays(20),
            'status' => 'siap_panen',
        ]);
    }

    $crop->activities()->create([
        'activity_type' => 'pemupukan',
        'scheduled_date' => today()->addDay(),
        'scheduled_time' => '08:30',
        'status' => 'belum_dilakukan',
    ]);
    $otherCrop->activities()->create([
        'activity_type' => 'penyiraman',
        'scheduled_date' => today()->addDay(),
        'status' => 'belum_dilakukan',
    ]);

    $user->products()->create([
        'name' => 'Pupuk Kompos',
        'category' => 'pupuk',
        'unit' => 'kg',
        'stock' => 2,
        'minimum_stock' => 5,
    ]);
    $user->products()->create([
        'name' => 'Pupuk Batas',
        'category' => 'pupuk',
        'unit' => 'kg',
        'stock' => 3,
        'minimum_stock' => 3,
    ]);
    $otherUser->products()->create([
        'name' => 'Stok Tetangga',
        'category' => 'pupuk',
        'unit' => 'kg',
        'stock' => 0,
        'minimum_stock' => 10,
    ]);

    $user->expenses()->create([
        'category' => 'bibit',
        'description' => 'Pembelian benih',
        'amount' => 50000,
        'expense_date' => today(),
    ]);
    $otherUser->expenses()->create([
        'category' => 'lainnya',
        'description' => 'Biaya milik tetangga',
        'amount' => 900000,
        'expense_date' => today(),
    ]);

    $harvest = $crop->harvests()->create([
        'harvest_date' => today(),
        'quantity' => 30,
        'unit' => 'kg',
    ]);
    $harvest->sales()->create([
        'buyer_name' => 'Pasar Desa',
        'sale_date' => today(),
        'quantity' => 30,
        'unit' => 'kg',
        'price_per_unit' => 5000,
        'total' => 150000,
    ]);
    $otherHarvest = $otherCrop->harvests()->create([
        'harvest_date' => today(),
        'quantity' => 50,
        'unit' => 'kg',
    ]);
    $otherHarvest->sales()->create([
        'buyer_name' => 'Pembeli Tetangga',
        'sale_date' => today(),
        'quantity' => 50,
        'unit' => 'kg',
        'price_per_unit' => 10000,
        'total' => 500000,
    ]);
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertViewHas('dashboardDate', fn (CarbonImmutable $date): bool => $date->toDateString() === '2026-10-15')
        ->assertViewHas('chartPeriodLabel', 'Mei 2026 – Oktober 2026')
        ->assertViewHas('activeLandCount', 1)
        ->assertViewHas('plantedCropCount', 2)
        ->assertViewHas('upcomingHarvestCount', 6)
        ->assertViewHas('upcomingActivityCount', 1)
        ->assertViewHas('lowStockProductCount', 2)
        ->assertViewHas('financialChart', function (array $chart): bool {
            expect($chart['income'])->toBe([0.0, 0.0, 0.0, 0.0, 0.0, 150000.0]);
            expect($chart['expenses'])->toBe([0.0, 0.0, 0.0, 0.0, 0.0, 50000.0]);

            return true;
        })
        ->assertViewHas('harvestChart', fn (array $chart): bool => $chart['datasets'][0]['label'] === 'kg'
            && $chart['datasets'][0]['data'][5] === 30.0)
        ->assertSee('Dari seluruh penjualan Anda')
        ->assertSee('Rp 150.000')
        ->assertSee('Rp 50.000')
        ->assertSee('Rp 100.000')
        ->assertSee('Kebun Utama')
        ->assertSee('Jagung Manis')
        ->assertSee('Pupuk Kompos')
        ->assertSee('Pupuk Batas')
        ->assertSee('Pemupukan')
        ->assertSee('Lahan: Kebun Utama')
        ->assertSee('Status: Belum dilakukan')
        ->assertSee('08:30')
        ->assertSee('12 hari lagi')
        ->assertSee('Pasar Desa')
        ->assertSee('Pembelian benih')
        ->assertDontSee('Padi Tetangga')
        ->assertDontSee('Penyiraman')
        ->assertDontSee('Stok Tetangga')
        ->assertDontSee('Pembeli Tetangga')
        ->assertDontSee('Biaya milik tetangga');
});

it('shows empty dashboard messages when the user has no records', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Rp 0')
        ->assertSee('Impas')
        ->assertSee('Belum ada penjualan')
        ->assertSee('Belum ada pengeluaran')
        ->assertSee('Belum ada lahan aktif')
        ->assertSee('Belum ada tanaman yang sedang ditanam')
        ->assertSee('Belum ada tanaman yang akan panen dalam 30 hari')
        ->assertSee('Tidak ada aktivitas yang dijadwalkan hari ini.')
        ->assertSee('Belum ada aktivitas mendatang.')
        ->assertSee('Tidak ada aktivitas yang terlambat.')
        ->assertSee('Tidak ada peringatan stok. Semua produk masih di atas batas minimum.')
        ->assertSee('Belum ada perkiraan panen dalam 30 hari.')
        ->assertSee('Belum ada transaksi pada periode ini.')
        ->assertSee('Belum ada hasil panen pada periode ini.')
        ->assertSee('data-farm-map', false)
        ->assertSee('class="h-72 w-full sm:h-80"', false)
        ->assertSee('data-map-satellite', false)
        ->assertSee('Lihat Satelit di Google Maps')
        ->assertSee('Tampilan awal peta Indonesia bukan lokasi lahan atau cuaca.')
        ->assertSee('Tambah lahan');
});

it('renders the base map and empty state when lands have no saved coordinates', function () {
    $user = User::factory()->create();
    $user->lands()->create([
        'name' => 'Kebun Tanpa Titik',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('data-farm-map', false)
        ->assertSee('class="h-72 w-full sm:h-80"', false)
        ->assertSee('data-map-satellite', false)
        ->assertSee('Lihat Satelit di Google Maps')
        ->assertSee('Belum ada titik koordinat. Tambahkan atau perbarui titik pada halaman Lahan.');
});

it('shows active reminders and stock warnings only for the signed-in user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tomat Sendiri',
        'planting_date' => '2026-10-01',
        'status' => 'perawatan',
    ]);
    $crop->activities()->createMany([
        [
            'activity_type' => 'pemupukan_hari_ini',
            'scheduled_date' => '2026-10-15',
            'status' => 'belum_dilakukan',
        ],
        [
            'activity_type' => 'penyiraman_mendatang',
            'scheduled_date' => '2026-10-16',
            'status' => 'belum_dilakukan',
        ],
        [
            'activity_type' => 'pemangkasan_terlambat',
            'scheduled_date' => '2026-10-14',
            'status' => 'belum_dilakukan',
        ],
        [
            'activity_type' => 'aktivitas_selesai_hari_ini',
            'scheduled_date' => '2026-10-15',
            'status' => 'selesai',
        ],
        [
            'activity_type' => 'aktivitas_selesai_mendatang',
            'scheduled_date' => '2026-10-16',
            'status' => 'selesai',
        ],
        [
            'activity_type' => 'aktivitas_selesai_terlambat',
            'scheduled_date' => '2026-10-14',
            'status' => 'selesai',
        ],
    ]);
    $user->products()->createMany([
        [
            'name' => 'Pupuk Habis',
            'category' => 'pupuk',
            'unit' => 'kg',
            'stock' => '0.00',
            'minimum_stock' => '2.00',
        ],
        [
            'name' => 'Benih Menipis',
            'category' => 'pupuk',
            'unit' => 'pak',
            'stock' => '1.00',
            'minimum_stock' => '3.00',
        ],
        [
            'name' => 'Pestisida Cukup',
            'category' => 'pestisida',
            'unit' => 'liter',
            'stock' => '5.00',
            'minimum_stock' => '3.00',
        ],
    ]);

    $otherLand = $otherUser->lands()->create([
        'name' => 'Kebun Tetangga',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Cabai Tetangga',
        'planting_date' => '2026-10-01',
        'status' => 'perawatan',
    ]);
    $otherCrop->activities()->create([
        'activity_type' => 'aktivitas_rahasia',
        'scheduled_date' => '2026-10-15',
        'status' => 'belum_dilakukan',
    ]);
    $otherUser->products()->create([
        'name' => 'Produk Tetangga',
        'category' => 'pupuk',
        'unit' => 'kg',
        'stock' => '0.00',
        'minimum_stock' => '10.00',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertViewHas('todayActivitiesCount', 1)
        ->assertViewHas('futureActivitiesCount', 1)
        ->assertViewHas('overdueActivitiesCount', 1)
        ->assertViewHas('lowStockProductCount', 2)
        ->assertSee('Pemupukan hari ini')
        ->assertSee('Penyiraman mendatang')
        ->assertSee('Pemangkasan terlambat')
        ->assertSee('Tomat Sendiri')
        ->assertSee('Kebun Sendiri')
        ->assertSee('Stok habis')
        ->assertSee('Stok menipis')
        ->assertSee(route('activities.edit', $crop->activities()->where('activity_type', 'pemupukan_hari_ini')->firstOrFail()))
        ->assertSee(route('products.index'))
        ->assertDontSee('aktivitas selesai')
        ->assertDontSee('aktivitas_rahasia')
        ->assertDontSee('Cabai Tetangga')
        ->assertDontSee('Produk Tetangga')
        ->assertDontSee('Pestisida Cukup');
});

it('uses the application timezone to determine which activities are due today', function () {
    config(['app.timezone' => 'Pacific/Kiritimati']);
    $this->travelTo(CarbonImmutable::parse('2026-10-15 12:30:00', 'UTC'));

    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Zona Waktu',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Jagung',
        'planting_date' => '2026-10-01',
        'status' => 'perawatan',
    ]);
    $crop->activities()->createMany([
        [
            'activity_type' => 'hari_ini',
            'scheduled_date' => '2026-10-16',
            'status' => 'belum_dilakukan',
        ],
        [
            'activity_type' => 'hari_berikutnya',
            'scheduled_date' => '2026-10-17',
            'status' => 'belum_dilakukan',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertViewHas('todayActivitiesCount', 1)
        ->assertViewHas('futureActivitiesCount', 1)
        ->assertViewHas('todayActivities', fn ($activities): bool => $activities->first()->activity_type === 'hari_ini');
});

it('shows a loss when the user expenses exceed sales', function () {
    $user = User::factory()->create();
    $user->expenses()->create([
        'category' => 'lainnya',
        'description' => 'Biaya melebihi pemasukan',
        'amount' => 25000,
        'expense_date' => today(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertViewHas('profit', fn ($profit) => (float) $profit === -25000.0)
        ->assertSee('Kerugian')
        ->assertSee('Rp -25.000');
});

it('includes crops harvesting in thirty days but excludes completed crops', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Panen',
        'location' => 'Desa Makmur',
        'area' => 1.0,
        'status' => 'aktif',
    ]);

    $land->crops()->create([
        'name' => 'Panen Batas',
        'planting_date' => today()->subDays(60),
        'estimated_harvest_date' => today()->addDays(30),
        'status' => 'siap_panen',
    ]);
    $land->crops()->create([
        'name' => 'Panen Selesai',
        'planting_date' => today()->subDays(70),
        'estimated_harvest_date' => today()->addDays(5),
        'status' => 'selesai',
    ]);
    $land->crops()->create([
        'name' => 'Panen Nanti',
        'planting_date' => today()->subDays(30),
        'estimated_harvest_date' => today()->addDays(31),
        'status' => 'ditanam',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertViewHas('dashboardDate', fn (CarbonImmutable $date): bool => $date->toDateString() === '2026-10-15')
        ->assertViewHas('upcomingHarvestCount', 1)
        ->assertSee('Panen Batas')
        ->assertSee('30 hari lagi')
        ->assertDontSee('Panen Selesai')
        ->assertDontSee('Panen Nanti');
});

it('redirects guests to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
