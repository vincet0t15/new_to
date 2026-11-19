<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A named set of the three signature lines the printed travel order
     * carries — CERTIFIED AS TO FUND AVAILABILITY, APPROVED, and OIC — so an
     * office can save its own signers and reach them again from a list. A null
     * employee on a line keeps that line's usual designation-derived signer.
     */
    public function up(): void
    {
        Schema::create('signatory_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('certified_signatory_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
            $table->string('certified_signatory_title')->nullable();
            $table->foreignId('approved_signatory_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
            $table->string('approved_signatory_title')->nullable();
            $table->foreignId('oic_signatory_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
            $table->string('oic_signatory_title')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatory_groups');
    }
};
