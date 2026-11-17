<?php
namespace Database\Factories;
use App\Models\FundSource;
use Illuminate\Database\Eloquent\Factories\Factory;
class FundSourceFactory extends Factory {
    protected $model = FundSource::class;
    public function definition(): array { return ['name'=>fake()->unique()->words(2,true),'description'=>fake()->sentence(),'is_active'=>true]; }
}
