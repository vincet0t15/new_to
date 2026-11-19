<?php

namespace Database\Factories;

use App\Enums\TripTicketStatus;
use App\Models\TravelOrder;
use App\Models\TripTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripTicket>
 */
class TripTicketFactory extends Factory
{
    protected $model = TripTicket::class;

    public function definition(): array
    {
        $distance = fake()->randomFloat(2, 10, 300);

        return [
            'travel_order_id' => TravelOrder::factory(),
            'status' => TripTicketStatus::Issued,
            'vehicle_plate_no' => strtoupper(fake()->bothify('???-####')),
            'vehicle_type' => 'Van',
            'driver_id' => null,
            'fuel_type' => 'Diesel',
            'fuel_efficiency' => 10,
            'distance_km' => $distance,
            'round_trip' => true,
            'odometer_start' => fake()->randomFloat(2, 1000, 90000),
            'liters' => round(($distance * 2) / 10, 2),
            'ticket_no' => 'TT-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => TripTicketStatus::Completed,
            'completed_at' => now(),
            'odometer_end' => fake()->randomFloat(2, 90000, 120000),
            'fuel_consumed' => fake()->randomFloat(2, 10, 100),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => TripTicketStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => 'The trip was cancelled.',
        ]);
    }
}
