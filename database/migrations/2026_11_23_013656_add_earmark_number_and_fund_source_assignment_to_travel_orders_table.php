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
        Schema::table('travel_orders', function (Blueprint $table): void {
            $table->string('earmark_number', 100)->nullable()->after('earmarked_at');
            $table->foreignId('fund_source_assigned_by_user_id')
                ->nullable()
                ->after('fund_source_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('fund_source_assigned_at')
                ->nullable()
                ->after('fund_source_assigned_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table): void {
            $table->dropColumn('earmark_number');
            $table->dropConstrainedForeignId('fund_source_assigned_by_user_id');
            $table->dropColumn('fund_source_assigned_at');
        });
    }
};
