<?php

use App\Models\Crop;
use App\Models\Note;
use App\Models\User;

function createNoteTestCrop(User $user, string $cropName, string $landName): Crop
{
    $land = $user->lands()->create([
        'name' => $landName,
        'location' => 'Desa Makmur',
        'area' => 1,
        'status' => 'aktif',
    ]);

    return $land->crops()->create([
        'name' => $cropName,
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);
}

it('lists paginated notes with crop and land details while hiding other users notes', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $crop = createNoteTestCrop($user, 'Tomat Lokal', 'Kebun Sendiri');
    $otherCrop = createNoteTestCrop($otherUser, 'Tanaman Rahasia', 'Lahan Rahasia');

    foreach (range(1, 11) as $noteNumber) {
        $crop->notes()->create([
            'title' => sprintf('Pengamatan %02d', $noteNumber),
            'content' => 'Isi pengamatan '.$noteNumber,
            'note_date' => '2026-10-'.sprintf('%02d', $noteNumber),
        ]);
    }

    $otherCrop->notes()->create([
        'title' => 'Catatan Rahasia',
        'content' => 'Isi milik pengguna lain',
        'note_date' => '2026-10-17',
    ]);

    $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertSee('Pengamatan 11')
        ->assertSee('11/10/2026')
        ->assertSee('Tanaman:')
        ->assertSee('Tomat Lokal')
        ->assertSee('Lahan:')
        ->assertSee('Kebun Sendiri')
        ->assertSee('Halaman 1 dari 2')
        ->assertSee('Catatan')
        ->assertDontSee('Pengamatan 01')
        ->assertDontSee('Catatan Rahasia')
        ->assertDontSee('Lahan Rahasia');

    $this->get(route('notes.index', ['page' => 2]))
        ->assertSee('Pengamatan 01')
        ->assertSee('Halaman 2 dari 2')
        ->assertDontSee('Catatan Rahasia');
});

it('shows an empty state and an action when the user has no notes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertSee('Belum ada catatan')
        ->assertSee('Tambahkan catatan untuk menyimpan pengamatan perkembangan tanaman Anda.')
        ->assertSee('Tambah Catatan')
        ->assertSee(route('notes.create'));
});

it('shows only the signed in users crops in the note form', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    createNoteTestCrop($user, 'Tomat Sendiri', 'Kebun Sendiri');
    createNoteTestCrop($otherUser, 'Tomat Orang Lain', 'Kebun Orang Lain');

    $this->actingAs($user)
        ->get(route('notes.create'))
        ->assertSee('Tomat Sendiri - Kebun Sendiri')
        ->assertDontSee('Tomat Orang Lain')
        ->assertDontSee('Kebun Orang Lain')
        ->assertSee('Tanggal catatan')
        ->assertSee('Isi catatan');
});

it('creates a note through an owned crop and ignores a submitted owner id', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $crop = createNoteTestCrop($user, 'Cabai Rawit', 'Kebun Cabai');

    $this->actingAs($user)
        ->get(route('notes.create'))
        ->assertSee('Tambah Catatan');

    $this->post(route('notes.store'), [
        'user_id' => $otherUser->id,
        'crop_id' => $crop->id,
        'title' => 'Pertumbuhan Minggu Pertama',
        'content' => 'Daun tumbuh sehat setelah pemupukan.',
        'note_date' => '2026-10-10',
    ])
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('status', 'Catatan berhasil ditambahkan.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('notes', [
        'crop_id' => $crop->id,
        'title' => 'Pertumbuhan Minggu Pertama',
        'content' => 'Daun tumbuh sehat setelah pemupukan.',
    ]);

    $note = Note::query()->where('title', 'Pertumbuhan Minggu Pertama')->firstOrFail();
    expect($note->note_date->toDateString())->toBe('2026-10-10');

    $this->assertDatabaseCount('notes', 1);
});

it('rejects missing and invalid note fields with Indonesian messages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('notes.create'))
        ->post(route('notes.store'), [
            'crop_id' => '',
            'title' => '',
            'content' => '',
            'note_date' => 'tanggal-salah',
        ])
        ->assertRedirect(route('notes.create'))
        ->assertSessionHasErrors([
            'crop_id' => 'Pilih tanaman terlebih dahulu.',
            'title' => 'Judul catatan wajib diisi.',
            'content' => 'Isi catatan wajib diisi.',
            'note_date' => 'Tanggal catatan harus menggunakan format yang valid.',
        ]);

    $crop = createNoteTestCrop($user, 'Tanaman Uji', 'Lahan Uji');

    $this->from(route('notes.create'))
        ->post(route('notes.store'), [
            'crop_id' => $crop->id,
            'title' => str_repeat('a', 256),
            'content' => str_repeat('b', 65536),
            'note_date' => '2026-10-10',
        ])
        ->assertRedirect(route('notes.create'))
        ->assertSessionHasErrors([
            'title' => 'Judul catatan maksimal 255 karakter.',
            'content' => 'Isi catatan maksimal 65.535 karakter.',
        ]);

    $this->assertDatabaseCount('notes', 0);
});

