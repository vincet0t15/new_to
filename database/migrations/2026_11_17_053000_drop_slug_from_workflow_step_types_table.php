<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workflow_step_types', function (Blueprint $table) {
            $table->dropUnique('workflow_step_types_slug_unique');
            $table->dropColumn('slug');
        });
    }

    /**
     * Restore generated slugs if this migration is rolled back.
     */
    public function down(): void
    {
        Schema::table('workflow_step_types', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        $usedSlugs = [];

        DB::table('workflow_step_types')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $stepType) use (&$usedSlugs): void {
                $base = Str::slug($stepType->name, '_') ?: 'step_type';
                $slug = $base;
                $suffix = 2;

                while (isset($usedSlugs[$slug])) {
                    $slug = $base.'_'.$suffix;
                    $suffix++;
                }

                $usedSlugs[$slug] = true;

                DB::table('workflow_step_types')
                    ->where('id', $stepType->id)
                    ->update(['slug' => $slug]);
            });

        Schema::table('workflow_step_types', function (Blueprint $table) {
            $table->unique('slug');
        });
    }
};
