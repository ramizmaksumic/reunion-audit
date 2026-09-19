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
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workbook_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->unique();
            $table->string('group_label');
            $table->text('text');
            $table->enum('answer_type', ['binary', 'threshold', 'graded', 'audit_opinion']);
            $table->enum('priority', ['kritican', 'vazan', 'preporucen']);
            $table->string('evidence_source')->nullable();
            $table->boolean('self_service_eligible')->default(false);
            $table->boolean('is_relevance_gate')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criteria');
    }
};
