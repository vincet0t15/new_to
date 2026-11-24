<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('an account with a temporary password is sent to security settings', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->get(route('employee.dashboard'))
        ->assertRedirect(route('security.edit'));
});

test('changing the password clears the forced-change flag', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/security')
            ->where('requiresPasswordChange', true)
        );

    $this->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect(route('security.edit'));

    expect($user->refresh()->must_change_password)->toBeFalse();

    $this->actingAs($user)
        ->get(route('employee.dashboard'))
        ->assertOk();
});

test('an ordinary account is not interrupted by the forced-change middleware', function () {
    $user = User::factory()->create(['must_change_password' => false]);

    $this->actingAs($user)
        ->get(route('employee.dashboard'))
        ->assertOk();
});
