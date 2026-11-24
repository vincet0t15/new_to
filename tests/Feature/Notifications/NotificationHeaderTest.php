<?php

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 */
function headerNotice(User $user, array $overrides = []): DatabaseNotification
{
    /** @var DatabaseNotification $notice */
    $notice = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'data' => [
            'title' => 'Travel order routed to you',
            'message' => 'TO-001 is waiting for your action.',
            'url' => '/employee/travel-orders/1',
        ],
        'read_at' => null,
        ...$overrides,
    ]);

    return $notice;
}

test('opening a notification marks it read and redirects to its screen', function () {
    $user = User::factory()->create();
    $notice = headerNotice($user);

    $this->actingAs($user)
        ->get(route('inbox.open', $notice->id))
        ->assertRedirect('/employee/travel-orders/1');

    expect($notice->fresh()->read_at)->not->toBeNull();
});

test('an account cannot open another account notification', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $notice = headerNotice($owner);

    $this->actingAs($other)
        ->get(route('inbox.open', $notice->id))
        ->assertNotFound();

    expect($notice->fresh()->read_at)->toBeNull();
});

test('mark all read clears every unread notification of the account', function () {
    $user = User::factory()->create();
    headerNotice($user);
    headerNotice($user);

    $this->actingAs($user)
        ->post(route('inbox.read-all'))
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('the header receives the latest notifications and unread count', function () {
    $user = User::factory()->create();
    headerNotice($user);

    $this->actingAs($user)
        ->get(route('employee.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('portals/employee/dashboard')
            ->where('unreadNotificationsCount', 1)
            ->has('notifications', 1)
            ->where('notifications.0.title', 'Travel order routed to you')
        );
});

test('the header normalizes stored absolute notification links', function () {
    $user = User::factory()->create();
    headerNotice($user, [
        'data' => [
            'title' => 'Routed to approvals',
            'message' => 'Waiting for action.',
            'url' => 'http://localhost:8000/approvals/travel-orders/4',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('employee.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications', 1)
            ->where('notifications.0.url', '/approvals/travel-orders/4')
        );
});
