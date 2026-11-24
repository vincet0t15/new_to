<?php

use App\Models\TravelOrder;
use App\Models\User;
use App\Notifications\TravelOrderNotification;
use App\Support\NotificationUrl;
use Illuminate\Support\Str;

test('travel order notifications store relative app links but keep absolute email links', function () {
    $user = User::factory()->create();
    $order = TravelOrder::factory()->create(['created_by' => $user->id]);

    $notification = new TravelOrderNotification(
        $order,
        'Travel order routed to you',
        'TO-001 is waiting for your action.',
        'routed',
    );

    $url = $notification->toArray($user)['url'];

    expect($url)
        ->toBeString()
        ->toStartWith('/')
        ->not->toContain('http://')
        ->not->toContain('localhost');

    expect($notification->toMail($user)->actionUrl)
        ->toBeString()
        ->toStartWith('http')
        ->toContain((string) $order->id);
});

test('old absolute notification links redirect on the current host', function () {
    $user = User::factory()->create();
    $notice = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'data' => [
            'title' => 'Old notice',
            'message' => 'Written before host-safe links.',
            'kind' => 'routed',
            'url' => 'http://localhost:8000/approvals/travel-orders/4',
        ],
    ]);

    $response = $this->actingAs($user)
        ->withServerVariables([
            'HTTP_HOST' => '127.0.0.1:8000',
            'SERVER_PORT' => '8000',
        ])
        ->get(route('inbox.open', $notice->id));

    $response->assertRedirect()
        ->assertHeader('Location', '/approvals/travel-orders/4');
});

test('notification links never keep a foreign or script scheme', function () {
    $fallback = route('dashboard', [], false);

    expect(NotificationUrl::toLocalPath('https://example.test/elsewhere'))->toBe('/elsewhere')
        ->and(NotificationUrl::toLocalPath('//example.test/elsewhere'))->toBe($fallback)
        ->and(NotificationUrl::toLocalPath('javascript:alert(1)'))->toBe($fallback);
});
