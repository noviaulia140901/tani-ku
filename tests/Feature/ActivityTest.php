<?php

use App\Models\User;

it('lists only the signed in users activities in date order', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $land = $user->lands()->create([
        'name' => 'Lahan Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tanaman Sendiri',
        'planting_date' => today(),
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Lahan Orang Lain',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Tanaman Rahasia',
        'planting_date' => today(),
    ]);

    $crop->activities()->create([
        'activity_type' => 'penyiraman',
        'scheduled_date' => today()->addDays(2),
        'status' => 'belum_dilakukan',
    ]);
    $crop->activities()->create([
        'activity_type' => 'pemupukan',
        'scheduled_date' => today()->addDay(),
        'status' => 'selesai',
    ]);
    $otherCrop->activities()->create([
        'activity_type' => 'pemangkasan rahasia',
        'scheduled_date' => today()->addDay(),
        'status' => 'belum_dilakukan',
    ]);

    $this->actingAs($user)
        ->get(route('activities.index'))
        ->assertSeeInOrder(['Pemupukan', 'Penyiraman'])
        ->assertSee('Lahan Sendiri')
        ->assertDontSee('Tanaman Rahasia')
        ->assertDontSee('Lahan Orang Lain')
        ->assertDontSee('pemangkasan rahasia');
});

it('uses Indonesian labels when the activity list has multiple pages', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Lahan Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tanaman Sendiri',
        'planting_date' => today(),
    ]);

    foreach (range(1, 11) as $activityNumber) {
        $crop->activities()->create([
            'activity_type' => 'perawatan '.$activityNumber,
            'scheduled_date' => today()->addDays($activityNumber),
            'status' => 'belum_dilakukan',
        ]);
    }

    $this->actingAs($user)
        ->get(route('activities.index'))
        ->assertSee('Halaman 1 dari 2')
        ->assertSee('Berikutnya')
        ->assertSee('Sebelumnya');

    $this->get(route('activities.index', ['page' => 2]))
        ->assertSee('Halaman 2 dari 2')
        ->assertSee('Berikutnya')
        ->assertSee('Sebelumnya');
});

it('creates activities only for crops owned by the signed in user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $land = $user->lands()->create([
        'name' => 'Lahan Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tanaman Sendiri',
        'planting_date' => today(),
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Lahan Orang Lain',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Tanaman Orang Lain',
        'planting_date' => today(),
    ]);
    $data = [
        'crop_id' => $crop->id,
        'activity_type' => 'pemupukan',
        'scheduled_date' => today()->addDays(3)->toDateString(),
        'scheduled_time' => '08:30',
        'product_name' => 'Pupuk kompos',
        'dosage' => '2 kg',
        'status' => 'belum_dilakukan',
        'notes' => 'Berikan setelah penyiraman.',
    ];

    $this->actingAs($user)
        ->get(route('activities.create'))
        ->assertSee('Tanaman Sendiri - Lahan Sendiri')
        ->assertDontSee('Tanaman Orang Lain');

    $this->post(route('activities.store'), $data)
        ->assertRedirect(route('activities.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('activities', [
        'crop_id' => $crop->id,
        'activity_type' => 'pemupukan',
        'scheduled_time' => '08:30',
        'status' => 'belum_dilakukan',
    ]);

    $this->from(route('activities.create'))
        ->post(route('activities.store'), [...$data, 'crop_id' => $otherCrop->id])
        ->assertSessionHasErrors('crop_id');

    $this->assertDatabaseMissing('activities', [
        'crop_id' => $otherCrop->id,
        'activity_type' => 'pemupukan',
    ]);
});

it('updates and deletes owned activities but hides another users activities', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $land = $user->lands()->create([
        'name' => 'Lahan Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tanaman Sendiri',
        'planting_date' => today(),
    ]);
    $otherLand = $otherUser->lands()->create([
        'name' => 'Lahan Orang Lain',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Tanaman Orang Lain',
        'planting_date' => today(),
    ]);
    $activity = $crop->activities()->create([
        'activity_type' => 'pemupukan',
        'scheduled_date' => today()->addDay(),
        'status' => 'belum_dilakukan',
    ]);
    $otherActivity = $otherCrop->activities()->create([
        'activity_type' => 'penyiraman',
        'scheduled_date' => today()->addDay(),
        'status' => 'belum_dilakukan',
    ]);
    $updatedData = [
        'crop_id' => $crop->id,
        'activity_type' => 'pemupukan susulan',
        'scheduled_date' => today()->addDays(2)->toDateString(),
        'scheduled_time' => '',
        'product_name' => '',
        'dosage' => '',
        'status' => 'selesai',
        'notes' => '',
    ];

    $this->actingAs($user)
        ->get(route('activities.edit', $activity))
        ->assertSee('Edit Aktivitas')
        ->assertSee('Tanaman Sendiri - Lahan Sendiri');

    $this->put(route('activities.update', $activity), $updatedData)
        ->assertRedirect(route('activities.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('activities', [
        'id' => $activity->id,
        'activity_type' => 'pemupukan susulan',
        'status' => 'selesai',
    ]);

    $this->get(route('activities.edit', $otherActivity))
        ->assertNotFound();

    $this->put(route('activities.update', $otherActivity), $updatedData)
        ->assertNotFound();

    $this->delete(route('activities.destroy', $otherActivity))
        ->assertNotFound();

    $this->assertDatabaseHas('activities', ['id' => $otherActivity->id]);

    $this->delete(route('activities.destroy', $activity))
        ->assertRedirect(route('activities.index'));

    $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
});

it('shows Indonesian validation errors for invalid activity schedules', function () {
    $user = User::factory()->create();
    $land = $user->lands()->create([
        'name' => 'Lahan Sendiri',
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $crop = $land->crops()->create([
        'name' => 'Tanaman Sendiri',
        'planting_date' => today(),
    ]);

    $this->actingAs($user)
        ->from(route('activities.create'))
        ->post(route('activities.store'), [
            'crop_id' => $crop->id,
            'activity_type' => '',
            'scheduled_date' => 'tanggal-salah',
            'scheduled_time' => '99:99',
            'status' => 'status-salah',
        ])
        ->assertSessionHasErrors([
            'activity_type',
            'scheduled_date',
            'scheduled_time',
            'status',
        ]);

    $this->assertDatabaseCount('activities', 0);
});

it('redirects guests to the activity login page', function () {
    $this->get(route('activities.index'))
        ->assertRedirect(route('login'));
});