it('rejects a crop owned by another user when creating or updating a note', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $crop = createNoteTestCrop($user, 'Tanaman Sendiri', 'Kebun Sendiri');
    $otherCrop = createNoteTestCrop($otherUser, 'Tanaman Orang Lain', 'Kebun Orang Lain');
    $note = $crop->notes()->create([
        'title' => 'Catatan Milik Saya',
        'content' => 'Catatan awal.',
        'note_date' => '2026-10-10',
    ]);
    $data = [
        'crop_id' => $otherCrop->id,
        'title' => 'Catatan Tidak Sah',
        'content' => 'Tidak boleh terkait dengan tanaman orang lain.',
        'note_date' => '2026-10-11',
    ];

    $this->actingAs($user)
        ->from(route('notes.create'))
        ->post(route('notes.store'), $data)
        ->assertRedirect(route('notes.create'))
        ->assertSessionHasErrors([
            'crop_id' => 'Tanaman yang dipilih tidak tersedia untuk akun Anda.',
        ]);

    $this->from(route('notes.edit', $note))
        ->put(route('notes.update', $note), $data)
        ->assertRedirect(route('notes.edit', $note))
        ->assertSessionHasErrors([
            'crop_id' => 'Tanaman yang dipilih tidak tersedia untuk akun Anda.',
        ]);

    $this->assertDatabaseHas('notes', [
        'id' => $note->id,
        'crop_id' => $crop->id,
        'title' => 'Catatan Milik Saya',
    ]);

    $this->assertDatabaseCount('notes', 1);
});

it('updates an owned note and hides notes belonging to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $crop = createNoteTestCrop($user, 'Tanaman Pertama', 'Kebun Pertama');
    $secondCrop = createNoteTestCrop($user, 'Tanaman Kedua', 'Kebun Kedua');
    $otherCrop = createNoteTestCrop($otherUser, 'Tanaman Rahasia', 'Kebun Rahasia');
    $note = $crop->notes()->create([
        'title' => 'Catatan Lama',
        'content' => 'Isi lama.',
        'note_date' => '2026-10-10',
    ]);
    $otherNote = $otherCrop->notes()->create([
        'title' => 'Catatan Pengguna Lain',
        'content' => 'Isi rahasia.',
        'note_date' => '2026-10-10',
    ]);
    $updatedData = [
        'user_id' => $otherUser->id,
        'crop_id' => $secondCrop->id,
        'title' => 'Catatan Diperbarui',
        'content' => 'Pertumbuhan semakin baik.',
        'note_date' => '2026-10-12',
    ];

    $this->actingAs($user)
        ->get(route('notes.edit', $note))
        ->assertSee('Edit Catatan')
        ->assertSee('Catatan Lama')
        ->assertSee('Tanaman Pertama - Kebun Pertama');

    $this->put(route('notes.update', $note), $updatedData)
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('status', 'Catatan berhasil diperbarui.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('notes', [
        'id' => $note->id,
        'crop_id' => $secondCrop->id,
        'title' => 'Catatan Diperbarui',
        'content' => 'Pertumbuhan semakin baik.',
    ]);

    $note->refresh();
    expect($note->note_date->toDateString())->toBe('2026-10-12');

    $this->get(route('notes.edit', $otherNote))
        ->assertNotFound();

    $this->put(route('notes.update', $otherNote), $updatedData)
        ->assertNotFound();

    $this->delete(route('notes.destroy', $otherNote))
        ->assertNotFound();

    $this->assertDatabaseHas('notes', [
        'id' => $otherNote->id,
        'title' => 'Catatan Pengguna Lain',
    ]);
});

it('deletes an owned note without deleting its crop', function () {
    $user = User::factory()->create();
    $crop = createNoteTestCrop($user, 'Tanaman Tetap Ada', 'Kebun Tetap Ada');
    $note = $crop->notes()->create([
        'title' => 'Catatan untuk Dihapus',
        'content' => 'Catatan ini akan dihapus.',
        'note_date' => '2026-10-10',
    ]);

    $this->actingAs($user)
        ->delete(route('notes.destroy', $note))
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('status', 'Catatan berhasil dihapus.');

    $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    $this->assertModelExists($crop);
});

it('escapes note titles and content in the list', function () {
    $user = User::factory()->create();
    $crop = createNoteTestCrop($user, 'Tomat Aman', 'Kebun Aman');
    $crop->notes()->create([
        'title' => '<script>alert(1)</script>',
        'content' => '<img src=x onerror=alert(2)>',
        'note_date' => '2026-10-10',
    ]);

    $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertSee('<script>alert(1)</script>')
        ->assertSee('<img src=x onerror=alert(2)>')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x onerror=alert(2)>', false);
});

it('redirects guests to the note login page', function () {
    $this->get(route('notes.index'))
        ->assertRedirect(route('login'));
});
