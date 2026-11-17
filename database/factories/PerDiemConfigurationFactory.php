<?php
namespace Database\Factories;
use App\Models\PerDiemConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;
class PerDiemConfigurationFactory extends Factory {
    protected $model = PerDiemConfiguration::class;
    public function definition(): array { return ['name'=>fake()->words(2,true),'effective_from'=>now()->toDateString(),'effective_until'=>null,'daily_rate'=>fake()->randomFloat(2,100,500),'is_active'=>true]; }
}
