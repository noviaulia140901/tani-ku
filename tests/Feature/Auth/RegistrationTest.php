<?php

test('registration page is unavailable', function () {
    $this->get('/register')
        ->assertNotFound();
});

test('visitors cannot create accounts through registration', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});
