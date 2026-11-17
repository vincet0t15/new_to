<?php
namespace Database\Factories;
use App\Models\TravelClassification;
use Illuminate\Database\Eloquent\Factories\Factory;
class TravelClassificationFactory extends Factory {
    protected $model = TravelClassification::class;
    public function definition(): array { return ['name'=>fake()->unique()->words(2,true),'description'=>fake()->sentence(),'is_active'=>true]; }
}
