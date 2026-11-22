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
            $table->foreignId('earmarked_by_user_id')
                ->nullable()
                ->after('fund_source')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('earmarked_at')->nullable()->after('earmarked_by_user_id');
            $table->text('earmark_remarks')->nullable()->after('earmarked_at');

            $table->foreignId('recorded_by_user_id')
                ->nullable()
                ->after('to_number')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('recorded_at')->nullable()->after('recorded_by_user_id');
            $table->text('recording_remarks')->nullable()->after('recorded_at');
            $table->timestamp('distributed_at')->nullable()->after('recording_remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('earmarked_by_user_id');
            $table->dropColumn(['earmarked_at', 'earmark_remarks']);
            $table->dropConstrainedForeignId('recorded_by_user_id');
            $table->dropColumn(['recorded_at', 'recording_remarks', 'distributed_at']);
        });
    }
};
