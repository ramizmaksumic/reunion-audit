<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->string('key')->unique();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            // Fraction of its area's overall score this workbook contributes.
            // No DB default: seeder/admin must set it explicitly (evenly split within the area by default).
            $table->decimal('weight', 8, 6);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workbooks');
    }
};
