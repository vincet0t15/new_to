<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores the signature lines resolved when the order is submitted. A filed
     * document then keeps the names it was sent with even after a designation
     * changes hands; a draft is resolved live at print time.
     */
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table): void {
            $table->json('signatories')->nullable()->after('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table): void {
            $table->dropColumn('signatories');
        });
    }
};
