<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The single audit trail for every action taken on a travel order. The
     * revision link is added with the revision tables in Phase 3.
     */
    public function up(): void
    {
        Schema::create('travel_order_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_index')->nullable();
            $table->string('step_label')->nullable();
            $table->string('event', 40);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['travel_order_id', 'id']);
            $table->index(['event', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_order_actions');
    }
};
