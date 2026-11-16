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
        Schema::create('travel_order_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')->constrained()->cascadeOnDelete();
            // The position in routed_steps this copy belongs to.
            $table->unsignedInteger('step_index');
            $table->foreignId('step_type_id')->nullable()->constrained('workflow_step_types')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->string('acted_via', 30)->nullable();
            $table->timestamps();

            $table->index(['travel_order_id', 'step_index', 'status'], 'travel_order_deliveries_step_idx');
            $table->index(['user_id', 'status'], 'travel_order_deliveries_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_order_deliveries');
    }
};
