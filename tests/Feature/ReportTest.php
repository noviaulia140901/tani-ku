<?php

use App\Models\User;

it('calculates financial totals and harvest summary for the selected dates and signed-in user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Saya',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tomat',
        'planting_date' => '2026-09-01',
        'status' => 'siap_panen',
    ]);
    $harvest = $crop->harvests()->create([
        'harvest_date' => '2026-10-10',
        'quantity' => '2.50',
        'unit' => 'kg',
    ]);
    $secondHarvest = $crop->harvests()->create([
        'harvest_date' => '2026-10-12',
        'quantity' => '6.00',
        'unit' => 'kg',
    ]);
    $crop->harvests()->create([
        'harvest_date' => '2026-10-13',
        'quantity' => '40.00',
        'unit' => 'kg',
    ]);
    $harvest->sales()->createMany([
        [
            'buyer_name' => 'Pasar A',
            'sale_date' => '2026-10-10',
            'quantity' => '1.00',
            'unit' => 'kg',
            'price_per_unit' => '10000.00',
            'total' => '10000.00',
            'payment_status' => 'lunas',
        ],
        [
            'buyer_name' => 'Pasar B',
            'sale_date' => '2026-10-10',
            'quantity' => '2.00',
            'unit' => 'kg',
            'price_per_unit' => '12500.00',
            'total' => '25000.00',
            'payment_status' => 'belum_lunas',
        ],
    ]);
    $secondHarvest->sales()->create([
        'buyer_name' => 'Pasar C',
        'sale_date' => '2026-10-12',
        'quantity' => '1.00',
        'unit' => 'kg',
        'price_per_unit' => '15000.00',
        'total' => '15000.00',
        'payment_status' => 'lunas',
    ]);
    $harvest->sales()->create([
        'buyer_name' => 'Pasar Sebelumnya',
        'sale_date' => '2026-10-09',
        'quantity' => '1.00',
        'unit' => 'kg',
        'price_per_unit' => '5000.00',
        'total' => '5000.00',
        'payment_status' => 'lunas',
    ]);
    $user->expenses()->createMany([
        [
            'category' => 'Perawatan',
            'description' => 'Biaya sebelum periode',
            'amount' => '500.00',
            'expense_date' => '2026-10-09',
        ],
        [
            'category' => 'Pupuk',
            'description' => 'Biaya dalam periode',
            'amount' => '1200.50',
            'expense_date' => '2026-10-11',
        ],
        [
            'category' => 'Peralatan',
            'description' => 'Biaya di luar periode',
            'amount' => '3000.00',
            'expense_date' => '2026-10-13',
        ],
    ]);

    $otherLand = $otherUser->lands()->create([
        'name' => 'Kebun Pengguna Lain',
        'location' => 'Desa Sejahtera',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Cabai',
        'planting_date' => '2026-09-01',
        'status' => 'siap_panen',
    ]);
    $otherHarvest = $otherCrop->harvests()->create([
        'harvest_date' => '2026-10-11',
        'quantity' => '900.00',
        'unit' => 'kg',
    ]);
    $otherHarvest->sales()->create([
        'buyer_name' => 'Pembeli Lain',
        'sale_date' => '2026-10-11',
        'quantity' => '1.00',
        'unit' => 'kg',
        'price_per_unit' => '900000.00',
        'total' => '900000.00',
        'payment_status' => 'lunas',
    ]);
    $otherUser->expenses()->create([
        'category' => 'Lainnya',
        'description' => 'Pengeluaran pengguna lain',
        'amount' => '700000.00',
        'expense_date' => '2026-10-11',
    ]);

    $this->actingAs($user)
        ->get(route('reports.index', ['from' => '2026-10-10', 'to' => '2026-10-12']))
        ->assertSee('Rp 50.000,00')
        ->assertSee('Rp 1.200,50')
        ->assertSee('Rp 48.799,50')
        ->assertSee('2 catatan panen')
        ->assertSee('8,50 kg')
        ->assertDontSee('900.000,00')
        ->assertDontSee('700.000,00')
        ->assertViewHas('financialChart', fn (array $chart): bool => $chart['income'] === [50000.0]
            && $chart['expenses'] === [1200.5])
        ->assertViewHas('harvestSummary', fn ($summary): bool => $summary->count() === 1
            && (float) $summary->first()->quantity === 8.5);
});

it('rejects a report range where the end date is before the start date', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('reports.index', ['from' => '2026-10-12', 'to' => '2026-10-10']))
        ->assertSessionHasErrors([
            'to' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);
});

it('requires authentication to view reports', function () {
    $this->get(route('reports.index'))
        ->assertRedirect(route('login'));
});

it('shows empty states when the selected period has no financial or harvest records', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
        ->assertSee('Belum ada transaksi pada periode ini.')
        ->assertSee('Belum ada panen pada periode ini.')
        ->assertDontSee('data-report-chart');
});

