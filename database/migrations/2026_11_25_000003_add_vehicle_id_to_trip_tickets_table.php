<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_tickets', function (Blueprint $table): void {
            // The plate and type stay on the ticket as a snapshot of what was
            // printed. The link only records which registered vehicle was
            // selected, so historical tickets survive a vehicle being retired.
            $table->foreignId('vehicle_id')
                ->nullable()
                ->after('vehicle_type')
                ->constrained('vehicles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trip_tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vehicle_id');
        });
    }
};
