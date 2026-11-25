<?php

use App\Models\EmploymentType;
use App\Models\Office;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

/**
 * A complete public registration payload, mirroring employee create.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Test',
        'middle_name' => '',
        'last_name' => 'User',
        'suffix' => '',
        'position_id' => Position::factory()->create()->id,
        'office_id' => Office::factory()->create()->id,
        'employment_type_id' => EmploymentType::factory()->create()->id,
        'contact_number' => '09171234567',
        'monthly_salary' => '25000.00',
        'username' => 'testuser',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}

test('registration screen can be rendered', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/register')
            ->has('passwordRules')
            ->has('offices')
            ->has('positions')
            ->has('employmentTypes')
        );
});

test('new users can register with an employee record', function () {
    $payload = registrationPayload();

    $response = $this->post(route('register.store'), $payload);

    $this->assertAuthenticated();
    $response->assertRedirect(route('employee.dashboard', absolute: false));

    $user = User::query()->where('username', 'testuser')->sole();
    $employee = $user->employee;

    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBeNull()
        ->and($user->is_active)->toBeTrue()
        ->and($user->roles)->toHaveCount(0)
        ->and($employee->first_name)->toBe('Test')
        ->and($employee->last_name)->toBe('User')
        ->and($employee->position_id)->toBe($payload['position_id'])
        ->and($employee->office_id)->toBe($payload['office_id'])
        ->and($employee->employment_type_id)->toBe($payload['employment_type_id'])
        ->and($employee->contact_number)->toBe('09171234567')
        ->and((float) $employee->monthly_salary)->toBe(25000.0)
        ->and($employee->is_active)->toBeTrue()
        ->and($employee->image_path)->toBeNull();
});

test('an inactive employee and account can register', function () {
    $this->post(route('register.store'), registrationPayload([
        'username' => 'inactiveuser',
        'is_active' => false,
    ]))->assertRedirect();

    $user = User::query()->where('username', 'inactiveuser')->sole();

    expect($user->is_active)->toBeFalse()
        ->and($user->employee->is_active)->toBeFalse();
});

test('registration requires the employee fields', function () {
    $this->post(route('register.store'), [
        'username' => 'incomplete',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors([
        'first_name',
        'last_name',
        'position_id',
        'office_id',
        'employment_type_id',
    ]);

    $this->assertGuest();
});

test('registered usernames are lowercased', function () {
    $this->post(route('register.store'), registrationPayload([
        'username' => 'TestUser',
    ]));

    expect(User::query()->where('username', 'testuser')->exists())->toBeTrue();
});

test('username is required when registering', function () {
    $this->post(route('register.store'), registrationPayload([
        'username' => '',
    ]))->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('username must be unique when registering', function () {
    User::factory()->create(['username' => 'taken_user']);

    $this->post(route('register.store'), registrationPayload([
        'username' => 'Taken_User',
    ]))->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('the password must be confirmed when registering', function () {
    $this->post(route('register.store'), registrationPayload([
        'password_confirmation' => 'something-else',
    ]))->assertSessionHasErrors('password');

    $this->assertGuest();
});

test('registration is rate limited per ip address', function () {
    $key = 'registration:127.0.0.1';

    RateLimiter::clear($key);

    try {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('register.store'), registrationPayload([
                'username' => 'ratelimited'.$attempt,
            ]))->assertRedirect();
        }

        $this->post(route('register.store'), registrationPayload([
            'username' => 'ratelimited6',
        ]))->assertStatus(429);

        expect(User::query()->where('username', 'ratelimited6')->exists())->toBeFalse();
    } finally {
        RateLimiter::clear($key);
    }
});
