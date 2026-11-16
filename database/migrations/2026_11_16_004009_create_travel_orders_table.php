<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Only the columns the routing engine relies on are created here. The
     * submission, revision, and detail fields belong to the phases that build
     * those features.
     */
    public function up(): void
    {
        Schema::create('travel_orders', function (Blueprint $table) {
            $table->id();
            $table->string('control_no', 40)->nullable()->unique();
            $table->string('to_number', 40)->nullable()->unique();
            $table->foreignId('employee_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('workflow_id')->nullable()->constrained()->nullOnDelete();
            $table->text('purpose')->nullable();
            $table->string('destination')->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('fund_source')->nullable();
            $table->string('travel_classification')->nullable();
            $table->string('status', 30)->default('draft')->index();
            // The frozen list of workflow_steps ids that this order follows.
            $table->json('routed_steps')->nullable();
            $table->unsignedInteger('current_step_index')->nullable();
            // Legacy pointer kept in step with the current step's first holder.
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['created_by', 'status']);
            $table->index(['office_id', 'status']);
            $table->index(['employee_id', 'date_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_orders');
    }
};
