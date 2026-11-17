<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('travel_orders', 'revision_number')) {
            return;
        }

        Schema::table('travel_orders', function (Blueprint $table) {
            $table->unsignedInteger('revision_number')->default(1)->after('status');
            $table->foreignId('returned_by_user_id')->nullable()->after('revision_number')
                ->constrained('users')->nullOnDelete();
            $table->unsignedInteger('returned_from_step_index')->nullable()->after('returned_by_user_id');
            $table->string('returned_from_step')->nullable()->after('returned_from_step_index');
            $table->text('return_remarks')->nullable()->after('returned_from_step');
            $table->timestamp('returned_at')->nullable()->after('return_remarks');
            $table->timestamp('last_resubmitted_at')->nullable()->after('returned_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('travel_orders', 'revision_number')) {
            return;
        }

        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('returned_by_user_id');
            $table->dropColumn([
                'revision_number',
                'returned_from_step_index',
                'returned_from_step',
                'return_remarks',
                'returned_at',
                'last_resubmitted_at',
            ]);
        });
    }
};
