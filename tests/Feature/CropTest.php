<?php

use App\Models\Crop;
use App\Models\User;

it('lists only the signed in users crops and their land details', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Kebun Orang Lain',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $land->crops()->create([
        'name' => 'Tomat Lokal',
        'variety' => 'Rampai',
        'planting_date' => '2026-10-01',
        'estimated_harvest_date' => '2026-12-01',
        'status' => 'perawatan',
        'seed_quantity' => 5,
        'seed_unit' => 'gram',
        'notes' => 'Tanam di bedeng utara.',
    ]);
    $otherLand->crops()->create([
        'name' => 'Tanaman Rahasia',
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);

    $this->actingAs($user)
        ->get(route('crops.index'))
        ->assertSee('Tomat Lokal')
        ->assertSee('Rampai')
        ->assertSee('Kebun Sendiri')
        ->assertSee('01/10/2026')
        ->assertSee('01/12/2026')
        ->assertSee('Perawatan')
        ->assertSee('5,00')
        ->assertSee('gram')
        ->assertSee('Tanam di bedeng utara.')
        ->assertDontSee('Tanaman Rahasia')
        ->assertDontSee('Kebun Orang Lain');
});

it('shows the crop empty state when the user has no crops', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('crops.index'))
        ->assertSee('Belum ada tanaman')
        ->assertSee('Tambahkan tanaman ke salah satu lahan Anda.')
        ->assertSee('Tambah Tanaman');
});

it('creates a crop through the owned land relationship', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherUser->lands()->create([
        'name' => 'Kebun Orang Lain',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $data = [
        'land_id' => $land->id,
        'name' => 'Cabai Rawit',
        'variety' => 'Domba',
        'planting_date' => '2026-10-10',
        'estimated_harvest_date' => '2027-01-10',
        'status' => 'ditanam',
        'seed_quantity' => '2.50',
        'seed_unit' => 'gram',
        'notes' => 'Benih pilihan.',
    ];

    $this->actingAs($user)
        ->post(route('crops.store'), $data)
        ->assertRedirect(route('crops.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('crops', [
        'land_id' => $land->id,
        'name' => 'Cabai Rawit',
        'variety' => 'Domba',
        'status' => 'ditanam',
        'seed_quantity' => '2.50',
        'seed_unit' => 'gram',
        'notes' => 'Benih pilihan.',
    ]);

    $crop = Crop::query()->where('name', 'Cabai Rawit')->firstOrFail();
    expect($crop->planting_date->toDateString())->toBe('2026-10-10')
        ->and($crop->estimated_harvest_date->toDateString())->toBe('2027-01-10');

    $this->get(route('crops.index'))
        ->assertSee('Cabai Rawit')
        ->assertSee('Kebun Sendiri');
});

it('rejects a crop assigned to another users land', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Kebun Orang Lain',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);

    $this->actingAs($user)
        ->get(route('crops.create'))
        ->assertSee('Kebun Sendiri')
        ->assertDontSee('Kebun Orang Lain');

    $this->from(route('crops.create'))
        ->post(route('crops.store'), [
            'land_id' => $otherLand->id,
            'name' => 'Cabai',
            'planting_date' => '2026-10-10',
            'status' => 'ditanam',
        ])
        ->assertRedirect(route('crops.create'))
        ->assertSessionHasErrors([
            'land_id' => 'Lahan yang dipilih tidak tersedia untuk akun Anda.',
        ]);

    $this->assertDatabaseCount('crops', 0);
});

it('updates owned crops and hides crops belonging to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Kebun Orang Lain',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Jagung',
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Tanaman Rahasia',
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);
    $updatedData = [
        'land_id' => $land->id,
        'name' => 'Jagung Manis',
        'variety' => 'Bonanza',
        'planting_date' => '2026-10-02',
        'estimated_harvest_date' => '2027-01-02',
        'status' => 'perawatan',
        'seed_quantity' => '3.25',
        'seed_unit' => 'kg',
        'notes' => 'Pertumbuhan baik.',
    ];

    $this->actingAs($user)
        ->get(route('crops.edit', $crop))
        ->assertSee('Edit Tanaman')
        ->assertSee('Jagung');

    $this->put(route('crops.update', $crop), $updatedData)
        ->assertRedirect(route('crops.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('crops', [
        'id' => $crop->id,
        'land_id' => $land->id,
        'name' => 'Jagung Manis',
        'variety' => 'Bonanza',
        'status' => 'perawatan',
        'seed_quantity' => '3.25',
        'seed_unit' => 'kg',
        'notes' => 'Pertumbuhan baik.',
    ]);

    $this->get(route('crops.edit', $otherCrop))
        ->assertNotFound();

    $this->put(route('crops.update', $otherCrop), $updatedData)
        ->assertNotFound();

    $this->delete(route('crops.destroy', $otherCrop))
        ->assertNotFound();

    $this->assertDatabaseHas('crops', [
        'id' => $otherCrop->id,
        'name' => 'Tanaman Rahasia',
    ]);
});

