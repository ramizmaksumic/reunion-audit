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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->text('business_model_notes')->nullable();
            $table->string('b2b_or_b2c')->nullable();
            $table->string('market_scope')->nullable();
            $table->boolean('has_physical_location')->default(false);
            $table->boolean('sells_online')->default(false);
            $table->boolean('provides_online_services')->default(false);
            $table->boolean('works_by_appointment')->default(false);
            $table->boolean('has_multiple_locations')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
