<?php

use App\Models\Location;
use Database\Seeders\LocationSeeder;

test('the location seeder creates the initial municipal locations', function () {
    $this->seed(LocationSeeder::class);

    expect(Location::query()->orderBy('name')->pluck('name')->all())
        ->toBe(['Municipal Hall', 'Provincial Capitol']);
});

test('re-running the location seeder does not duplicate locations', function () {
    $this->seed(LocationSeeder::class);
    $this->seed(LocationSeeder::class);

    expect(Location::query()->count())->toBe(2);
});
