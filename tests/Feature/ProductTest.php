<?php

use App\Models\User;

it('lists paginated products owned by the signed in user with readable stock and prices', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $user->products()->create([
        'name' => 'Bahan Pupuk Kompos',
        'category' => 'obat_lainnya',
        'unit' => 'kg',
        'stock' => '2.50',
        'minimum_stock' => '5.00',
        'purchase_price' => '12500.75',
        'description' => 'Pupuk organik untuk kebun.',
    ]);

    foreach (range(1, 10) as $productNumber) {
        $user->products()->create([
            'name' => sprintf('Produk %02d', $productNumber),
            'category' => 'pupuk',
            'unit' => 'kg',
        ]);
    }

    $otherUser->products()->create([
        'name' => 'Produk Rahasia',
        'category' => 'pupuk',
        'unit' => 'kg',
    ]);

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertSee('Bahan Pupuk Kompos')
        ->assertSee('Obat lainnya')
        ->assertSee('2,50 kg')
        ->assertSee('5,00 kg')
        ->assertSee('Rp 12.500,75')
        ->assertSee('Pupuk organik untuk kebun.')
        ->assertSee('Halaman 1 dari 2')
        ->assertDontSee('Produk 10')
        ->assertDontSee('Produk Rahasia');

    $this->get(route('products.index', ['page' => 2]))
        ->assertSee('Produk 10')
        ->assertSee('Halaman 2 dari 2')
        ->assertDontSee('Produk Rahasia');
});

it('shows an empty state with an action when the user has no products', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertSee('Belum ada produk')
        ->assertSee('Tambahkan produk pertanian untuk memantau stok dan biaya pembelian.')
        ->assertSee('Tambah Produk')
        ->assertSee(route('products.create'));
});

it('escapes product names and descriptions in the list', function () {
    $user = User::factory()->create();
    $user->products()->create([
        'name' => '<script>alert(1)</script>',
        'category' => 'pupuk',
        'unit' => 'kg',
        'description' => '<img src=x onerror=alert(2)>',
    ]);

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertSee('<script>alert(1)</script>')
        ->assertSee('<img src=x onerror=alert(2)>')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x onerror=alert(2)>', false);
});

it('creates a product for the signed in user and ignores a submitted owner id', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $data = [
        'user_id' => $otherUser->id,
        'name' => 'Pupuk NPK',
        'category' => 'pupuk',
        'unit' => 'karung',
        'stock' => '12.50',
        'minimum_stock' => '3.00',
        'purchase_price' => '175000.25',
        'description' => 'Pupuk untuk masa pertumbuhan.',
    ];

    $this->actingAs($user)
        ->get(route('products.create'))
        ->assertSee('Tambah Produk')
        ->assertSee('Batas stok minimum');

    $this->post(route('products.store'), $data)
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('status', 'Produk berhasil ditambahkan.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('products', [
        'user_id' => $user->id,
        'name' => 'Pupuk NPK',
        'category' => 'pupuk',
        'unit' => 'karung',
        'stock' => '12.50',
        'minimum_stock' => '3.00',
        'purchase_price' => '175000.25',
        'description' => 'Pupuk untuk masa pertumbuhan.',
    ]);

    $this->assertDatabaseMissing('products', [
        'user_id' => $otherUser->id,
        'name' => 'Pupuk NPK',
    ]);
});

