<?php

use App\Models\Harvest;
use App\Models\User;

it('lists only the signed in users harvests with crop and land details', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tomat Lokal',
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Kebun Orang Lain',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Tanaman Rahasia',
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);
    $crop->harvests()->create([
        'harvest_date' => '2026-10-01',
        'quantity' => '125.50',
        'unit' => 'kg',
        'quality' => 'Kualitas premium',
        'notes' => 'Dipetik pagi hari.',
    ]);
    $otherCrop->harvests()->create([
        'harvest_date' => '2026-10-02',
        'quantity' => 50,
        'unit' => 'kg',
        'notes' => 'Catatan rahasia.',
    ]);

    $this->actingAs($user)
        ->get(route('harvests.index'))
        ->assertSee('Tomat Lokal')
        ->assertSee('Kebun Sendiri')
        ->assertSee('01/10/2026')
        ->assertSee('125,50 kg')
        ->assertSee('Kualitas premium')
        ->assertSee('Dipetik pagi hari.')
        ->assertDontSee('Tanaman Rahasia')
        ->assertDontSee('Catatan rahasia.');
});

it('shows the empty state when the user has no harvests', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('harvests.index'))
        ->assertSee('Belum ada panen')
        ->assertSee('Tambahkan catatan panen dari tanaman di lahan Anda.')
        ->assertSee('Tambah Panen');
});

it('escapes user supplied harvest and crop details in the list', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => '<script>alert(1)</script>',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => '<script>alert(2)</script>',
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);
    $crop->harvests()->create([
        'harvest_date' => '2026-10-01',
        'quantity' => 1,
        'unit' => 'kg',
        'quality' => '<script>alert(3)</script>',
        'notes' => '<script>alert(4)</script>',
    ]);

    $this->actingAs($user)
        ->get(route('harvests.index'))
        ->assertSee('<script>alert(1)</script>')
        ->assertSee('<script>alert(2)</script>')
        ->assertSee('<script>alert(3)</script>')
        ->assertSee('<script>alert(4)</script>')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<script>alert(2)</script>', false)
        ->assertDontSee('<script>alert(3)</script>', false)
        ->assertDontSee('<script>alert(4)</script>', false);
});

it('creates a harvest using a crop owned by the signed in user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Cabai Rawit',
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);
    $data = [
        'user_id' => $otherUser->id,
        'crop_id' => $crop->id,
        'harvest_date' => '2026-10-10',
        'quantity' => '25.75',
        'unit' => 'kg',
        'quality' => 'Grade A',
        'notes' => 'Panen pertama.',
    ];

    $this->actingAs($user)
        ->get(route('harvests.create'))
        ->assertSee('Cabai Rawit - Kebun Sendiri');

    $this->post(route('harvests.store'), $data)
        ->assertRedirect(route('harvests.index'))
        ->assertSessionHas('status', 'Panen berhasil ditambahkan.')
        ->assertSessionHasNoErrors();

    $harvest = Harvest::query()->where('crop_id', $crop->id)->firstOrFail();
    expect($harvest->harvest_date->toDateString())->toBe('2026-10-10')
        ->and($harvest->quantity)->toBe('25.75')
        ->and($harvest->unit)->toBe('kg')
        ->and($harvest->quality)->toBe('Grade A')
        ->and($harvest->notes)->toBe('Panen pertama.')
        ->and($harvest->crop->land->user_id)->toBe($user->id);

    $this->get(route('harvests.index'))
        ->assertSee('25,75 kg')
        ->assertSee('Panen pertama.');
});

