<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->safeIndex('travel_orders', ['status', 'created_at']);
        $this->safeIndex('travel_orders', 'returned_at');
        $this->safeIndex('travel_orders', 'last_resubmitted_at');
        $this->safeIndex('travel_order_actions', ['event', 'created_at']);
        $this->safeIndex('travel_order_revisions', 'travel_order_id');
    }

    public function down(): void
    {
        //
    }

    /**
     * @param  string|array<int, string>  $columns
     */
    private function safeIndex(string $table, string|array $columns): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                $table->index($columns);
            });
        } catch (Throwable $e) {
            // Index already exists; nothing to do.
        }
    }
};
