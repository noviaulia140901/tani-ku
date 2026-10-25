<?php

use App\Models\User;

it('lists only the signed in users lands', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $user->lands()->create([
        'name' => 'Lahan Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1.25,
        'area_unit' => 'hektare',
        'soil_type' => 'Lempung',
        'status' => 'aktif',
        'description' => 'Lahan untuk sayuran.',
    ]);
    $otherUser->lands()->create([
        'name' => 'Lahan Rahasia',
        'location' => 'Desa Sebelah',
        'area' => 2,
        'area_unit' => 'hektare',
        'status' => 'persiapan',
    ]);

    $this->actingAs($user)
        ->get(route('lands.index'))
        ->assertSee('Lahan Sendiri')
        ->assertSee('Desa Makmur')
        ->assertSee('1,25 hektare')
        ->assertSee('Lempung')
        ->assertSee('Aktif')
        ->assertSee('Lahan untuk sayuran.')
        ->assertDontSee('Lahan Rahasia')
        ->assertDontSee('Desa Sebelah');
});

it('renders address suggestions without assigning coordinates before a selection', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('lands.create'))
        ->assertSee('data-land-location-picker', false)
        ->assertSee('data-land-location-search', false)
        ->assertSee('role="combobox"', false)
        ->assertSee('role="listbox"', false)
        ->assertSeeInOrder(['name="latitude"', 'type="number"', 'data-land-latitude'], false)
        ->assertSeeInOrder(['name="longitude"', 'type="number"', 'data-land-longitude'], false)
        ->assertSee('coordinates-help', false)
        ->assertSee('data-land-geolocation', false)
        ->assertSee('Gunakan lokasi perangkat')
        ->assertSee('data-land-satellite-map', false)
        ->assertSee('Lihat Satelit di Google Maps')
        ->assertSee('Teks pencarian dikirim ke Photon berbasis OpenStreetMap hanya setelah Anda mulai mencari.')
        ->assertSee('https://www.openstreetmap.org/copyright');
});

it('renders saved land location coordinates on the edit form', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Kebun Bertitik',
        'location' => 'Alamat kebun tersimpan',
        'latitude' => '-6.9',
        'longitude' => '107.6',
        'area' => 1,
        'area_unit' => 'hektare',
        'status' => 'aktif',
    ]);

    $this->actingAs($user)
        ->get(route('lands.edit', $land))
        ->assertSee('value="Alamat kebun tersimpan"', false)
        ->assertSee('data-saved-latitude="-6.9"', false)
        ->assertSee('data-saved-longitude="107.6"', false)
        ->assertSee('value="-6.9"', false)
        ->assertSee('value="107.6"', false)
        ->assertSee('data-land-satellite-map', false)
        ->assertSee('Lihat Satelit di Google Maps')
        ->assertSee('Hapus titik lokasi tersimpan')
        ->assertSee('data-land-map', false);
});

it('creates a land for the signed in user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $data = [
        'name' => 'Kebun Baru',
        'location' => 'Desa Hijau',
        'area' => '1.50',
        'area_unit' => 'hektare',
        'soil_type' => 'Andosol',
        'status' => 'persiapan',
        'description' => 'Lahan baru.',
        'user_id' => $otherUser->id,
    ];

    $this->actingAs($user)
        ->post(route('lands.store'), $data)
        ->assertRedirect(route('lands.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('lands', [
        'user_id' => $user->id,
        'name' => 'Kebun Baru',
        'location' => 'Desa Hijau',
        'area' => '1.50',
        'area_unit' => 'hektare',
        'soil_type' => 'Andosol',
        'status' => 'persiapan',
        'description' => 'Lahan baru.',
    ]);

    $this->get(route('lands.index'))
        ->assertSee('Kebun Baru');
});

