<?php

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 */
function inboxNotice(User $user, array $overrides = []): DatabaseNotification
{
    /** @var DatabaseNotification $notice */
    $notice = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'data' => [
            'title' => 'Travel order routed to you',
            'message' => 'TO-001 is waiting for your action.',
            'kind' => 'routed',
            'url' => '/employee/travel-orders/1',
        ],
        'read_at' => null,
        ...$overrides,
    ]);

    return $notice;
}

test('the inbox lists the account notifications newest first', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    inboxNotice($user, [
        'created_at' => now()->subDay(),
        'data' => [
            'title' => 'Older notice',
            'message' => 'The older message.',
            'kind' => 'returned',
            'url' => '/employee/travel-orders/1',
        ],
    ]);
    inboxNotice($user, [
        'created_at' => now(),
        'data' => [
            'title' => 'Newer notice',
            'message' => 'The newer message.',
            'kind' => 'routed',
            'url' => '/employee/travel-orders/2',
        ],
    ]);
    inboxNotice($other);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/index')
            ->has('notificationItems.data', 2)
            ->where('notificationItems.data.0.title', 'Newer notice')
            ->where('notificationItems.data.1.title', 'Older notice')
            ->where('filters.status', 'all')
            ->where('counts.all', 2)
            ->where('counts.unread', 2)
            ->where('counts.read', 0)
        );
});

test('the inbox filters by unread and read', function () {
    $user = User::factory()->create();

    inboxNotice($user, [
        'data' => [
            'title' => 'Unread notice',
            'message' => 'Still waiting on you.',
            'kind' => 'routed',
            'url' => '/employee/travel-orders/1',
        ],
    ]);
    inboxNotice($user, [
        'read_at' => now(),
        'data' => [
            'title' => 'Read notice',
            'message' => 'Already opened.',
            'kind' => 'returned',
            'url' => '/employee/travel-orders/2',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('notifications.index', ['status' => 'unread']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notificationItems.data', 1)
            ->where('notificationItems.data.0.title', 'Unread notice')
            ->where('filters.status', 'unread')
            ->where('counts.all', 2)
            ->where('counts.unread', 1)
            ->where('counts.read', 1)
        );

    $this->actingAs($user)
        ->get(route('notifications.index', ['status' => 'read']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notificationItems.data', 1)
            ->where('notificationItems.data.0.title', 'Read notice')
            ->where('filters.status', 'read')
            ->where('counts.all', 2)
            ->where('counts.unread', 1)
            ->where('counts.read', 1)
        );
});

test('an unknown filter falls back to all', function () {
    $user = User::factory()->create();
    inboxNotice($user);

    $this->actingAs($user)
        ->get(route('notifications.index', ['status' => 'bogus']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', 'all')
            ->has('notificationItems.data', 1)
        );
});

test('guests are redirected from the inbox', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});
