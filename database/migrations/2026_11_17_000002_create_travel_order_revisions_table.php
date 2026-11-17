<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('travel_order_revisions')) {
            return;
        }

        Schema::create('travel_order_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->foreignId('returned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->text('return_remarks')->nullable();
            $table->foreignId('revised_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revision_remarks')->nullable();
            $table->string('change_type', 20)->default('minor');
            $table->json('changed_fields')->nullable();
            $table->json('previous_data')->nullable();
            $table->json('new_data')->nullable();
            $table->timestamp('resubmitted_at')->nullable();
            $table->timestamps();

            $table->unique(['travel_order_id', 'revision_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_revisions');
    }
};
