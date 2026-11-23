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
        Schema::table('fuel_allocations', function (Blueprint $table): void {
            $table->string('type', 20)->default('allocation')->after('office_id')->index();
            $table->foreignId('source_office_id')->nullable()->after('type')->constrained('offices')->nullOnDelete();
            $table->foreignId('related_allocation_id')->nullable()->after('source_office_id')->constrained('fuel_allocations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_allocations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('related_allocation_id');
            $table->dropConstrainedForeignId('source_office_id');
            $table->dropColumn('type');
        });
    }
};
