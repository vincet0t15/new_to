<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'testuser',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('employee.dashboard', absolute: false));

    $user = User::query()->where('username', 'testuser')->sole();

    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBeNull();
});

test('registered usernames are lowercased', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'TestUser',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::query()->where('username', 'testuser')->exists())->toBeTrue();
});

test('username is required when registering', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => '',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('username must be unique when registering', function () {
    User::factory()->create(['username' => 'taken_user']);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'Taken_User',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('the password must be confirmed when registering', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'testuser',
        'password' => 'password',
        'password_confirmation' => 'something-else',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
});
