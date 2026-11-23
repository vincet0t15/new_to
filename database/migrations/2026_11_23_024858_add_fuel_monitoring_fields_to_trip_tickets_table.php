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
            $table->decimal('fuel_price_per_liter', 10, 2)->nullable()->after('liters');
            $table->decimal('fuel_cost', 12, 2)->nullable()->after('fuel_price_per_liter');
            $table->string('fuel_station', 150)->nullable()->after('fuel_cost');
            $table->string('fuel_receipt_no', 100)->nullable()->after('fuel_station');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_tickets', function (Blueprint $table): void {
            $table->dropColumn([
                'fuel_price_per_liter',
                'fuel_cost',
                'fuel_station',
                'fuel_receipt_no',
            ]);
        });
    }
};
