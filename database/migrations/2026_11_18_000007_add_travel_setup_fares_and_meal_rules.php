<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportation_fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transportation_mode_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->unique(['transportation_mode_id', 'location_id']);
        });

        Schema::create('meal_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->boolean('has_cutoff')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        $now = now();

        DB::table('meal_types')->insert([
            ['code' => 'breakfast', 'name' => 'Breakfast', 'has_cutoff' => true, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'lunch', 'name' => 'Lunch', 'has_cutoff' => true, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'dinner', 'name' => 'Dinner', 'has_cutoff' => true, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'incidental', 'name' => 'Incidental', 'has_cutoff' => false, 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'other', 'name' => 'Other', 'has_cutoff' => false, 'is_active' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::create('per_diem_meal_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('per_diem_configuration_id')->constrained()->cascadeOnDelete();
            $table->string('meal_type');
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->time('cutoff_time')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('meal_type')->references('code')->on('meal_types')->restrictOnDelete();
            $table->index(['per_diem_configuration_id', 'sort_order']);
        });

        Schema::create('per_diem_configuration_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('per_diem_configuration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->unique(['per_diem_configuration_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('per_diem_configuration_location');
        Schema::dropIfExists('per_diem_meal_rules');
        Schema::dropIfExists('meal_types');
        Schema::dropIfExists('transportation_fares');
    }
};
