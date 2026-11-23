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
        Schema::create('fuel_allocation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fuel_allocation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 50);
            $table->text('remarks')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['fuel_allocation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_allocation_events');
    }
};
