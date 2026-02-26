<?php

test('registration screen is not available', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});

test('registration endpoint is not available', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertNotFound();
});
