<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * An office names the signatory groups its printed travel order and
     * Appendix A use. Null means every line keeps its designation-derived
     * signer or standard role title.
     */
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table): void {
            $table->foreignId('signatory_group_id')
                ->nullable()
                ->after('workflow_id')
                ->constrained('signatory_groups')
                ->nullOnDelete();
            $table->foreignId('itinerary_signatory_group_id')
                ->nullable()
                ->after('signatory_group_id')
                ->constrained('itinerary_signatory_groups')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('itinerary_signatory_group_id');
            $table->dropConstrainedForeignId('signatory_group_id');
        });
    }
};
