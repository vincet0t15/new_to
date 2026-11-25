<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_order_actions', function (Blueprint $table): void {
            // An internal note is part of the audit trail but is never shown to
            // the filer. Everyone who may act on the order can still read it.
            $table->boolean('is_internal')->default(false)->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('travel_order_actions', function (Blueprint $table): void {
            $table->dropColumn('is_internal');
        });
    }
};
