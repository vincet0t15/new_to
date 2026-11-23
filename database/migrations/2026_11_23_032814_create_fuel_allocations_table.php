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
        Schema::create('fuel_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->date('allocation_date');
            $table->string('reference_no', 100)->nullable();
            $table->string('fuel_type', 50)->nullable();
            $table->decimal('allocated_liters', 12, 2)->default(0);
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['office_id', 'allocation_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_allocations');
    }
};
