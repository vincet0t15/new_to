<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A reusable set of the Itinerary of Travel's (Appendix A) two printed
     * signature lines. Each line names an optional employee and an optional
     * printed title; a blank line keeps the standard role title.
     */
    public function up(): void
    {
        Schema::create('itinerary_signatory_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('certified_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
            $table->string('certified_title')->nullable();
            $table->foreignId('approved_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
            $table->string('approved_title')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itinerary_signatory_groups');
    }
};
