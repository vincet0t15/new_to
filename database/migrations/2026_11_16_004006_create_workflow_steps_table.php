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
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_step_type_id')->constrained()->restrictOnDelete();
            // A null label falls back to the step type's name, so a shared type
            // can still read differently in one workflow without being redefined.
            $table->string('label')->nullable();
            // Null means the step follows the office that filed the travel order.
            $table->foreignId('office_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order');
            $table->boolean('auto_skip_creator')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workflow_id', 'sort_order']);
            $table->index(['workflow_step_type_id', 'office_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
