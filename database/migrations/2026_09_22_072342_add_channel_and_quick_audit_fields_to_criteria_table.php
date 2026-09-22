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
        Schema::table('criteria', function (Blueprint $table) {
            $table->enum('channel', ['web', 'gbp', 'social'])->nullable()->after('is_relevance_gate');
            $table->boolean('quick_audit')->default(false)->after('channel');
            $table->string('quick_block')->nullable()->after('quick_audit');
            $table->enum('quick_source', ['auto_http', 'auto_psi', 'auto_places', 'self'])->nullable()->after('quick_block');
            $table->text('quick_question')->nullable()->after('quick_source');
            $table->json('quick_option_labels')->nullable()->after('quick_question');
            $table->text('quick_note')->nullable()->after('quick_option_labels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('criteria', function (Blueprint $table) {
            $table->dropColumn([
                'channel',
                'quick_audit',
                'quick_block',
                'quick_source',
                'quick_question',
                'quick_option_labels',
                'quick_note',
            ]);
        });
    }
};
