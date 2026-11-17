<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The reference-header fields of a travel order.
     *
     * The legacy text columns (travel_classification, fund_source, destination)
     * stay in place so old rows and the register search keep working. They are
     * mirrored from the lookup rows when the order is written; the foreign keys
     * are the source of truth for new orders.
     */
    public function up(): void
    {
        if (Schema::hasTable('travel_orders')) {
            $this->addLookupColumn('travel_classification_id', 'travel_classification', 'travel_classifications');
            $this->addLookupColumn('fund_source_id', 'travel_classification_id', 'fund_sources');
            $this->addLookupColumn('origin_id', 'fund_source_id', 'locations');
            $this->addLookupColumn('destination_id', 'origin_id', 'locations');
            $this->addLookupColumn('official_station_id', 'destination_id', 'locations');

            if (! Schema::hasColumn('travel_orders', 'other_expenses')) {
                Schema::table('travel_orders', function (Blueprint $table): void {
                    $table->string('other_expenses')->nullable()->after('submission_notes');
                });
            }

            // Existing rows predate the lookups. Match their free-text values
            // to the configured rows by name so an upgraded order keeps its
            // header instead of showing an empty select.
            $this->backfillLookup('travel_classification', 'travel_classification_id', 'travel_classifications');
            $this->backfillLookup('fund_source', 'fund_source_id', 'fund_sources');
            $this->backfillLookup('destination', 'destination_id', 'locations');
        }

        if (! Schema::hasTable('travel_order_expense_type')) {
            Schema::create('travel_order_expense_type', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('travel_order_id');
                $table->unsignedBigInteger('expense_type_id');
                $table->timestamps();

                $table->foreign('travel_order_id', 'to_expense_order_fk')
                    ->references('id')
                    ->on('travel_orders')
                    ->cascadeOnDelete();
                $table->foreign('expense_type_id', 'to_expense_type_fk')
                    ->references('id')
                    ->on('expense_types')
                    ->restrictOnDelete();
                $table->unique(['travel_order_id', 'expense_type_id'], 'to_expense_unique');
            });
        }

        if (! Schema::hasTable('travel_order_transportation_mode')) {
            Schema::create('travel_order_transportation_mode', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('travel_order_id');
                $table->unsignedBigInteger('transportation_mode_id');
                $table->timestamps();

                $table->foreign('travel_order_id', 'to_mode_order_fk')
                    ->references('id')
                    ->on('travel_orders')
                    ->cascadeOnDelete();
                $table->foreign('transportation_mode_id', 'to_mode_mode_fk')
                    ->references('id')
                    ->on('transportation_modes')
                    ->restrictOnDelete();
                $table->unique(['travel_order_id', 'transportation_mode_id'], 'to_mode_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_transportation_mode');
        Schema::dropIfExists('travel_order_expense_type');

        if (! Schema::hasTable('travel_orders')) {
            return;
        }

        foreach (['travel_classification_id', 'fund_source_id', 'origin_id', 'destination_id', 'official_station_id'] as $column) {
            if (! Schema::hasColumn('travel_orders', $column)) {
                continue;
            }

            Schema::table('travel_orders', function (Blueprint $table) use ($column): void {
                $table->dropConstrainedForeignId($column);
            });
        }

        if (Schema::hasColumn('travel_orders', 'other_expenses')) {
            Schema::table('travel_orders', function (Blueprint $table): void {
                $table->dropColumn('other_expenses');
            });
        }
    }

    /**
     * Add a nullable lookup foreign key without failing when an earlier run
     * already created it.
     */
    private function addLookupColumn(string $column, string $after, string $table): void
    {
        if (Schema::hasColumn('travel_orders', $column)) {
            return;
        }

        Schema::table('travel_orders', function (Blueprint $blueprint) use ($column, $after, $table): void {
            $blueprint->foreignId($column)
                ->nullable()
                ->after($after)
                ->constrained($table)
                ->restrictOnDelete();
        });
    }

    /**
     * Copy a legacy text value onto the matching lookup row when one exists.
     */
    private function backfillLookup(string $legacyColumn, string $foreignColumn, string $lookupTable): void
    {
        if (
            ! Schema::hasColumn('travel_orders', $legacyColumn)
            || ! Schema::hasColumn('travel_orders', $foreignColumn)
            || ! Schema::hasTable($lookupTable)
        ) {
            return;
        }

        DB::table('travel_orders')
            ->select(['id', $legacyColumn.' as legacy_value'])
            ->whereNotNull($legacyColumn)
            ->whereNull($foreignColumn)
            ->orderBy('id')
            ->chunkById(200, function ($orders) use ($foreignColumn, $lookupTable): void {
                foreach ($orders as $order) {
                    /** @var array{id: int|string, legacy_value: string|null} $row */
                    $row = (array) $order;
                    $legacy = $row['legacy_value'];

                    if (! is_string($legacy) || trim($legacy) === '') {
                        continue;
                    }

                    $lookupId = DB::table($lookupTable)->where('name', $legacy)->value('id');

                    if ($lookupId === null) {
                        continue;
                    }

                    DB::table('travel_orders')
                        ->where('id', $row['id'])
                        ->update([$foreignColumn => $lookupId]);
                }
            });
    }
};
