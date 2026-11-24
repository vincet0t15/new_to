<?php

use App\Models\TravelOrder;
use App\Models\User;

test('an inaccessible employee document redirects to the notification inbox with a message', function () {
    $owner = User::factory()->create();
    $order = TravelOrder::factory()->create(['created_by' => $owner->id]);
    $viewer = User::factory()->create();

    $response = $this->actingAs($viewer)
        ->get(route('travel-orders.show', $order, false).'?from=notification');

    $response->assertRedirect()
        ->assertHeader('Location', '/inbox')
        ->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash(
            'toast.message',
            'This document is no longer accessible to your account.',
        );
});

test('an inaccessible approval document redirects to the notification inbox with a message', function () {
    $owner = User::factory()->create();
    $order = TravelOrder::factory()->create(['created_by' => $owner->id]);
    $viewer = User::factory()->create();

    $response = $this->actingAs($viewer)
        ->get(route('approvals.travel-orders.show', $order, false).'?from=notification');

    $response->assertRedirect()
        ->assertHeader('Location', '/inbox')
        ->assertInertiaFlash('toast.type', 'error');
});

test('a missing notification document redirects to the notification inbox', function () {
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get(route('approvals.travel-orders.show', 999999, false).'?from=notification')
        ->assertRedirect()
        ->assertHeader('Location', '/inbox')
        ->assertInertiaFlash('toast.type', 'error');
});

test('a direct denied document visit keeps its normal forbidden response', function () {
    $owner = User::factory()->create();
    $order = TravelOrder::factory()->create(['created_by' => $owner->id]);
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get(route('approvals.travel-orders.show', $order))
        ->assertForbidden()
        ->assertInertiaFlashMissing('toast');
});

test('unrelated portal denials still return a forbidden response', function () {
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get(route('approvals.inbox'))
        ->assertForbidden();
});