it('rejects a crop from another users land and excludes it from the form', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $userLand = $user->lands()->create([
        'name' => 'Lahan Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $userCrop = $userLand->crops()->create([
        'name' => 'Tanaman Sendiri',
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Lahan Orang Lain',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Tanaman Rahasia',
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);

    $this->actingAs($user)
        ->get(route('harvests.create'))
        ->assertSee('Tanaman Sendiri - Lahan Sendiri')
        ->assertDontSee('Tanaman Rahasia')
        ->assertDontSee('Lahan Orang Lain');

    $this->from(route('harvests.create'))
        ->post(route('harvests.store'), [
            'crop_id' => $otherCrop->id,
            'harvest_date' => '2026-10-10',
            'quantity' => '5.00',
            'unit' => 'kg',
        ])
        ->assertRedirect(route('harvests.create'))
        ->assertSessionHasErrors([
            'crop_id' => 'Tanaman yang dipilih tidak tersedia untuk akun Anda.',
        ]);

    $ownedHarvest = $userCrop->harvests()->create([
        'harvest_date' => '2026-10-01',
        'quantity' => '10.00',
        'unit' => 'kg',
    ]);

    $this->from(route('harvests.edit', $ownedHarvest))
        ->put(route('harvests.update', $ownedHarvest), [
            'crop_id' => $otherCrop->id,
            'harvest_date' => '2026-10-10',
            'quantity' => '5.00',
            'unit' => 'kg',
        ])
        ->assertRedirect(route('harvests.edit', $ownedHarvest))
        ->assertSessionHasErrors([
            'crop_id' => 'Tanaman yang dipilih tidak tersedia untuk akun Anda.',
        ]);

    $this->assertDatabaseHas('harvests', [
        'id' => $ownedHarvest->id,
        'crop_id' => $userCrop->id,
    ]);
    expect($ownedHarvest->refresh()->quantity)->toBe('10.00');
});

it('updates and deletes owned harvests while hiding another users harvests', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tomat',
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Kebun Orang Lain',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Tanaman Orang Lain',
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);
    $harvest = $crop->harvests()->create([
        'harvest_date' => '2026-10-01',
        'quantity' => '10.00',
        'unit' => 'kg',
        'quality' => 'Grade B',
    ]);
    $otherHarvest = $otherCrop->harvests()->create([
        'harvest_date' => '2026-10-02',
        'quantity' => 15,
        'unit' => 'kg',
    ]);
    $updatedData = [
        'crop_id' => $crop->id,
        'harvest_date' => '2026-10-05',
        'quantity' => '12.50',
        'unit' => 'keranjang',
        'quality' => 'Grade A',
        'notes' => 'Hasil meningkat.',
    ];

    $this->actingAs($user)
        ->get(route('harvests.edit', $harvest))
        ->assertSee('Edit Panen')
        ->assertSee('Tomat');

    $this->put(route('harvests.update', $harvest), $updatedData)
        ->assertRedirect(route('harvests.index'))
        ->assertSessionHas('status', 'Panen berhasil diperbarui.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('harvests', [
        'id' => $harvest->id,
        'crop_id' => $crop->id,
        'unit' => 'keranjang',
        'quality' => 'Grade A',
        'notes' => 'Hasil meningkat.',
    ]);
    $harvest->refresh();
    expect($harvest->harvest_date->toDateString())->toBe('2026-10-05')
        ->and($harvest->quantity)->toBe('12.50');

    $this->get(route('harvests.edit', $otherHarvest))
        ->assertNotFound();

    $this->put(route('harvests.update', $otherHarvest), $updatedData)
        ->assertNotFound();

    $this->delete(route('harvests.destroy', $otherHarvest))
        ->assertNotFound();

    $this->assertModelExists($otherHarvest);

    $this->delete(route('harvests.destroy', $harvest))
        ->assertRedirect(route('harvests.index'))
        ->assertSessionHas('status', 'Panen berhasil dihapus.');

    $this->assertDatabaseMissing('harvests', ['id' => $harvest->id]);
});

