<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('per_diem_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->decimal('daily_rate', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('per_diem_configurations');
    }
};
