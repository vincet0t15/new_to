<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transportation_fares')) {
            Schema::create('transportation_fares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transportation_mode_id')->constrained()->cascadeOnDelete();
                $table->foreignId('location_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->timestamps();
                $table->unique(['transportation_mode_id', 'location_id']);
            });
        }

        if (! Schema::hasTable('meal_types')) {
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
        }

        $this->seedMealTypes();

        if (! Schema::hasTable('per_diem_meal_rules')) {
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
        }

        $this->createPerDiemLocationPivot();
    }

    public function down(): void
    {
        Schema::dropIfExists('per_diem_configuration_location');
        Schema::dropIfExists('per_diem_meal_rules');
        Schema::dropIfExists('meal_types');
        Schema::dropIfExists('transportation_fares');
    }

    /**
     * Seed the default meal types only when the lookup is still empty so the
     * migration can safely run against a partially migrated database.
     */
    private function seedMealTypes(): void
    {
        if (DB::table('meal_types')->exists()) {
            return;
        }

        $now = now();

        DB::table('meal_types')->insert([
            ['code' => 'breakfast', 'name' => 'Breakfast', 'has_cutoff' => true, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'lunch', 'name' => 'Lunch', 'has_cutoff' => true, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'dinner', 'name' => 'Dinner', 'has_cutoff' => true, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'incidental', 'name' => 'Incidental', 'has_cutoff' => false, 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'other', 'name' => 'Other', 'has_cutoff' => false, 'is_active' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * The default foreign key names are longer than MySQL's 64 character
     * identifier limit, so the pivot uses explicit, shorter constraint names.
     */
    private function createPerDiemLocationPivot(): void
    {
        if (! Schema::hasTable('per_diem_configuration_location')) {
            Schema::create('per_diem_configuration_location', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('per_diem_configuration_id');
                $table->unsignedBigInteger('location_id');
                $table->unique(['per_diem_configuration_id', 'location_id'], 'pdc_location_unique');
                $table->foreign('per_diem_configuration_id', 'pdc_location_configuration_fk')
                    ->references('id')->on('per_diem_configurations')->cascadeOnDelete();
                $table->foreign('location_id', 'pdc_location_location_fk')
                    ->references('id')->on('locations')->restrictOnDelete();
            });

            return;
        }

        $foreignKeys = $this->constraintNames(Schema::getForeignKeys('per_diem_configuration_location'));
        $indexes = $this->constraintNames(Schema::getIndexes('per_diem_configuration_location'));

        Schema::table('per_diem_configuration_location', function (Blueprint $table) use ($foreignKeys, $indexes): void {
            if (! in_array('pdc_location_unique', $indexes, true)) {
                $table->unique(['per_diem_configuration_id', 'location_id'], 'pdc_location_unique');
            }

            if (! in_array('pdc_location_configuration_fk', $foreignKeys, true)) {
                $table->foreign('per_diem_configuration_id', 'pdc_location_configuration_fk')
                    ->references('id')->on('per_diem_configurations')->cascadeOnDelete();
            }

            if (! in_array('pdc_location_location_fk', $foreignKeys, true)) {
                $table->foreign('location_id', 'pdc_location_location_fk')
                    ->references('id')->on('locations')->restrictOnDelete();
            }
        });
    }

    /**
     * @param  array<int, array{name: string}|array<string, mixed>>  $definitions
     * @return array<int, string>
     */
    private function constraintNames(array $definitions): array
    {
        return array_values(array_filter(array_map(
            static fn (array $definition): ?string => is_string($definition['name'] ?? null) ? $definition['name'] : null,
            $definitions,
        )));
    }
};
