<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates an account with a hidden validated password', function () {
    $this->artisan('users:create')
        ->expectsQuestion('Nama pengguna', 'Ayah')
        ->expectsQuestion('Email pengguna', 'ayah@example.com')
        ->expectsQuestion('Password', 'SecurePassword123!')
        ->expectsQuestion('Konfirmasi password', 'SecurePassword123!')
        ->expectsOutput('Akun pengguna berhasil dibuat.')
        ->doesntExpectOutputToContain('SecurePassword123!')
        ->assertSuccessful();

    $user = User::query()->sole();

    expect($user->name)->toBe('Ayah')
        ->and($user->email)->toBe('ayah@example.com')
        ->and(Hash::check('SecurePassword123!', $user->password))->toBeTrue();
});

it('rejects an account with a duplicate email before asking for a password', function () {
    User::factory()->create(['email' => 'ayah@example.com']);

    $this->artisan('users:create')
        ->expectsQuestion('Nama pengguna', 'Ayah')
        ->expectsQuestion('Email pengguna', 'ayah@example.com')
        ->expectsOutputToContain('email')
        ->assertFailed();

    $this->assertDatabaseCount('users', 1);
});

it('does not create an account when password confirmation fails', function () {
    $this->artisan('users:create')
        ->expectsQuestion('Nama pengguna', 'Ayah')
        ->expectsQuestion('Email pengguna', 'ayah@example.com')
        ->expectsQuestion('Password', 'SecurePassword123!')
        ->expectsQuestion('Konfirmasi password', 'DifferentPassword123!')
        ->expectsOutputToContain('confirmation')
        ->assertFailed();

    $this->assertDatabaseCount('users', 0);
});

it('does not create an account with a weak password', function () {
    $this->artisan('users:create')
        ->expectsQuestion('Nama pengguna', 'Ayah')
        ->expectsQuestion('Email pengguna', 'ayah@example.com')
        ->expectsQuestion('Password', 'weak')
        ->expectsQuestion('Konfirmasi password', 'weak')
        ->expectsOutputToContain('password')
        ->assertFailed();

    $this->assertDatabaseCount('users', 0);
});
