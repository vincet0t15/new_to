<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trip_tickets', function (Blueprint $table): void {
            $table->foreignId('fuel_type_id')->nullable()->after('driver_id')->constrained('fuel_types')->nullOnDelete();
        });

        Schema::table('fuel_allocations', function (Blueprint $table): void {
            $table->foreignId('fuel_type_id')->nullable()->after('allocation_date')->constrained('fuel_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_allocations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('fuel_type_id');
        });

        Schema::table('trip_tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('fuel_type_id');
        });
    }
};
