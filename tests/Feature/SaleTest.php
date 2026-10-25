<?php

use App\Models\Harvest;
use App\Models\Sale;
use App\Models\User;

function createSaleTestHarvest(User $user, string $quantity = '100.00', string $unit = 'kg', string $cropName = 'Tomat'): Harvest
{
    $land = $user->lands()->create([
        'name' => 'Kebun '.$cropName,
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => $cropName,
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);

    return $crop->harvests()->create([
        'harvest_date' => '2026-10-01',
        'quantity' => $quantity,
        'unit' => $unit,
    ]);
}

function createSaleTestSale(Harvest $harvest, array $attributes = []): Sale
{
    return $harvest->sales()->create(array_replace([
        'buyer_name' => 'Pasar Desa',
        'sale_date' => '2026-10-02',
        'quantity' => '5.00',
        'unit' => $harvest->unit,
        'price_per_unit' => '10000.00',
        'total' => '50000.00',
        'payment_status' => 'belum_lunas',
    ], $attributes));
}

it('lists only the signed in users sales with harvest details', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $harvest = createSaleTestHarvest($user, '50.00', 'kg', 'Tomat Lokal');
    $otherHarvest = createSaleTestHarvest($otherUser, '50.00', 'kg', 'Tanaman Rahasia');
    createSaleTestSale($harvest, [
        'buyer_name' => 'Koperasi Makmur',
        'sale_date' => '2026-10-03',
        'quantity' => '12.50',
        'price_per_unit' => '8000.00',
        'total' => '100000.00',
        'payment_status' => 'lunas',
        'notes' => 'Pembayaran diterima.',
    ]);
    createSaleTestSale($otherHarvest, [
        'buyer_name' => 'Pembeli Rahasia',
        'total' => '50000.00',
    ]);

    $this->actingAs($user)
        ->get(route('sales.index'))
        ->assertSee('Koperasi Makmur')
        ->assertSee('Tomat Lokal')
        ->assertSee('Kebun Tomat Lokal')
        ->assertSee('03/10/2026')
        ->assertSee('12,50 kg')
        ->assertSee('Rp 100.000,00')
        ->assertSee('Lunas')
        ->assertSee('Pembayaran diterima.')
        ->assertDontSee('Pembeli Rahasia')
        ->assertDontSee('Tanaman Rahasia');
});

it('shows an empty state and an action when the user has no sales', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('sales.index'))
        ->assertSee('Belum ada penjualan')
        ->assertSee('Tambahkan transaksi untuk mencatat hasil penjualan panen Anda.')
        ->assertSee('Tambah Penjualan');
});

it('creates an owned sale and calculates the total instead of trusting submitted values', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $harvest = createSaleTestHarvest($user, '20.00');

    $this->actingAs($user)
        ->get(route('sales.create'))
        ->assertSee('Tomat - Kebun Tomat')
        ->assertSee('Satuan harus sama dengan satuan pada data panen.');

    $this->post(route('sales.store'), [
        'user_id' => $otherUser->id,
        'harvest_id' => $harvest->id,
        'buyer_name' => 'Warung Tani',
        'sale_date' => '2026-10-10',
        'quantity' => '2.50',
        'unit' => 'kg',
        'price_per_unit' => '3.33',
        'total' => '1.00',
        'payment_status' => 'lunas',
        'notes' => 'Harga sudah dibayar.',
    ])
        ->assertRedirect(route('sales.index'))
        ->assertSessionHas('status', 'Penjualan berhasil ditambahkan.')
        ->assertSessionHasNoErrors();

    $sale = Sale::query()->where('buyer_name', 'Warung Tani')->firstOrFail();
    expect($sale->total)->toBe('8.33')
        ->and($sale->unit)->toBe('kg')
        ->and($sale->sale_date->toDateString())->toBe('2026-10-10')
        ->and($sale->harvest->crop->land->user_id)->toBe($user->id);

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'harvest_id' => $harvest->id,
        'total' => '8.33',
        'payment_status' => 'lunas',
    ]);

    $this->get(route('dashboard'))
        ->assertViewHas('income', fn ($income): bool => (float) $income === 8.33)
        ->assertViewHas('salesCount', 1)
        ->assertSee('Rp 8');
});

