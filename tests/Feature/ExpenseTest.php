<?php

use App\Models\Expense;
use App\Models\User;

it('lists only the signed in users expenses with related details', function () {
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
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);
    $product = $user->products()->create([
        'name' => 'Pupuk Kompos',
        'category' => 'pupuk',
        'unit' => 'kg',
    ]);
    $user->expenses()->create([
        'crop_id' => $crop->id,
        'product_id' => $product->id,
        'category' => 'bibit',
        'description' => 'Pembelian benih tomat',
        'amount' => '50000.25',
        'expense_date' => '2026-10-10',
    ]);
    $otherUser->expenses()->create([
        'category' => 'lainnya',
        'description' => 'Biaya rahasia pengguna lain',
        'amount' => 900000,
        'expense_date' => '2026-10-10',
    ]);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertSee('Pembelian benih tomat')
        ->assertSee('Bibit')
        ->assertSee('10/10/2026')
        ->assertSee('Rp 50.000,25')
        ->assertSee('Tomat Lokal')
        ->assertSee('Kebun Sendiri')
        ->assertSee('Pupuk Kompos')
        ->assertDontSee('Biaya rahasia pengguna lain')
        ->assertDontSee('900.000');
});

it('shows the empty state when the user has no expenses', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertSee('Belum ada pengeluaran')
        ->assertSee('Tambahkan catatan pengeluaran untuk memantau biaya usaha tani Anda.')
        ->assertSee('Tambah Pengeluaran');
});

it('escapes expense category and description in the list', function () {
    $user = User::factory()->create();
    $user->expenses()->create([
        'category' => '<script>alert(1)</script>',
        'description' => '<script>alert(2)</script>',
        'amount' => 1000,
        'expense_date' => '2026-10-10',
    ]);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertSee('<script>alert(1)</script>')
        ->assertSee('<script>alert(2)</script>')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<script>alert(2)</script>', false);
});

it('creates an expense with owned crop and product and ignores a submitted owner id', function () {
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
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);
    $product = $user->products()->create([
        'name' => 'Pupuk Kompos',
        'category' => 'pupuk',
        'unit' => 'kg',
    ]);
    $data = [
        'user_id' => $otherUser->id,
        'crop_id' => $crop->id,
        'product_id' => $product->id,
        'category' => 'pupuk',
        'description' => 'Pemupukan awal',
        'amount' => '12500.50',
        'expense_date' => '2026-10-10',
    ];

    $this->actingAs($user)
        ->get(route('expenses.create'))
        ->assertSee('Tomat Lokal - Kebun Sendiri')
        ->assertSee('Pupuk Kompos');

    $this->post(route('expenses.store'), $data)
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('status', 'Pengeluaran berhasil ditambahkan.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('expenses', [
        'user_id' => $user->id,
        'crop_id' => $crop->id,
        'product_id' => $product->id,
        'category' => 'pupuk',
        'description' => 'Pemupukan awal',
    ]);

    $expense = Expense::query()->where('description', 'Pemupukan awal')->firstOrFail();
    expect($expense->amount)->toBe('12500.50')
        ->and($expense->expense_date->toDateString())->toBe('2026-10-10');

    $this->get(route('expenses.index'))
        ->assertSee('Pemupukan awal')
        ->assertSee('Rp 12.500,50');
});

it('rejects crops and products owned by another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherLand = $otherUser->lands()->create([
        'name' => 'Kebun Orang Lain',
        'location' => 'Desa Sebelah',
        'area' => 1,
        'status' => 'aktif',
    ]);
    $otherCrop = $otherLand->crops()->create([
        'name' => 'Tanaman Rahasia',
        'planting_date' => '2026-10-01',
        'status' => 'ditanam',
    ]);
    $otherProduct = $otherUser->products()->create([
        'name' => 'Produk Rahasia',
        'category' => 'pupuk',
        'unit' => 'kg',
    ]);
    $data = [
        'category' => 'pupuk',
        'description' => 'Pengeluaran tidak valid',
        'amount' => '1000.00',
        'expense_date' => '2026-10-10',
    ];

    $this->actingAs($user)
        ->get(route('expenses.create'))
        ->assertDontSee('Tanaman Rahasia')
        ->assertDontSee('Produk Rahasia');

    $this->from(route('expenses.create'))
        ->post(route('expenses.store'), [...$data, 'crop_id' => $otherCrop->id])
        ->assertRedirect(route('expenses.create'))
        ->assertSessionHasErrors([
            'crop_id' => 'Tanaman yang dipilih tidak tersedia untuk akun Anda.',
        ]);

    $this->from(route('expenses.create'))
        ->post(route('expenses.store'), [...$data, 'product_id' => $otherProduct->id])
        ->assertRedirect(route('expenses.create'))
        ->assertSessionHasErrors([
            'product_id' => 'Produk yang dipilih tidak tersedia untuk akun Anda.',
        ]);

    $this->assertDatabaseCount('expenses', 0);
});

