<?php

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\TravelOrderDelivery;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array{ok: bool, errors: int, warnings: int, findings: array<int, array{code: string, severity: string}>}
 */
function integrityPayload(): array
{
    /** @var array{ok: bool, errors: int, warnings: int, findings: array<int, array{code: string, severity: string}>} $payload */
    $payload = json_decode(trim(Artisan::output()), true, 512, JSON_THROW_ON_ERROR);

    return $payload;
}

/**
 * @return array<int, string>
 */
function integrityCodes(): array
{
    return array_column(integrityPayload()['findings'], 'code');
}

it('passes a clean submitted order', function () {
    routingSubmit(routingChain());

    $exit = Artisan::call('travel-orders:check-integrity', ['--json' => true]);
    $payload = integrityPayload();

    expect($exit)->toBe(Command::SUCCESS)
        ->and($payload['ok'])->toBeTrue()
        ->and($payload['findings'])->toBe([]);
});

it('reports a recipient pointer that no longer matches an open holder', function () {
    $order = routingSubmit(routingChain());
    $order->deliveries()->firstOrFail()->update([
        'user_id' => User::factory()->create()->id,
    ]);

    $exit = Artisan::call('travel-orders:check-integrity', ['--json' => true]);

    expect($exit)->toBe(Command::FAILURE)
        ->and(integrityCodes())->toContain('recipient_not_holder');
});

it('reports stale open deliveries on closed orders', function () {
    $draft = routingDraft(routingChain());
    $user = User::factory()->create();
    TravelOrderDelivery::factory()->create([
        'travel_order_id' => $draft->id,
        'user_id' => $user->id,
    ]);
    $draft->update([
        'current_step_index' => 0,
        'recipient_user_id' => $user->id,
        'status' => TravelOrderStatus::Draft->value,
    ]);

    $exit = Artisan::call('travel-orders:check-integrity', ['--json' => true]);
    $codes = integrityCodes();

    expect($exit)->toBe(Command::FAILURE)
        ->and($codes)->toContain('stale_open_delivery')
        ->and($codes)->toContain('stale_route_pointer');
});

it('only fails on warnings when explicitly asked', function () {
    $chain = routingChain();
    $draft = routingDraft($chain);
    $user = User::factory()->create();
    $draft->update([
        'current_step_index' => 0,
        'recipient_user_id' => $user->id,
    ]);

    expect(Artisan::call('travel-orders:check-integrity', ['--json' => true]))
        ->toBe(Command::SUCCESS)
        ->and(integrityPayload()['warnings'])->toBeGreaterThan(0);

    expect(Artisan::call('travel-orders:check-integrity', [
        '--json' => true,
        '--fail-on-warning' => true,
    ]))->toBe(Command::FAILURE);
});

it('does not modify travel-order data', function () {
    $order = routingSubmit(routingChain());
    $before = $order->only([
        'status',
        'current_step_index',
        'recipient_user_id',
        'routed_steps',
        'updated_at',
    ]);

    Artisan::call('travel-orders:check-integrity');

    expect($order->refresh()->only(array_keys($before)))->toEqual($before)
        ->and(TravelOrder::query()->count())->toBe(1);
});