it('rejects a harvest owned by another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownedHarvest = createSaleTestHarvest($user, '20.00', 'kg', 'Tomat Saya');
    $otherHarvest = createSaleTestHarvest($otherUser, '20.00', 'kg', 'Tomat Pengguna Lain');
    $ownedSale = createSaleTestSale($ownedHarvest);

    $this->actingAs($user)
        ->get(route('sales.create'))
        ->assertSee('Tomat Saya - Kebun Tomat Saya')
        ->assertDontSee('Tomat Pengguna Lain');

    $this->from(route('sales.create'))
        ->post(route('sales.store'), [
            'harvest_id' => $otherHarvest->id,
            'buyer_name' => 'Pembeli',
            'sale_date' => '2026-10-10',
            'quantity' => '1.00',
            'unit' => 'kg',
            'price_per_unit' => '1000.00',
            'payment_status' => 'lunas',
        ])
        ->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors([
            'harvest_id' => 'Panen yang dipilih tidak tersedia untuk akun Anda.',
        ]);

    $this->from(route('sales.edit', $ownedSale))
        ->put(route('sales.update', $ownedSale), [
            'harvest_id' => $otherHarvest->id,
            'buyer_name' => 'Pembeli',
            'sale_date' => '2026-10-10',
            'quantity' => '1.00',
            'unit' => 'kg',
            'price_per_unit' => '1000.00',
            'payment_status' => 'lunas',
        ])
        ->assertRedirect(route('sales.edit', $ownedSale))
        ->assertSessionHasErrors([
            'harvest_id' => 'Panen yang dipilih tidak tersedia untuk akun Anda.',
        ]);

    $this->assertDatabaseCount('sales', 1);
    expect($ownedSale->refresh()->harvest_id)->toBe($ownedHarvest->id);
});

it('updates an owned sale without changing its harvest record', function () {
    $user = User::factory()->create();
    $originalHarvest = createSaleTestHarvest($user, '20.00', 'kg', 'Tomat');
    $targetHarvest = createSaleTestHarvest($user, '12.00', 'kg', 'Cabai');
    $sale = createSaleTestSale($originalHarvest, [
        'quantity' => '5.00',
        'total' => '50000.00',
    ]);
    $originalHarvestAttributes = [
        'quantity' => $originalHarvest->quantity,
        'unit' => $originalHarvest->unit,
        'harvest_date' => $originalHarvest->harvest_date->toDateString(),
    ];

    $this->actingAs($user)
        ->get(route('sales.edit', $sale))
        ->assertSee('Edit Penjualan')
        ->assertSee('Pasar Desa');

    $this->put(route('sales.update', $sale), [
        'harvest_id' => $targetHarvest->id,
        'buyer_name' => 'Pasar Baru',
        'sale_date' => '2026-10-05',
        'quantity' => '3.00',
        'unit' => 'kg',
        'price_per_unit' => '12500.00',
        'total' => '1.00',
        'payment_status' => 'belum_lunas',
        'notes' => 'Menunggu pelunasan.',
    ])
        ->assertRedirect(route('sales.index'))
        ->assertSessionHas('status', 'Penjualan berhasil diperbarui.')
        ->assertSessionHasNoErrors();

    $sale->refresh();
    expect($sale->harvest_id)->toBe($targetHarvest->id)
        ->and($sale->total)->toBe('37500.00')
        ->and($sale->buyer_name)->toBe('Pasar Baru')
        ->and($sale->notes)->toBe('Menunggu pelunasan.');

    $updatedOriginalHarvest = $originalHarvest->refresh();
    expect([
        'quantity' => $updatedOriginalHarvest->quantity,
        'unit' => $updatedOriginalHarvest->unit,
        'harvest_date' => $updatedOriginalHarvest->harvest_date->toDateString(),
    ])
        ->toBe($originalHarvestAttributes);
    $this->assertModelExists($targetHarvest);
});

