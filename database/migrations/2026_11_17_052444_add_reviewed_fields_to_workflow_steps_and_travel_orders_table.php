<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->json('reviewed_fields')->nullable()->after('auto_skip_creator');
        });

        Schema::table('travel_orders', function (Blueprint $table) {
            $table->json('routed_step_review_fields')->nullable()->after('routed_steps');
        });

        $fieldsByLegacySlug = [
            'department_head_approval' => [
                'purpose',
                'date_from',
                'date_to',
                'travel_classification_id',
                'origin_id',
                'destination_id',
                'official_station_id',
                'employee_id',
            ],
            'budget_review' => [
                'fund_source_id',
                'other_expenses',
                'expense_type_ids',
                'transportation_mode_ids',
            ],
        ];

        $reviewedFieldsByStepId = [];

        DB::table('workflow_steps')
            ->join('workflow_step_types', 'workflow_steps.workflow_step_type_id', '=', 'workflow_step_types.id')
            ->select(['workflow_steps.id', 'workflow_step_types.slug'])
            ->orderBy('workflow_steps.id')
            ->get()
            ->each(function (object $step) use ($fieldsByLegacySlug, &$reviewedFieldsByStepId): void {
                $fields = $fieldsByLegacySlug[$step->slug] ?? [];

                DB::table('workflow_steps')
                    ->where('id', $step->id)
                    ->update(['reviewed_fields' => json_encode($fields, JSON_THROW_ON_ERROR)]);

                $reviewedFieldsByStepId[(int) $step->id] = $fields;
            });

        DB::table('travel_orders')
            ->whereNotNull('routed_steps')
            ->orderBy('id')
            ->chunkById(200, function ($orders) use (&$reviewedFieldsByStepId): void {
                foreach ($orders as $order) {
                    $stepIds = json_decode($order->routed_steps, true);

                    if (! is_array($stepIds)) {
                        continue;
                    }

                    $reviewedFields = array_map(
                        fn (int|string $stepId): array => $reviewedFieldsByStepId[(int) $stepId] ?? [],
                        array_values($stepIds),
                    );

                    DB::table('travel_orders')
                        ->where('id', $order->id)
                        ->update([
                            'routed_step_review_fields' => json_encode($reviewedFields, JSON_THROW_ON_ERROR),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn('routed_step_review_fields');
        });

        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropColumn('reviewed_fields');
        });
    }
};
