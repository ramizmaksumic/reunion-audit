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
        Schema::table('workbooks', function (Blueprint $table) {
            // Null means "present since the original methodology (v2.0)".
            // A workbook added later (e.g. v2.1) is stamped with the version
            // that introduced it, so ScoringService can tell whether it
            // should count toward an older assessment's score.
            $table->string('introduced_in_version')->nullable()->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workbooks', function (Blueprint $table) {
            $table->dropColumn('introduced_in_version');
        });
    }
};
