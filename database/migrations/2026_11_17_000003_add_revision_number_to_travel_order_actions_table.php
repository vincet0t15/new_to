<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('travel_order_actions', 'revision_id')) {
            return;
        }

        Schema::table('travel_order_actions', function (Blueprint $table) {
            $table->foreignId('revision_id')->nullable()->after('event')
                ->constrained('travel_order_revisions')->nullOnDelete();
            $table->index(['travel_order_id', 'revision_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('travel_order_actions', 'revision_id')) {
            return;
        }

        Schema::table('travel_order_actions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revision_id');
            $table->dropIndex(['travel_order_id', 'revision_id']);
        });
    }
};