it('hides another users sale from edit update and delete operations', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherHarvest = createSaleTestHarvest($otherUser, '20.00');
    $otherSale = createSaleTestSale($otherHarvest);
    $updateData = [
        'harvest_id' => $otherHarvest->id,
        'buyer_name' => 'Perubahan tidak diizinkan',
        'sale_date' => '2026-10-05',
        'quantity' => '1.00',
        'unit' => 'kg',
        'price_per_unit' => '1000.00',
        'payment_status' => 'lunas',
    ];

    $this->actingAs($user)
        ->get(route('sales.edit', $otherSale))
        ->assertNotFound();

    $this->put(route('sales.update', $otherSale), $updateData)
        ->assertNotFound();

    $this->delete(route('sales.destroy', $otherSale))
        ->assertNotFound();

    $this->assertModelExists($otherSale);
    expect($otherSale->refresh()->buyer_name)->toBe('Pasar Desa');
});

it('deletes only a sale and keeps its harvest and other sales intact', function () {
    $user = User::factory()->create();
    $harvest = createSaleTestHarvest($user, '20.00');
    $sale = createSaleTestSale($harvest);
    $otherSale = createSaleTestSale($harvest, [
        'buyer_name' => 'Pembeli Kedua',
        'quantity' => '3.00',
        'total' => '30000.00',
    ]);

    $this->actingAs($user)
        ->delete(route('sales.destroy', $sale))
        ->assertRedirect(route('sales.index'))
        ->assertSessionHas('status', 'Penjualan berhasil dihapus.');

    $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    $this->assertModelExists($harvest);
    $this->assertModelExists($otherSale);
});

it('rejects quantities above the remaining harvest amount on create and update', function () {
    $user = User::factory()->create();
    $harvest = createSaleTestHarvest($user, '10.00');
    $existingSale = createSaleTestSale($harvest, [
        'quantity' => '8.00',
        'total' => '80000.00',
    ]);
    $data = [
        'harvest_id' => $harvest->id,
        'buyer_name' => 'Pembeli Tambahan',
        'sale_date' => '2026-10-04',
        'quantity' => '3.00',
        'unit' => 'kg',
        'price_per_unit' => '10000.00',
        'payment_status' => 'belum_lunas',
    ];

    $this->actingAs($user)
        ->from(route('sales.create'))
        ->post(route('sales.store'), $data)
        ->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors([
            'quantity' => 'Jumlah penjualan melebihi sisa hasil panen.',
        ]);

    $this->from(route('sales.edit', $existingSale))
        ->put(route('sales.update', $existingSale), [...$data, 'quantity' => '11.00'])
        ->assertRedirect(route('sales.edit', $existingSale))
        ->assertSessionHasErrors([
            'quantity' => 'Jumlah penjualan melebihi sisa hasil panen.',
        ]);

    $this->assertDatabaseCount('sales', 1);
    expect($existingSale->refresh()->quantity)->toBe('8.00');
});

