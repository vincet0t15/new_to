<?php

namespace Database\Factories;

use App\Models\TravelOrder;
use App\Models\TravelOrderAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelOrderAction>
 */
class TravelOrderActionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'travel_order_id' => TravelOrder::factory(),
            'step_index' => null,
            'step_label' => null,
            'event' => 'submitted',
            'actor_user_id' => User::factory(),
            'remarks' => null,
            'meta' => null,
        ];
    }
}
