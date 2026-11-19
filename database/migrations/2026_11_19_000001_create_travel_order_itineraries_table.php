<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The legs of a travel order.
 *
 * Each row is one place-to-place movement with its own dates, times, fare and
 * per diem. The amounts may be written by hand or computed from the configured
 * transportation fares and per diem rules, which is why each carries an
 * "is_auto" flag: an automatic figure can be recomputed, a manual one must be
 * left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_order_itineraries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')->constrained()->cascadeOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('departure_time', 5);
            $table->string('arrival_time', 5)->nullable();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('activity');
            $table->string('reason')->nullable();
            $table->foreignId('transportation_mode_id')->nullable()->constrained('transportation_modes')->nullOnDelete();
            $table->decimal('transportation_fare', 12, 2)->nullable();
            $table->boolean('transportation_fare_is_auto')->default(false);
            $table->decimal('per_diem', 12, 2)->nullable();
            $table->boolean('per_diem_is_auto')->default(false);
            $table->decimal('registration_fee', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['travel_order_id', 'date_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_itineraries');
    }
};