it('rejects unit mismatches and incompatible existing sales when calculating remaining harvest', function () {
    $user = User::factory()->create();
    $harvest = createSaleTestHarvest($user, '20.00', 'kg');

    $this->actingAs($user)
        ->from(route('sales.create'))
        ->post(route('sales.store'), [
            'harvest_id' => $harvest->id,
            'buyer_name' => 'Pembeli',
            'sale_date' => '2026-10-04',
            'quantity' => '1.00',
            'unit' => 'karung',
            'price_per_unit' => '10000.00',
            'payment_status' => 'lunas',
        ])
        ->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors([
            'unit' => 'Satuan penjualan harus sama dengan satuan hasil panen.',
        ]);

    $legacySale = createSaleTestSale($harvest, [
        'unit' => 'karung',
        'quantity' => '1.00',
    ]);

    $this->from(route('sales.create'))
        ->post(route('sales.store'), [
            'harvest_id' => $harvest->id,
            'buyer_name' => 'Pembeli Baru',
            'sale_date' => '2026-10-05',
            'quantity' => '1.00',
            'unit' => 'kg',
            'price_per_unit' => '10000.00',
            'payment_status' => 'lunas',
        ])
        ->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors([
            'harvest_id' => 'Sisa panen tidak dapat dihitung karena terdapat penjualan dengan satuan yang berbeda.',
        ]);

    $this->assertModelExists($legacySale);
    $this->assertDatabaseCount('sales', 1);
});

it('rejects invalid sale dates amounts quantities and payment statuses', function () {
    $user = User::factory()->create();
    $harvest = createSaleTestHarvest($user);

    $this->actingAs($user)
        ->from(route('sales.create'))
        ->post(route('sales.store'), [
            'harvest_id' => $harvest->id,
            'buyer_name' => '',
            'sale_date' => '2026-02-30',
            'quantity' => '0',
            'unit' => 'kg',
            'price_per_unit' => '0',
            'payment_status' => 'menunggu',
        ])
        ->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors([
            'buyer_name' => 'Nama pembeli wajib diisi.',
            'sale_date' => 'Tanggal penjualan harus menggunakan format yang valid.',
            'quantity' => 'Jumlah penjualan harus lebih besar dari 0.',
            'price_per_unit' => 'Harga per satuan harus lebih besar dari 0.',
            'payment_status' => 'Status pembayaran yang dipilih tidak valid.',
        ]);

    $this->from(route('sales.create'))
        ->post(route('sales.store'), [
            'harvest_id' => $harvest->id,
            'buyer_name' => 'Pembeli',
            'sale_date' => '2026-10-05',
            'quantity' => '100000000.00',
            'unit' => 'kg',
            'price_per_unit' => '10000000000000.00',
            'payment_status' => 'lunas',
            'notes' => str_repeat('a', 65536),
        ])
        ->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors([
            'quantity' => 'Jumlah penjualan melebihi batas yang dapat disimpan.',
            'price_per_unit' => 'Harga per satuan melebihi batas yang dapat disimpan.',
            'notes' => 'Catatan maksimal 65.535 karakter.',
        ]);

    $largeHarvest = createSaleTestHarvest($user, '99999999.99', 'kg', 'Padi');

    $this->from(route('sales.create'))
        ->post(route('sales.store'), [
            'harvest_id' => $largeHarvest->id,
            'buyer_name' => 'Pembeli Besar',
            'sale_date' => '2026-10-05',
            'quantity' => '99999999.99',
            'unit' => 'kg',
            'price_per_unit' => '9999999999999.99',
            'payment_status' => 'lunas',
        ])
        ->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors([
            'price_per_unit' => 'Total penjualan melebihi batas yang dapat disimpan.',
        ]);

    $this->assertDatabaseCount('sales', 0);
});

it('paginates the signed in users sales', function () {
    $user = User::factory()->create();
    $harvest = createSaleTestHarvest($user, '100.00');

    foreach (range(1, 11) as $saleNumber) {
        createSaleTestSale($harvest, [
            'buyer_name' => 'Pembeli '.$saleNumber,
            'quantity' => '1.00',
            'total' => '10000.00',
        ]);
    }

    $this->actingAs($user)
        ->get(route('sales.index'))
        ->assertSee('Halaman 1 dari 2')
        ->assertSee('Berikutnya')
        ->assertSee('Sebelumnya');

    $this->get(route('sales.index', ['page' => 2]))
        ->assertSee('Halaman 2 dari 2');
});

it('redirects guests to the sales login page', function () {
    $this->get(route('sales.index'))
        ->assertRedirect(route('login'));
});
