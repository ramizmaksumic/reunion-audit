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
        Schema::create('criterion_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->decimal('points', 6, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criterion_options');
    }
};