it('rejects invalid product fields with Indonesian validation messages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('products.create'))
        ->post(route('products.store'), [
            'name' => '',
            'category' => 'alat_pertanian',
            'unit' => '',
            'stock' => '-1',
            'minimum_stock' => '100000000.00',
            'purchase_price' => '10000000000000.00',
        ])
        ->assertRedirect(route('products.create'))
        ->assertSessionHasErrors([
            'name' => 'Nama produk wajib diisi.',
            'category' => 'Kategori produk yang dipilih tidak valid.',
            'unit' => 'Satuan produk wajib diisi.',
            'stock' => 'Stok produk tidak boleh kurang dari 0.',
            'minimum_stock' => 'Batas stok minimum melebihi batas yang dapat disimpan.',
            'purchase_price' => 'Harga beli melebihi batas yang dapat disimpan.',
        ]);

    $this->from(route('products.create'))
        ->post(route('products.store'), [
            'name' => 'Produk Bernilai Negatif',
            'category' => 'pupuk',
            'unit' => 'kg',
            'stock' => '100000000.00',
            'minimum_stock' => '-1',
            'purchase_price' => '-1',
        ])
        ->assertRedirect(route('products.create'))
        ->assertSessionHasErrors([
            'stock' => 'Stok produk melebihi batas yang dapat disimpan.',
            'minimum_stock' => 'Batas stok minimum tidak boleh kurang dari 0.',
            'purchase_price' => 'Harga beli tidak boleh kurang dari 0.',
        ]);

    $this->from(route('products.create'))
        ->post(route('products.store'), [
            'name' => 'Produk Pecahan',
            'category' => 'pupuk',
            'unit' => 'kg',
            'stock' => '1.234',
            'minimum_stock' => '0',
            'purchase_price' => '100.001',
        ])
        ->assertRedirect(route('products.create'))
        ->assertSessionHasErrors([
            'stock' => 'Stok produk maksimal menggunakan dua angka di belakang koma.',
            'purchase_price' => 'Harga beli maksimal menggunakan dua angka di belakang koma.',
        ]);

    $this->assertDatabaseCount('products', 0);
});

it('updates owned products and hides products belonging to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = $user->products()->create([
        'name' => 'Pupuk Lama',
        'category' => 'pupuk',
        'unit' => 'kg',
    ]);
    $otherProduct = $otherUser->products()->create([
        'name' => 'Produk Pengguna Lain',
        'category' => 'pestisida',
        'unit' => 'liter',
    ]);
    $updatedData = [
        'user_id' => $otherUser->id,
        'name' => 'Pupuk Diperbarui',
        'category' => 'fungisida',
        'unit' => 'botol',
        'stock' => '4.25',
        'minimum_stock' => '1.00',
        'purchase_price' => '50000.00',
        'description' => 'Informasi terbaru.',
    ];

    $this->actingAs($user)
        ->get(route('products.edit', $product))
        ->assertSee('Edit Produk')
        ->assertSee('Pupuk Lama');

    $this->put(route('products.update', $product), $updatedData)
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('status', 'Produk berhasil diperbarui.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'user_id' => $user->id,
        'name' => 'Pupuk Diperbarui',
        'category' => 'fungisida',
        'unit' => 'botol',
        'stock' => '4.25',
        'purchase_price' => '50000.00',
    ]);

    $this->get(route('products.edit', $otherProduct))
        ->assertNotFound();

    $this->put(route('products.update', $otherProduct), $updatedData)
        ->assertNotFound();

    $this->delete(route('products.destroy', $otherProduct))
        ->assertNotFound();

    $this->assertDatabaseHas('products', [
        'id' => $otherProduct->id,
        'name' => 'Produk Pengguna Lain',
    ]);
});

it('deletes an owned product that is not referenced by an expense', function () {
    $user = User::factory()->create();
    $product = $user->products()->create([
        'name' => 'Pupuk yang Dihapus',
        'category' => 'pupuk',
        'unit' => 'kg',
    ]);

    $this->actingAs($user)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('status', 'Produk berhasil dihapus.');

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

it('prevents deleting a product referenced by an expense and preserves the relation', function () {
    $user = User::factory()->create();
    $product = $user->products()->create([
        'name' => 'Pupuk Terkait Biaya',
        'category' => 'pupuk',
        'unit' => 'kg',
    ]);
    $expense = $user->expenses()->create([
        'product_id' => $product->id,
        'category' => 'pupuk',
        'description' => 'Pembelian pupuk',
        'amount' => '25000.00',
        'expense_date' => '2026-10-10',
    ]);

    expect($expense->product->is($product))->toBeTrue()
        ->and($product->expenses()->whereKey($expense->id)->exists())->toBeTrue();

    $this->actingAs($user)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('error', 'Produk tidak dapat dihapus karena masih digunakan pada catatan pengeluaran.');

    $this->assertDatabaseHas('products', ['id' => $product->id]);
    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'product_id' => $product->id,
    ]);
});

it('redirects guests to the product login page', function () {
    $this->get(route('products.index'))
        ->assertRedirect(route('login'));
});
