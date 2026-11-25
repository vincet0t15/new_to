<?php

use App\Enums\TravelOrderStatus;
use App\Services\TravelOrderApprovalService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('submission assigns a filing number in order for the year', function () {
    $chain = routingChain();
    $year = now()->year;

    $first = routingSubmit($chain);
    $second = routingSubmit($chain);

    expect($first->to_number)->toBe("TO-{$year}-0001")
        ->and($second->to_number)->toBe("TO-{$year}-0002");
});

test('hrmo acknowledgement assigns a series number counted separately', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);
    $year = now()->year;

    $first = routingSubmit($chain);
    $second = routingSubmit($chain);

    $first = $service->approve($first, $chain['headUser']);
    $first = $service->approve($first, $chain['budgetUser']);
    $second = $service->approve($second, $chain['headUser']);
    $second = $service->approve($second, $chain['budgetUser']);

    $first = $service->acknowledge($first, $chain['hrmoUser']);
    $second = $service->acknowledge($second, $chain['hrmoUser']);

    expect($first->to_number)->toBe("TO-{$year}-0001")
        ->and($first->control_no)->toBe("HRMO-TO-{$year}-0001")
        ->and($second->to_number)->toBe("TO-{$year}-0002")
        ->and($second->control_no)->toBe("HRMO-TO-{$year}-0002");
});

test('hrmo signature approval assigns the series number too', function () {
    $chain = routingChain();
    $chain['hrmoType']->update(['is_signatory' => true]);

    $service = app(TravelOrderApprovalService::class);

    $order = routingSubmit($chain);
    $order = $service->approve($order, $chain['headUser']);
    $order = $service->approve($order, $chain['budgetUser']);
    $order = $service->approve($order, $chain['hrmoUser']);

    expect($order->control_no)->toBe('HRMO-TO-'.now()->year.'-0001')
        ->and($order->status)->toBe(TravelOrderStatus::Approved);
});

test('an existing series number is not overwritten', function () {
    $chain = routingChain();
    $service = app(TravelOrderApprovalService::class);

    $order = routingSubmit($chain);
    $order = $service->approve($order, $chain['headUser']);
    $order = $service->approve($order, $chain['budgetUser']);

    $order->forceFill(['control_no' => 'LEGACY-SERIES-1'])->save();

    $order = $service->acknowledge($order->refresh(), $chain['hrmoUser']);

    expect($order->control_no)->toBe('LEGACY-SERIES-1');
});

test('filing numbers restart each year', function () {
    $chain = routingChain();

    $this->travelTo('2026-12-31 23:00:00');
    $first = routingSubmit($chain);

    $this->travelTo('2027-01-01 00:30:00');
    $second = routingSubmit($chain);

    expect($first->to_number)->toBe('TO-2026-0001')
        ->and($second->to_number)->toBe('TO-2027-0001');

    $this->travelBack();
});
