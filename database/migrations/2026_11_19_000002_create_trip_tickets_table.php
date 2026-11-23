<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The trip ticket prepared for a travel order that uses a government vehicle.
 *
 * Unlike the reference application this table carries the vehicle details
 * directly (plate, type, fuel, efficiency) instead of pointing at a fleet
 * registry, because the fleet module has not been ported to this application
 * yet. The ticket is numbered when prepared, completed after the trip, and a
 * completed ticket cannot be cancelled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('issued')->index();
            $table->string('vehicle_plate_no', 30);
            $table->string('vehicle_type')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('fuel_type')->nullable();
            $table->decimal('fuel_efficiency', 8, 2)->nullable();
            $table->decimal('distance_km', 8, 2)->default(0);
            $table->boolean('round_trip')->default(true);
            $table->decimal('odometer_start', 12, 2)->nullable();
            $table->decimal('odometer_end', 12, 2)->nullable();
            $table->decimal('liters', 8, 2)->default(0);
            $table->decimal('fuel_consumed', 8, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->string('ticket_no')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_tickets');
    }
};