it('updates and deletes owned expenses while hiding another users expense', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $expense = $user->expenses()->create([
        'category' => 'bibit',
        'description' => 'Biaya lama',
        'amount' => '10000.00',
        'expense_date' => '2026-10-01',
    ]);
    $otherExpense = $otherUser->expenses()->create([
        'category' => 'lainnya',
        'description' => 'Biaya rahasia',
        'amount' => 900000,
        'expense_date' => '2026-10-01',
    ]);
    $updatedData = [
        'user_id' => $otherUser->id,
        'category' => 'transportasi',
        'description' => 'Biaya angkut hasil panen',
        'amount' => '25000.75',
        'expense_date' => '2026-10-11',
    ];

    $this->actingAs($user)
        ->get(route('expenses.edit', $expense))
        ->assertSee('Edit Pengeluaran')
        ->assertSee('Biaya lama');

    $this->put(route('expenses.update', $expense), $updatedData)
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('status', 'Pengeluaran berhasil diperbarui.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'user_id' => $user->id,
        'category' => 'transportasi',
        'description' => 'Biaya angkut hasil panen',
    ]);

    $expense->refresh();
    expect($expense->amount)->toBe('25000.75')
        ->and($expense->expense_date->toDateString())->toBe('2026-10-11');

    $this->get(route('expenses.edit', $otherExpense))
        ->assertNotFound();

    $this->put(route('expenses.update', $otherExpense), $updatedData)
        ->assertNotFound();

    $this->delete(route('expenses.destroy', $otherExpense))
        ->assertNotFound();

    $this->assertDatabaseHas('expenses', [
        'id' => $otherExpense->id,
        'description' => 'Biaya rahasia',
    ]);

    $this->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('status', 'Pengeluaran berhasil dihapus.');

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});

it('rejects invalid expense fields with Indonesian messages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('expenses.create'))
        ->post(route('expenses.store'), [
            'category' => '',
            'description' => '',
            'amount' => '0.001',
            'expense_date' => 'tanggal-salah',
        ])
        ->assertRedirect(route('expenses.create'))
        ->assertSessionHasErrors([
            'category' => 'Kategori pengeluaran wajib diisi.',
            'description' => 'Deskripsi pengeluaran wajib diisi.',
            'amount' => 'Jumlah biaya maksimal menggunakan dua angka di belakang koma.',
            'expense_date' => 'Tanggal pengeluaran harus menggunakan format yang valid.',
        ]);

    $this->from(route('expenses.create'))
        ->post(route('expenses.store'), [
            'category' => 'pupuk',
            'description' => 'Biaya dengan nominal tidak valid',
            'amount' => '10000000000000.00',
            'expense_date' => '2026-10-10',
        ])
        ->assertRedirect(route('expenses.create'))
        ->assertSessionHasErrors([
            'amount' => 'Jumlah biaya melebihi batas yang dapat disimpan.',
        ]);

    $this->assertDatabaseCount('expenses', 0);
});

it('paginates the signed in users expense list', function () {
    $user = User::factory()->create();

    foreach (range(1, 11) as $expenseNumber) {
        $user->expenses()->create([
            'category' => 'operasional',
            'description' => 'Pengeluaran '.$expenseNumber,
            'amount' => 1000,
            'expense_date' => '2026-10-10',
        ]);
    }

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertSee('Halaman 1 dari 2')
        ->assertSee('Berikutnya')
        ->assertSee('Sebelumnya');

    $this->get(route('expenses.index', ['page' => 2]))
        ->assertSee('Halaman 2 dari 2')
        ->assertSee('Berikutnya')
        ->assertSee('Sebelumnya');
});

it('redirects guests to the expense login page', function () {
    $this->get(route('expenses.index'))
        ->assertRedirect(route('login'));
});
