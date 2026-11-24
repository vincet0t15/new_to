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
        ->get(route('inbox.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/index')
            ->has('notificationItems.data', 2)
            ->where('notificationItems.data.0.title', 'Newer notice')
            ->where('notificationItems.data.1.title', 'Older notice')
        );
});

test('the inbox normalizes stored absolute notification links', function () {
    $user = User::factory()->create();

    inboxNotice($user, [
        'data' => [
            'title' => 'Routed to approvals',
            'message' => 'Waiting for action.',
            'kind' => 'routed',
            'url' => 'http://localhost:8000/approvals/travel-orders/4?tab=details',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('inbox.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notificationItems.data', 1)
            ->where(
                'notificationItems.data.0.url',
                '/approvals/travel-orders/4?tab=details',
            )
        );
});

test('the inbox ignores status filters and lists every notice', function () {
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
        ->get(route('inbox.index', ['status' => 'unread']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notificationItems.data', 2)
            ->where('notificationItems.data.0.title', 'Unread notice')
            ->where('notificationItems.data.1.title', 'Read notice')
            ->missing('filters')
            ->missing('counts')
        );
});

test('guests are redirected from the inbox', function () {
    $this->get(route('inbox.index'))->assertRedirect(route('login'));
});