it('prevents deleting a harvest that has related sales', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Cabai',
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);
    $harvest = $crop->harvests()->create([
        'harvest_date' => '2026-10-01',
        'quantity' => '20.00',
        'unit' => 'kg',
    ]);
    $sale = $harvest->sales()->create([
        'buyer_name' => 'Pasar Desa',
        'sale_date' => '2026-10-02',
        'quantity' => '5.00',
        'unit' => 'kg',
        'price_per_unit' => '10000.00',
        'total' => '50000.00',
        'payment_status' => 'lunas',
    ]);

    $this->actingAs($user)
        ->delete(route('harvests.destroy', $harvest))
        ->assertRedirect(route('harvests.index'))
        ->assertSessionHas('error', 'Panen tidak dapat dihapus karena sudah memiliki data penjualan.');

    $this->assertModelExists($harvest);
    $this->assertModelExists($sale);
});

it('rejects invalid harvest dates quantities and required fields', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tomat',
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);

    $this->actingAs($user)
        ->from(route('harvests.create'))
        ->post(route('harvests.store'), [
            'crop_id' => $crop->id,
            'harvest_date' => 'tanggal-salah',
            'quantity' => '0',
            'unit' => '',
        ])
        ->assertRedirect(route('harvests.create'))
        ->assertSessionHasErrors([
            'harvest_date' => 'Tanggal panen harus menggunakan format yang valid.',
            'quantity' => 'Jumlah hasil panen harus lebih besar dari 0.',
            'unit' => 'Satuan hasil panen wajib diisi.',
        ]);

    $this->from(route('harvests.create'))
        ->post(route('harvests.store'), [
            'crop_id' => $crop->id,
            'harvest_date' => '2026-02-30',
            'quantity' => '1.00',
            'unit' => 'kg',
        ])
        ->assertRedirect(route('harvests.create'))
        ->assertSessionHasErrors([
            'harvest_date' => 'Tanggal panen harus menggunakan format yang valid.',
        ]);

    $this->from(route('harvests.create'))
        ->post(route('harvests.store'), [
            'crop_id' => $crop->id,
            'harvest_date' => '',
            'quantity' => '',
            'unit' => '',
        ])
        ->assertRedirect(route('harvests.create'))
        ->assertSessionHasErrors([
            'harvest_date' => 'Tanggal panen wajib diisi.',
            'quantity' => 'Jumlah hasil panen wajib diisi.',
            'unit' => 'Satuan hasil panen wajib diisi.',
        ]);

    $this->from(route('harvests.create'))
        ->post(route('harvests.store'), [
            'crop_id' => $crop->id,
            'harvest_date' => '2026-10-10',
            'quantity' => '100000000.00',
            'unit' => str_repeat('a', 256),
            'quality' => str_repeat('b', 256),
            'notes' => str_repeat('c', 65536),
        ])
        ->assertRedirect(route('harvests.create'))
        ->assertSessionHasErrors([
            'quantity' => 'Jumlah hasil panen melebihi batas yang dapat disimpan.',
            'unit' => 'Satuan hasil panen maksimal 255 karakter.',
            'quality' => 'Kualitas maksimal 255 karakter.',
            'notes' => 'Catatan maksimal 65.535 karakter.',
        ]);

    $this->assertDatabaseCount('harvests', 0);
});

it('paginates the signed in users harvest list', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tomat',
        'planting_date' => '2026-05-01',
        'status' => 'siap_panen',
    ]);

    foreach (range(1, 11) as $harvestNumber) {
        $crop->harvests()->create([
            'harvest_date' => '2026-10-10',
            'quantity' => $harvestNumber,
            'unit' => 'kg',
        ]);
    }

    $this->actingAs($user)
        ->get(route('harvests.index'))
        ->assertSee('Halaman 1 dari 2')
        ->assertSee('Berikutnya')
        ->assertSee('Sebelumnya');

    $this->get(route('harvests.index', ['page' => 2]))
        ->assertSee('Halaman 2 dari 2')
        ->assertSee('Berikutnya')
        ->assertSee('Sebelumnya');
});

it('redirects guests to the harvest login page', function () {
    $this->get(route('harvests.index'))
        ->assertRedirect(route('login'));
});
