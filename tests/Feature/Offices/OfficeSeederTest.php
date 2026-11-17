<?php

use App\Models\Office;
use Database\Seeders\OfficeSeeder;

test('the office seeder creates the municipal directory', function () {
    $this->seed(OfficeSeeder::class);

    expect(Office::query()->count())->toBe(21)
        ->and(Office::query()->where('office_name', 'Commission on Elections')->value('office_code'))->toBe('COMELEC')
        ->and(Office::query()->where('office_name', 'Office of the Municipal Treasurer')->value('office_code'))->toBe('TO')
        ->and(Office::query()->where('office_name', 'Office of the Sangguniang Bayan')->value('office_code'))->toBe('SBO');
});

test('re-running the office seeder does not duplicate offices', function () {
    $this->seed(OfficeSeeder::class);
    $this->seed(OfficeSeeder::class);

    expect(Office::query()->count())->toBe(21);
});

test('the office seeder folds a legacy office name into the official one', function () {
    $legacy = Office::factory()->create([
        'office_name' => "Mayor's Office",
        'office_code' => 'MAYOR',
    ]);

    $this->seed(OfficeSeeder::class);

    $legacy->refresh();

    expect($legacy->office_name)->toBe('Office of the Municipal Mayor')
        ->and($legacy->office_code)->toBe('MO')
        ->and(Office::query()->where('office_name', "Mayor's Office")->exists())->toBeFalse();
});