it('exports valid PDF and Excel reports using the selected period and only the signed-in users data', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Ekspor',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tomat',
        'planting_date' => '2026-10-01',
        'status' => 'siap_panen',
    ]);
    $harvest = $crop->harvests()->create([
        'harvest_date' => '2026-10-10',
        'quantity' => '12.50',
        'unit' => 'kg',
    ]);
    $harvest->sales()->createMany([
        [
            'buyer_name' => 'Pembeli Dalam Periode',
            'sale_date' => '2026-10-10',
            'quantity' => '2.00',
            'unit' => 'kg',
            'price_per_unit' => '10000.00',
            'total' => '20000.00',
            'payment_status' => 'lunas',
        ],
        [
            'buyer_name' => 'Pembeli Di Luar Periode',
            'sale_date' => '2026-11-01',
            'quantity' => '1.00',
            'unit' => 'kg',
            'price_per_unit' => '5000.00',
            'total' => '5000.00',
            'payment_status' => 'lunas',
        ],
    ]);
    $user->expenses()->createMany([
        [
            'category' => 'Pupuk',
            'description' => 'Biaya dalam periode',
            'amount' => '2500.50',
            'expense_date' => '2026-10-11',
        ],
        [
            'category' => 'Lainnya',
            'description' => 'Biaya di luar periode',
            'amount' => '4000.00',
            'expense_date' => '2026-11-01',
        ],
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Kebun Rahasia',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Cabai Rahasia',
        'planting_date' => '2026-10-01',
        'status' => 'siap_panen',
    ]);
    $otherHarvest = $otherCrop->harvests()->create([
        'harvest_date' => '2026-10-10',
        'quantity' => '999.00',
        'unit' => 'kg',
    ]);
    $otherHarvest->sales()->create([
        'buyer_name' => 'Pembeli Rahasia',
        'sale_date' => '2026-10-10',
        'quantity' => '1.00',
        'unit' => 'kg',
        'price_per_unit' => '900000.00',
        'total' => '900000.00',
        'payment_status' => 'lunas',
    ]);
    $otherUser->expenses()->create([
        'category' => 'Lainnya',
        'description' => 'Biaya Rahasia',
        'amount' => '800000.00',
        'expense_date' => '2026-10-10',
    ]);
    $period = ['from' => '2026-10-01', 'to' => '2026-10-31'];

    $this->actingAs($user)
        ->get(route('reports.index', $period))
        ->assertSee(route('reports.export', ['format' => 'pdf', ...$period]))
        ->assertSee(route('reports.export', ['format' => 'xls', ...$period]));

    $pdf = $this->get(route('reports.export', ['format' => 'pdf', ...$period]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename="laporan-taniku-2026-10-01-2026-10-31.pdf"')
        ->getContent();

    expect($pdf)
        ->toStartWith('%PDF-1.4')
        ->toContain('Laporan Terpadu TaniKu')
        ->toContain('Rp 20.000,00')
        ->toContain('Rp 2.500,50')
        ->toContain('Rp 17.499,50')
        ->toContain('12,50')
        ->not->toContain('900.000,00')
        ->not->toContain('800.000,00')
        ->not->toContain('5.000,00')
        ->not->toContain('4.000,00');
    preg_match('/startxref\n(\d+)\n%%EOF$/', $pdf, $xrefMatch);
    expect($xrefMatch)->toHaveCount(2);
    expect(substr($pdf, (int) $xrefMatch[1], 4))->toBe('xref');

    $excel = $this->get(route('reports.export', ['format' => 'xls', ...$period]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="laporan-taniku-2026-10-01-2026-10-31.xls"')
        ->getContent();

    expect($excel)
        ->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('<ss:Workbook')
        ->toContain('Pemasukan penjualan')
        ->toContain('20000')
        ->toContain('2500.5')
        ->toContain('12.5')
        ->not->toContain('900000')
        ->not->toContain('800000')
        ->not->toContain('5000')
        ->not->toContain('4000');
    $workbook = new DOMDocument;
    expect($workbook->loadXML($excel, LIBXML_NONET))->toBeTrue()
        ->and($workbook->documentElement->localName)->toBe('Workbook');
});

it('exports valid empty reports without requiring transactions or harvests', function () {
    $user = User::factory()->create();
    $period = ['from' => '2026-10-01', 'to' => '2026-10-31'];

    $this->actingAs($user);
    $pdf = $this->get(route('reports.export', ['format' => 'pdf', ...$period]))
        ->assertOk()
        ->getContent();
    $excel = $this->get(route('reports.export', ['format' => 'xls', ...$period]))
        ->assertOk()
        ->getContent();

    expect($pdf)
        ->toStartWith('%PDF-1.4')
        ->toContain('Rp 0,00')
        ->toContain('Belum ada catatan panen');
    expect($excel)
        ->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('Belum ada catatan panen pada periode ini.');
    $workbook = new DOMDocument;
    expect($workbook->loadXML($excel, LIBXML_NONET))->toBeTrue();
});

it('validates report date filters for both export formats', function (string $format) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('reports.index'))
        ->get(route('reports.export', [
            'format' => $format,
            'from' => '2026-10-15',
            'to' => '2026-10-01',
        ]))
        ->assertRedirect(route('reports.index'))
        ->assertSessionHasErrors([
            'to' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);
})->with(['pdf', 'xls']);

it('requires authentication to export reports', function (string $format) {
    $this->get(route('reports.export', [
        'format' => $format,
        'from' => '2026-10-01',
        'to' => '2026-10-31',
    ]))
        ->assertRedirect(route('login'));
})->with(['pdf', 'xls']);