it('deletes an owned crop without related records', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tanaman Uji',
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);

    $this->actingAs($user)
        ->delete(route('crops.destroy', $crop))
        ->assertRedirect(route('crops.index'))
        ->assertSessionHas('status', 'Tanaman berhasil dihapus.');

    $this->assertDatabaseMissing('crops', ['id' => $crop->id]);
});

it('prevents deleting crops that have any related records', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $activityCrop = $land->crops()->create([
        'name' => 'Tanaman Aktivitas',
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);
    $expenseCrop = $land->crops()->create([
        'name' => 'Tanaman Pengeluaran',
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);
    $harvestCrop = $land->crops()->create([
        'name' => 'Tanaman Panen',
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);
    $noteCrop = $land->crops()->create([
        'name' => 'Tanaman Catatan',
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);

    $activityCrop->activities()->create([
        'activity_type' => 'penyiraman',
        'scheduled_date' => '2026-10-11',
        'status' => 'belum_dilakukan',
    ]);
    $user->expenses()->create([
        'crop_id' => $expenseCrop->id,
        'category' => 'operasional',
        'description' => 'Biaya perawatan',
        'amount' => 10000,
        'expense_date' => '2026-10-11',
    ]);
    $harvestCrop->harvests()->create([
        'harvest_date' => '2026-12-01',
        'quantity' => 2,
        'unit' => 'kg',
    ]);
    $noteCrop->notes()->create([
        'title' => 'Catatan tanaman',
        'content' => 'Perlu pemantauan.',
        'note_date' => '2026-10-11',
    ]);

    $this->actingAs($user);

    foreach ([$activityCrop, $expenseCrop, $harvestCrop, $noteCrop] as $crop) {
        $this->delete(route('crops.destroy', $crop))
            ->assertRedirect(route('crops.index'))
            ->assertSessionHas('error', 'Tanaman tidak dapat dihapus karena masih memiliki aktivitas, pengeluaran, panen, atau catatan terkait.');

        $this->assertModelExists($crop);
    }
});

it('shows Indonesian validation errors for invalid crop data', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);

    $this->actingAs($user)
        ->from(route('crops.create'))
        ->post(route('crops.store'), [
            'land_id' => $land->id,
            'name' => 'Tomat',
            'planting_date' => '2026-10-10',
            'estimated_harvest_date' => '2026-09-30',
            'status' => 'status_salah',
            'seed_quantity' => '-1',
        ])
        ->assertRedirect(route('crops.create'))
        ->assertSessionHasErrors([
            'estimated_harvest_date' => 'Perkiraan panen tidak boleh sebelum tanggal tanam.',
            'status' => 'Status tanaman yang dipilih tidak valid.',
            'seed_quantity' => 'Jumlah benih tidak boleh kurang dari 0.',
        ]);

    $this->from(route('crops.create'))
        ->post(route('crops.store'), [
            'land_id' => $land->id,
            'name' => '',
            'planting_date' => '',
            'status' => '',
        ])
        ->assertRedirect(route('crops.create'))
        ->assertSessionHasErrors([
            'name' => 'Nama tanaman wajib diisi.',
            'planting_date' => 'Tanggal tanam wajib diisi.',
            'status' => 'Pilih status tanaman.',
        ]);

    $this->assertDatabaseCount('crops', 0);
});

it('makes a newly created crop available to activities and dashboard counts', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $land = $user->lands()->create([
        'name' => 'Kebun Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);

    $this->actingAs($user)
        ->post(route('crops.store'), [
            'land_id' => $land->id,
            'name' => 'Tomat Baru',
            'planting_date' => '2026-10-10',
            'status' => 'ditanam',
        ])
        ->assertRedirect(route('crops.index'))
        ->assertSessionHasNoErrors();

    $crop = Crop::query()->where('name', 'Tomat Baru')->firstOrFail();

    $this->get(route('activities.create'))
        ->assertSee('Tomat Baru - Kebun Sendiri');

    $this->post(route('activities.store'), [
        'crop_id' => $crop->id,
        'activity_type' => 'penyiraman',
        'scheduled_date' => '2026-10-11',
        'status' => 'belum_dilakukan',
    ])
        ->assertRedirect(route('activities.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('activities', [
        'crop_id' => $crop->id,
        'activity_type' => 'penyiraman',
    ]);

    $this->get(route('dashboard'))
        ->assertViewHas('plantedCropCount', 1);
});

it('redirects guests to the crop login page', function () {
    $this->get(route('crops.index'))
        ->assertRedirect(route('login'));
});
