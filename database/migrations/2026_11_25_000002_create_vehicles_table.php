<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('plate_no', 30)->unique();
            $table->string('vehicle_type', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