it('stores valid manual coordinates with a new land', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lands.store'), [
            'name' => 'Kebun Berkoordinat',
            'location' => 'Desa Hijau',
            'latitude' => '-6.9147440',
            'longitude' => '107.6098100',
            'area' => '1.50',
            'area_unit' => 'hektare',
            'status' => 'aktif',
        ])
        ->assertRedirect(route('lands.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('lands', [
        'user_id' => $user->id,
        'name' => 'Kebun Berkoordinat',
        'latitude' => '-6.9147440',
        'longitude' => '107.6098100',
    ]);
});

it('rejects coordinates outside their valid ranges', function (string $field, string $value, string $message) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('lands.create'))
        ->post(route('lands.store'), [
            'name' => 'Kebun Tidak Valid',
            'location' => 'Desa Hijau',
            'latitude' => $field === 'latitude' ? $value : '-6.9',
            'longitude' => $field === 'longitude' ? $value : '107.6',
            'area' => '1',
            'area_unit' => 'hektare',
            'status' => 'aktif',
        ])
        ->assertRedirect(route('lands.create'))
        ->assertSessionHasErrors([$field => $message]);

    $this->assertDatabaseCount('lands', 0);
})->with([
    'latitude above 90' => ['latitude', '90.0000001', 'Latitude harus berada antara -90 dan 90.'],
    'latitude below -90' => ['latitude', '-90.0000001', 'Latitude harus berada antara -90 dan 90.'],
    'longitude above 180' => ['longitude', '180.0000001', 'Longitude harus berada antara -180 dan 180.'],
    'longitude below -180' => ['longitude', '-180.0000001', 'Longitude harus berada antara -180 dan 180.'],
]);

it('rejects a partial coordinate pair', function (array $coordinates, string $missingField) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('lands.create'))
        ->post(route('lands.store'), [
            'name' => 'Kebun Koordinat Parsial',
            'location' => 'Desa Hijau',
            ...$coordinates,
            'area' => '1',
            'area_unit' => 'hektare',
            'status' => 'aktif',
        ])
        ->assertRedirect(route('lands.create'))
        ->assertSessionHasErrors([$missingField => 'Latitude dan longitude harus diisi bersama.']);

    $this->assertDatabaseCount('lands', 0);
})->with([
    'latitude without longitude' => [['latitude' => '-6.9'], 'longitude'],
    'longitude without latitude' => [['longitude' => '107.6'], 'latitude'],
]);

it('rejects invalid land status and required land fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('lands.create'))
        ->post(route('lands.store'), [
            'name' => '',
            'location' => '',
            'area' => 'luas',
            'area_unit' => '',
            'status' => 'dihapus',
        ])
        ->assertRedirect(route('lands.create'))
        ->assertSessionHasErrors([
            'name',
            'location',
            'area',
            'area_unit',
            'status' => 'Status lahan yang dipilih tidak valid.',
        ]);

    $this->assertDatabaseCount('lands', 0);
});

it('updates and deletes owned lands while hiding another users land', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Lahan Lama',
        'location' => 'Lokasi Lama',
        'area' => 1,
        'area_unit' => 'hektare',
        'status' => 'aktif',
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Lahan Milik Orang Lain',
        'location' => 'Lokasi Rahasia',
        'latitude' => '-7.1',
        'longitude' => '108.2',
        'area' => 2,
        'area_unit' => 'hektare',
        'status' => 'aktif',
    ]);
    $updatedData = [
        'name' => 'Lahan Diperbarui',
        'location' => 'Lokasi Baru',
        'area' => '3.25',
        'area_unit' => 'hektare',
        'soil_type' => 'Lempung',
        'status' => 'tidak_aktif',
        'description' => 'Sedang diistirahatkan.',
        'latitude' => '-6.9',
        'longitude' => '107.6',
    ];

    $this->actingAs($user)
        ->get(route('lands.edit', $land))
        ->assertSee('Edit Lahan')
        ->assertSee('Lahan Lama');

    $this->put(route('lands.update', $land), $updatedData)
        ->assertRedirect(route('lands.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('lands', [
        'id' => $land->id,
        'user_id' => $user->id,
        'name' => 'Lahan Diperbarui',
        'status' => 'tidak_aktif',
        'latitude' => '-6.9',
        'longitude' => '107.6',
    ]);

    $this->get(route('lands.edit', $otherLand))
        ->assertNotFound();

    $this->put(route('lands.update', $otherLand), $updatedData)
        ->assertNotFound();

    $this->delete(route('lands.destroy', $otherLand))
        ->assertNotFound();

    $this->assertDatabaseHas('lands', [
        'id' => $otherLand->id,
        'name' => 'Lahan Milik Orang Lain',
        'latitude' => '-7.1',
        'longitude' => '108.2',
    ]);

    $this->delete(route('lands.destroy', $land))
        ->assertRedirect(route('lands.index'))
        ->assertSessionHas('status', 'Lahan berhasil dihapus.');

    $this->assertDatabaseMissing('lands', ['id' => $land->id]);
});

