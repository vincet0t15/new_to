<?php

namespace Database\Factories;

use App\Models\TravelOrder;
use App\Models\TravelOrderDelivery;
use App\Models\User;
use App\Models\WorkflowStepType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelOrderDelivery>
 */
class TravelOrderDeliveryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'travel_order_id' => TravelOrder::factory(),
            'step_index' => 0,
            'step_type_id' => WorkflowStepType::factory(),
            'user_id' => User::factory(),
            'status' => 'pending',
            'received_at' => null,
            'acted_at' => null,
            'acted_via' => null,
        ];
    }
}
