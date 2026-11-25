<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('employee.dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('inactive accounts cannot authenticate', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'password',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('a deactivated account is signed out on the next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect();

    $user->update(['is_active' => false]);

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $this->assertGuest();
});

test('users can not authenticate with an unknown username', function () {
    User::factory()->create();

    $this->post(route('login.store'), [
        'username' => 'does-not-exist',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('users can not authenticate with their email address', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->username, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