it('preserves coordinates when an update omits the coordinate fields', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Lahan Berkoordinat',
        'location' => 'Desa Makmur',
        'latitude' => '-6.9',
        'longitude' => '107.6',
        'area' => 1,
        'area_unit' => 'hektare',
        'status' => 'aktif',
    ]);

    $this->actingAs($user)
        ->put(route('lands.update', $land), [
            'name' => 'Lahan Diperbarui',
            'location' => 'Desa Makmur',
            'area' => '1',
            'area_unit' => 'hektare',
            'status' => 'aktif',
        ])
        ->assertRedirect(route('lands.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('lands', [
        'id' => $land->id,
        'name' => 'Lahan Diperbarui',
        'latitude' => '-6.9',
        'longitude' => '107.6',
    ]);
});

it('preserves coordinates when an update submits an empty pair without a clear action', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Lahan Berkoordinat',
        'location' => 'Desa Makmur',
        'latitude' => '-6.9',
        'longitude' => '107.6',
        'area' => 1,
        'area_unit' => 'hektare',
        'status' => 'aktif',
    ]);

    $this->actingAs($user)
        ->put(route('lands.update', $land), [
            'name' => 'Lahan Diperbarui',
            'location' => 'Desa Makmur',
            'latitude' => '',
            'longitude' => '',
            'area' => '1',
            'area_unit' => 'hektare',
            'status' => 'aktif',
        ])
        ->assertRedirect(route('lands.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('lands', [
        'id' => $land->id,
        'latitude' => '-6.9',
        'longitude' => '107.6',
    ]);
});

it('clears stored coordinates only when the explicit clear action is valid', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Lahan Berkoordinat',
        'location' => 'Desa Makmur',
        'latitude' => '-6.9',
        'longitude' => '107.6',
        'area' => 1,
        'area_unit' => 'hektare',
        'status' => 'aktif',
    ]);

    $this->actingAs($user)
        ->put(route('lands.update', $land), [
            'name' => 'Lahan Tanpa Titik',
            'location' => 'Desa Makmur',
            'latitude' => '',
            'longitude' => '',
            'clear_coordinates' => '1',
            'area' => '1',
            'area_unit' => 'hektare',
            'status' => 'aktif',
        ])
        ->assertRedirect(route('lands.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('lands', [
        'id' => $land->id,
        'latitude' => null,
        'longitude' => null,
    ]);
});

it('prevents deleting a land that still has crops', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Lahan Berisi Tanaman',
        'location' => 'Desa Makmur',
        'area' => 1,
        'area_unit' => 'hektare',
        'status' => 'aktif',
    ]);
    $land->crops()->create([
        'name' => 'Cabai',
        'planting_date' => today(),
    ]);

    $this->actingAs($user)
        ->delete(route('lands.destroy', $land))
        ->assertRedirect(route('lands.index'))
        ->assertSessionHas('error', 'Lahan tidak dapat dihapus karena masih memiliki tanaman.');

    $this->assertDatabaseHas('lands', ['id' => $land->id]);
    $this->assertDatabaseHas('crops', ['land_id' => $land->id, 'name' => 'Cabai']);
});

it('redirects guests to the land login page', function () {
    $this->get(route('lands.index'))
        ->assertRedirect(route('login'));
});
